<?php

namespace App\Services;

use App\Models\Division;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * ProcurementValidationService
 *
 * Service terpusat yang mengimplementasikan 4-Gate Smart Validation Engine.
 *
 * PERUBAHAN ARSITEKTUR (Multi-Role Pipeline):
 * - Gate 1-3 dijalankan MANUAL oleh Staff via tombol di halaman detail ticket
 *   (hanya setelah PFA meng-approve dokumen → status = need_to_validate).
 * - Gate 4 (Document) sudah selesai di tahap PFA review → tidak diulang.
 * - Budget lock (Gate 1) TIDAK langsung mengurangi pagu — pagu baru dikunci
 *   saat Head Division approve (tahap 6).
 *
 * Urutan gate saat dijalankan manual:
 *   Gate 1 → Budget sufficiency check (TANPA lock)
 *   Gate 2 → CAPEX/OPEX classification
 *   Gate 3 → Eligibility check
 */
class ProcurementValidationService
{
    const CAPEX_THRESHOLD = 500_000_000.00;

    const CAPEX_KEYWORDS = [
        'server', 'hardware', 'infrastruktur', 'gedung', 'kendaraan',
        'mesin', 'peralatan berat', 'jaringan', 'network', 'data center',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // ENTRY POINT: MANUAL VALIDATION (dipanggil dari TicketController)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Jalankan Gate 1-3 pada ticket yang sudah di-approve dokumennya oleh PFA.
     *
     * Dipanggil oleh Staff via tombol "Run Validation" di halaman detail ticket.
     * Ticket harus berstatus 'need_to_validate'.
     *
     * @param  Ticket  $ticket  Ticket yang akan divalidasi
     * @return Ticket           Ticket yang sudah divalidasi dan statusnya di-update
     *
     * @throws ValidationException Jika salah satu gate gagal.
     */
    public function runValidationOnTicket(Ticket $ticket): Ticket
    {
        // ── Gate 1: Budget Sufficiency Check (TANPA lock) ────────────────
        $this->runGate1BudgetCheck($ticket);

        // ── Gate 2: CAPEX/OPEX Classification ────────────────────────────
        $expenditureType = $this->runGate2ExpenditureClassification(
            $ticket->title,
            (float) $ticket->budget_estimated
        );

        // ── Gate 3: Eligibility Check ────────────────────────────────────
        $this->runGate3EligibilityCheck($ticket->user, $ticket->vendor_name);

        // ── Semua gate lolos → update ticket ─────────────────────────────
        $ticket->update([
            'expenditure_type' => $expenditureType,
            'status'           => Ticket::STATUS_PENDING_DEPT_HEAD,
        ]);

        return $ticket->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BUDGET LOCK: Dipanggil saat Head Div APPROVE (bukan saat submit)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Kunci pagu anggaran divisi secara atomik saat Head Div approve ticket.
     *
     * Menggunakan lockForUpdate() untuk mencegah race condition / double-spending.
     *
     * @throws ValidationException Jika pagu tidak mencukupi saat approval.
     */
    public function lockBudgetOnApproval(Ticket $ticket): void
    {
        DB::transaction(function () use ($ticket) {
            $division = Division::where('id', $ticket->division_id)
                                ->lockForUpdate()
                                ->first();

            if (! $division) {
                throw ValidationException::withMessages([
                    'division_id' => ['Divisi requestor tidak ditemukan.'],
                ]);
            }

            if (! $division->hasSufficientBudget((float) $ticket->budget_estimated)) {
                throw ValidationException::withMessages([
                    'budget_estimated' => [
                        sprintf(
                            'Pagu divisi %s tidak mencukupi (sisa: Rp %s, dibutuhkan: Rp %s).',
                            $division->name,
                            number_format($division->remaining_budget, 0, ',', '.'),
                            number_format($ticket->budget_estimated, 0, ',', '.')
                        ),
                    ],
                ]);
            }

            // Smart Lock: kurangi pagu secara atomik
            $division->decrement('remaining_budget', (float) $ticket->budget_estimated);
        });
    }

    /**
     * Kembalikan pagu anggaran divisi saat ticket di-decline Head Div.
     * Hanya jika ticket sebelumnya sudah approved (pagu sudah dikunci).
     */
    public function refundBudget(Ticket $ticket): void
    {
        DB::transaction(function () use ($ticket) {
            $division = Division::where('id', $ticket->division_id)
                                ->lockForUpdate()
                                ->first();

            if ($division) {
                $division->increment('remaining_budget', (float) $ticket->budget_estimated);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DOCUMENT UPLOAD HELPER
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Upload dokumen Izin Prinsip ke S3 atau local disk (fallback).
     */
    public function uploadDocument(\Illuminate\Http\UploadedFile $file): string
    {
        $s3Configured = !empty(config('filesystems.disks.s3.key'))
                     && !empty(config('filesystems.disks.s3.bucket'));

        if ($s3Configured) {
            try {
                $path = Storage::disk('s3')->putFile('tickets', $file);
                return Storage::disk('s3')->url($path);
            } catch (\Exception $e) {
                Log::warning('S3 upload gagal, fallback ke local: ' . $e->getMessage());
            }
        }

        try {
            $path = $file->store('tickets', 'public');
            return Storage::disk('public')->url($path);
        } catch (\Exception $e) {
            Log::error('Local upload gagal: ' . $e->getMessage());
            throw ValidationException::withMessages([
                'document_path' => ['Gagal mengunggah dokumen. Pastikan folder storage memiliki izin tulis.'],
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GATE 1: BUDGET SUFFICIENCY CHECK (tanpa lock)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Cek apakah pagu divisi MASIH CUKUP untuk nominal ticket ini.
     * TIDAK mengurangi pagu — hanya pengecekan read-only.
     * Pagu baru dikurangi saat Head Div approve.
     */
    private function runGate1BudgetCheck(Ticket $ticket): void
    {
        $division = Division::find($ticket->division_id);

        if (! $division) {
            throw ValidationException::withMessages([
                'division_id' => ['Departemen requestor tidak ditemukan. Hubungi administrator.'],
            ]);
        }

        if (! $division->hasSufficientBudget((float) $ticket->budget_estimated)) {
            throw ValidationException::withMessages([
                'budget_estimated' => [
                    sprintf(
                        'Nilai pengadaan (Rp %s) melebihi sisa pagu departemen %s (Rp %s).',
                        number_format($ticket->budget_estimated, 0, ',', '.'),
                        $division->name,
                        number_format($division->remaining_budget, 0, ',', '.')
                    ),
                ],
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GATE 2: AUTOMATED CAPEX / OPEX CLASSIFICATION
    // ─────────────────────────────────────────────────────────────────────────

    private function runGate2ExpenditureClassification(string $title, float $amount): string
    {
        if ($amount >= self::CAPEX_THRESHOLD) {
            return Ticket::EXPENDITURE_CAPEX;
        }

        $titleLower = strtolower($title);
        foreach (self::CAPEX_KEYWORDS as $keyword) {
            if (str_contains($titleLower, $keyword)) {
                return Ticket::EXPENDITURE_CAPEX;
            }
        }

        return Ticket::EXPENDITURE_OPEX;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GATE 3: ELIGIBILITY CHECK
    // ─────────────────────────────────────────────────────────────────────────

    private function runGate3EligibilityCheck(User $requestor, string $vendorName): void
    {
        if (is_null($requestor->division_id)) {
            throw ValidationException::withMessages([
                'user_id' => ['Requestor tidak memiliki assignment departemen yang valid.'],
            ]);
        }

        if (empty(trim($vendorName))) {
            throw ValidationException::withMessages([
                'vendor_name' => ['Nama vendor tidak valid.'],
            ]);
        }
    }
}
