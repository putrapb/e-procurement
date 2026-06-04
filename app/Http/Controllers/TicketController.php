<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Models\Ticket;
use App\Services\ProcurementValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * TicketController
 *
 * Controller ini dijaga tetap slim sesuai arsitektur master context.
 * Seluruh logika validasi bisnis (4-Gate Engine) didelegasikan ke
 * ProcurementValidationService untuk memastikan kode tetap testable via Pest.
 */
class TicketController extends Controller
{
    public function __construct(
        private readonly ProcurementValidationService $validationService
    ) {}

    /**
     * Tampilkan daftar ticket milik user yang sedang login.
     */
    public function index(Request $request): View
    {
        $tickets = $request->user()
                           ->tickets()
                           ->with('division')
                           ->latest()
                           ->paginate(10);

        return view('tickets.index', compact('tickets'));
    }

    /**
     * Tampilkan form pengajuan ticket baru.
     */
    public function create(): View
    {
        return view('tickets.create');
    }

    /**
     * Proses pengajuan ticket baru melalui 4-Gate Validation Pipeline.
     *
     * Alur:
     * 1. StoreTicketRequest memvalidasi format & keberadaan field (sintaksis).
     * 2. ProcurementValidationService menjalankan Gate 1 → 4 (semantik/bisnis).
     * 3. Redirect berdasarkan hasil: sukses ke detail, gagal kembali ke form.
     */
    public function store(StoreTicketRequest $request): RedirectResponse
    {
        try {
            $ticket = $this->validationService->runValidationPipeline(
                requestor: $request->user(),
                payload: $request->validated(),
            );

            $message = $ticket->isDraft()
                ? 'Ticket berhasil dibuat. Lengkapi dokumen Izin Prinsip untuk melanjutkan proses validasi.'
                : 'Ticket berhasil diajukan dan pagu anggaran divisi Anda telah dikunci.';

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', $message);

        } catch (ValidationException $e) {
            // Re-throw agar Laravel menangani redirect balik ke form + flash errors
            throw $e;
        }
    }

    /**
     * Tampilkan detail satu ticket.
     */
    public function show(Ticket $ticket): View
    {
        // Otorisasi menggunakan TicketPolicy
        Gate::authorize('view', $ticket);

        $ticket->load('user', 'division');

        return view('tickets.show', compact('ticket'));
    }

    /**
     * Tampilkan form edit ticket.
     */
    public function edit(Ticket $ticket): View
    {
        // Otorisasi menggunakan TicketPolicy
        Gate::authorize('update', $ticket);

        return view('tickets.edit', compact('ticket'));
    }

    /**
     * Proses pembaruan data ticket.
     */
    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        // Otorisasi menggunakan TicketPolicy
        Gate::authorize('update', $ticket);

        // Validasi basic untuk form update
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'vendor_name' => ['required', 'string', 'max:255'],
        ]);

        $ticket->update($validated);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket berhasil diperbarui.');
    }

    /**
     * Proses penghapusan ticket.
     */
    public function destroy(Ticket $ticket): RedirectResponse
    {
        // Otorisasi menggunakan TicketPolicy
        Gate::authorize('delete', $ticket);

        $ticket->delete();

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Ticket berhasil dihapus.');
    }

    /**
     * Proses persetujuan tiket pengadaan (Status -> approved).
     */
    public function approve(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('approve', $ticket);

        // Hanya tiket yang berstatus 'budget_locked' yang bisa disetujui
        if ($ticket->status !== Ticket::STATUS_BUDGET_LOCKED) {
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('error', 'Hanya tiket yang berstatus Budget Locked yang dapat disetujui.');
        }

        $ticket->update(['status' => Ticket::STATUS_APPROVED]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Pengajuan pengadaan telah disetujui.');
    }

    /**
     * Proses penolakan tiket pengadaan (Status -> rejected, dan lakukan refund pagu divisi).
     */
    public function reject(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('approve', $ticket);

        // Hanya tiket yang berstatus 'budget_locked' yang bisa ditolak dan dikembalikan anggarannya
        if ($ticket->status !== Ticket::STATUS_BUDGET_LOCKED) {
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('error', 'Hanya tiket yang berstatus Budget Locked yang dapat ditolak.');
        }

        // Lakukan pengembalian pagu (refund) secara aman dan transaksional
        \Illuminate\Support\Facades\DB::transaction(function () use ($ticket) {
            // Lock baris divisi untuk mencegah race condition
            $division = \App\Models\Division::where('id', $ticket->division_id)
                                            ->lockForUpdate()
                                            ->first();
            
            if ($division) {
                $division->increment('remaining_budget', $ticket->budget_estimated);
            }

            $ticket->update(['status' => Ticket::STATUS_REJECTED]);
        });

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Pengajuan pengadaan ditolak dan pagu anggaran divisi telah dikembalikan.');
    }
}
