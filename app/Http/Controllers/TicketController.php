<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Models\Ticket;
use App\Services\ProcurementValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        // Policy: pastikan hanya pemilik ticket atau admin yang bisa akses
        // (akan diimplementasikan via TicketPolicy pada iterasi berikutnya)
        abort_unless(
            auth()->id() === $ticket->user_id,
            403,
            'Anda tidak memiliki akses ke ticket ini.'
        );

        $ticket->load('user', 'division');

        return view('tickets.show', compact('ticket'));
    }
}
