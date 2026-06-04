<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\ProcurementValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Services\PurchaseOrderService;

/**
 * TicketController — Multi-Role Approval Pipeline
 *
 * Actions per role:
 *   Staff:     store, update (revision), runValidation
 *   PFA:       reviewApprove, reviewReject, generatePO
 *   Head Dept: forward
 *   Head Div:  approve, decline
 *   All:       index, show
 */
class TicketController extends Controller
{
    public function __construct(
        private readonly ProcurementValidationService $validationService,
        private readonly PurchaseOrderService $poService
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // SHARED (Semua Role)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Tampilkan daftar ticket.
     * - Staff: hanya tiket miliknya
     * - PFA/Head Dept/Head Div: semua tiket
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Ticket::with('user', 'division')->latest();

        if ($user->isStaff()) {
            $query->where('user_id', $user->id);
        }

        $tickets = $query->paginate(15);

        return view('tickets.index', compact('tickets'));
    }

    /**
     * Tampilkan detail satu ticket.
     */
    public function show(Ticket $ticket): View
    {
        Gate::authorize('view', $ticket);

        $ticket->load('user', 'division', 'purchaseOrder');

        return view('tickets.show', compact('ticket'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STAFF: Create & Submit Ticket
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Tampilkan form pengajuan ticket baru (Staff only).
     */
    public function create(): View
    {
        Gate::authorize('create', Ticket::class);

        return view('tickets.create');
    }

    /**
     * Proses pembuatan ticket baru oleh Staff.
     *
     * Flow: Staff submit → status = pending_review → PFA akan review dokumen.
     * 4-Gate Engine BELUM dijalankan di tahap ini.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Ticket::class);

        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:5000'],
            'budget_estimated' => ['required', 'numeric', 'min:1'],
            'vendor_name'      => ['required', 'string', 'max:255'],
            'document_path'    => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        // Upload dokumen
        $documentUrl = $this->validationService->uploadDocument($request->file('document_path'));

        // Buat ticket dengan status pending_review
        $ticket = Ticket::create([
            'user_id'          => $request->user()->id,
            'division_id'      => $request->user()->division_id,
            'title'            => $validated['title'],
            'description'      => $validated['description'] ?? null,
            'budget_estimated' => $validated['budget_estimated'],
            'vendor_name'      => $validated['vendor_name'],
            'document_path'    => $documentUrl,
            'status'           => Ticket::STATUS_PENDING_REVIEW,
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket berhasil diajukan! Menunggu review dokumen oleh PFA.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STAFF: Edit & Revise (saat status = revision)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Tampilkan form edit ticket (Staff only, saat status = revision).
     */
    public function edit(Ticket $ticket): View
    {
        Gate::authorize('update', $ticket);

        return view('tickets.edit', compact('ticket'));
    }

    /**
     * Proses revisi ticket oleh Staff.
     * Setelah revisi, status kembali ke pending_review untuk di-review ulang PFA.
     */
    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        $validated = $request->validate([
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string', 'max:5000'],
            'vendor_name'   => ['required', 'string', 'max:255'],
            'document_path' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        // Jika ada file baru, upload dan ganti
        if ($request->hasFile('document_path')) {
            $validated['document_path'] = $this->validationService->uploadDocument(
                $request->file('document_path')
            );
        } else {
            unset($validated['document_path']);
        }

        // Update ticket dan kembalikan status ke pending_review
        $ticket->update(array_merge($validated, [
            'status'         => Ticket::STATUS_PENDING_REVIEW,
            'rejection_note' => null, // Clear rejection note
        ]));

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket telah direvisi dan dikirim ulang untuk review PFA.');
    }

    /**
     * Hapus ticket (Staff only, saat pending_review).
     */
    public function destroy(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('delete', $ticket);

        $ticket->delete();

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Ticket berhasil dihapus.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STAFF: Run 4-Gate Validation (saat status = need_to_validate)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Staff menjalankan 4-Gate Smart Validation Engine secara manual.
     * Tombol ini hanya muncul setelah PFA approve dokumen.
     */
    public function runValidation(Ticket $ticket): RedirectResponse
    {
        if ($ticket->status !== Ticket::STATUS_NEED_TO_VALIDATE) {
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('info', 'Validasi 4-Gate sudah dijalankan untuk tiket ini.');
        }

        Gate::authorize('runValidation', $ticket);

        try {
            $this->validationService->runValidationOnTicket($ticket);

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', '4-Gate Validation berhasil! Ticket diteruskan ke Head Department.');

        } catch (ValidationException $e) {
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('error', $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PFA: Document Review (saat status = pending_review)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * PFA meng-approve dokumen → status berubah ke need_to_validate.
     */
    public function reviewApprove(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('reviewDocument', $ticket);

        $ticket->update([
            'status'         => Ticket::STATUS_NEED_TO_VALIDATE,
            'rejection_note' => null,
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Dokumen diverifikasi. Staff dapat menjalankan validasi 4-Gate.');
    }

    /**
     * PFA menolak dokumen → status berubah ke revision + catatan penolakan.
     */
    public function reviewReject(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('reviewDocument', $ticket);

        $validated = $request->validate([
            'rejection_note' => ['required', 'string', 'max:2000'],
        ]);

        $ticket->update([
            'status'         => Ticket::STATUS_REVISION,
            'rejection_note' => $validated['rejection_note'],
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Dokumen ditolak. Staff akan diminta untuk merevisi.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HEAD DEPT: Forward (saat status = pending_dept_head)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Head Dept meneruskan ticket ke Head Division.
     */
    public function forward(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('forward', $ticket);

        $ticket->update([
            'status' => Ticket::STATUS_PENDING_DIV_HEAD,
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket diteruskan ke Head Division untuk keputusan akhir.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HEAD DIV: Approve / Decline (saat status = pending_div_head)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Head Div menyetujui ticket → pagu anggaran dikunci di sini.
     */
    public function approve(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('decide', $ticket);

        try {
            // Kunci pagu anggaran secara atomik
            $this->validationService->lockBudgetOnApproval($ticket);

            $ticket->update([
                'status' => Ticket::STATUS_APPROVED,
            ]);

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', 'Pengadaan DISETUJUI dan pagu anggaran telah dikunci.');

        } catch (ValidationException $e) {
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('error', 'Gagal approve: ' . collect($e->errors())->flatten()->first());
        }
    }

    /**
     * Head Div menolak ticket.
     */
    public function decline(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('decide', $ticket);

        $validated = $request->validate([
            'rejection_note' => ['required', 'string', 'max:2000'],
        ]);

        $ticket->update([
            'status'         => Ticket::STATUS_DECLINED,
            'rejection_note' => $validated['rejection_note'],
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Pengadaan DITOLAK oleh Head Division.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PFA: Generate Purchase Order (saat status = approved)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * PFA men-generate Purchase Order untuk ticket yang sudah approved.
     */
    public function generatePO(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('generatePo', $ticket);

        // Auto-generate nomor PO: PO-YYYYMMDD-XXXX
        $poNumber = 'PO-' . now()->format('Ymd') . '-' . str_pad($ticket->id, 4, '0', STR_PAD_LEFT);

        $po = $ticket->purchaseOrder()->create([
            'po_number'    => $poNumber,
            'generated_by' => $request->user()->id,
            'notes'        => $request->input('notes'),
        ]);

        // Generate PDF
        try {
            $pdfUrl = $this->poService->generatePoPdf($po, $ticket);
            $po->update(['pdf_path' => $pdfUrl]);
        } catch (\Exception $e) {
            $po->delete();
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('error', 'Gagal menghasilkan PDF Purchase Order: ' . $e->getMessage());
        }

        $ticket->update([
            'status' => Ticket::STATUS_PO_GENERATED,
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Purchase Order ' . $poNumber . ' berhasil di-generate!');
    }
}
