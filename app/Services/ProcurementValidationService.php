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
 * Service class terpusat yang mengimplementasikan 4-Gate Smart Validation Engine
 * untuk sistem E-Procurement BNI. Controller harus tetap slim — seluruh logika
 * validasi bisnis yang kompleks didelegasikan ke sini.
 *
 * Urutan eksekusi gate:
 *   Gate 1 → Gate 2 → Gate 3 → Gate 4
 */
class ProcurementValidationService
{
    /**
     * Threshold nilai anggaran (dalam Rupiah) untuk klasifikasi CAPEX otomatis.
     * Sesuai kebijakan akuntansi korporat BNI: >= 500 juta = CAPEX.
     */
    const CAPEX_THRESHOLD = 500_000_000.00;

    /**
     * Kategori item yang secara otomatis diklasifikasikan sebagai CAPEX
     * terlepas dari nilai anggaran (aset tetap, infrastruktur, hardware).
     */
    const CAPEX_KEYWORDS = [
        'server', 'hardware', 'infrastruktur', 'gedung', 'kendaraan',
        'mesin', 'peralatan berat', 'jaringan', 'network', 'data center',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // ENTRY POINT UTAMA
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Jalankan seluruh 4-Gate validation pipeline pada data payload ticket.
     *
     * Proses ini bersifat atomik: jika salah satu gate gagal, seluruh transaksi
     * dibatalkan dan tidak ada perubahan yang tersimpan ke database.
     *
     * @param  User   $requestor   User yang mengajukan ticket.
     * @param  array  $payload     Data ticket yang telah lolos Form Request.
     * @return Ticket              Ticket yang telah dibuat dan dikunci.
     *
     * @throws ValidationException Jika salah satu gate gagal.
     */
    public function runValidationPipeline(User $requestor, array $payload): Ticket
    {
        return DB::transaction(function () use ($requestor, $payload) {

            // ── Gate 1: Budget Checking & Smart Locking ──────────────────────
            $division = $this->runGate1BudgetCheck($requestor, $payload['budget_estimated']);

            // ── Gate 2: Automated CAPEX / OPEX Classification ────────────────
            $expenditureType = $this->runGate2ExpenditureClassification(
                $payload['title'],
                (float) $payload['budget_estimated']
            );

            // ── Gate 3: Vendor & Requester Eligibility Verification ──────────
            $this->runGate3EligibilityCheck($requestor, $payload['vendor_name']);

            // ── Gate 4: Document Upload & Cloud Storage Integration ──────────
            $documentUrl = null;
            if (isset($payload['document_path'])) {
                if ($payload['document_path'] instanceof \Illuminate\Http\UploadedFile) {
                    try {
                        // Unggah file ke disk 's3' (Supabase Storage) di folder 'tickets'
                        $path = Storage::disk('s3')->putFile('tickets', $payload['document_path']);
                        
                        // Dapatkan URL publik file hasil upload
                        $documentUrl = Storage::disk('s3')->url($path);
                    } catch (\Exception $e) {
                        Log::error('S3 upload failed: ' . $e->getMessage());
                        throw ValidationException::withMessages([
                            'document_path' => ['Gagal mengunggah dokumen Izin Prinsip ke cloud storage. Silakan coba beberapa saat lagi.'],
                        ]);
                    }
                } else {
                    // fallback jika yang di-pass berupa string path (untuk seeder / unit test)
                    $documentUrl = $payload['document_path'];
                }
            }

            // ── Buat Ticket + Kunci Pagu (atomik dalam satu transaksi DB) ────
            $ticket = Ticket::create([
                'user_id'          => $requestor->id,
                'division_id'      => $division->id,
                'title'            => $payload['title'],
                'description'      => $payload['description'] ?? null,
                'budget_estimated' => $payload['budget_estimated'],
                'expenditure_type' => $expenditureType,  // Hasil Gate 2 (otomatis)
                'vendor_name'      => $payload['vendor_name'],
                'document_path'    => $documentUrl,       // Menyimpan public URL atau null
                'status'           => Ticket::STATUS_BUDGET_LOCKED, // Gate 1 lolos
            ]);

            // ── Gate 4: Document Completeness Check ──────────────────────────
            // Jika dokumen belum ada, downgrade status ke 'draft'.
            if (! $ticket->hasDocument()) {
                $ticket->update(['status' => Ticket::STATUS_DRAFT]);
            }

            return $ticket;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GATE 1: BUDGET CHECKING & SMART LOCKING
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Memvalidasi sisa pagu divisi requestor terhadap nilai budget yang diajukan.
     *
     * Menggunakan `lockForUpdate()` (SELECT ... FOR UPDATE) untuk mencegah
     * race condition / double-spending di lingkungan konkuren (multi-user).
     *
     * Jika pagu mencukupi, sisa pagu (`remaining_budget`) langsung dikurangi
     * sebagai bagian dari smart lock mechanism.
     *
     * @throws ValidationException Jika budget_estimated melebihi remaining_budget.
     */
    private function runGate1BudgetCheck(User $requestor, float|string $amount): Division
    {
        $amount = (float) $amount;

        // Lock baris divisi untuk mencegah concurrent write
        $division = Division::where('id', $requestor->division_id)
                            ->lockForUpdate()
                            ->first();

        if (! $division) {
            throw ValidationException::withMessages([
                'division_id' => ['Akun Anda tidak terhubung ke divisi manapun. Hubungi administrator.'],
            ]);
        }

        if (! $division->hasSufficientBudget($amount)) {
            throw ValidationException::withMessages([
                'budget_estimated' => [
                    sprintf(
                        'Nilai pengadaan (Rp %s) melebihi sisa pagu divisi %s (Rp %s).',
                        number_format($amount, 0, ',', '.'),
                        $division->name,
                        number_format($division->remaining_budget, 0, ',', '.')
                    ),
                ],
            ]);
        }

        // Smart Lock: kurangi sisa pagu secara atomik
        $division->decrement('remaining_budget', $amount);

        return $division->fresh(); // Kembalikan data divisi terbaru pasca-lock
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GATE 2: AUTOMATED CAPEX / OPEX CLASSIFICATION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Menentukan klasifikasi CAPEX atau OPEX secara otomatis berdasarkan:
     * 1. Nilai budget (>= CAPEX_THRESHOLD = CAPEX)
     * 2. Keyword pada judul item (matches CAPEX_KEYWORDS = CAPEX)
     * 3. Default: OPEX untuk pengadaan rutin di bawah threshold
     *
     * Hasil ditulis langsung ke database — BUKAN input manual dari user.
     */
    private function runGate2ExpenditureClassification(string $title, float $amount): string
    {
        // Rule 1: Nilai melebihi threshold korporat → CAPEX
        if ($amount >= self::CAPEX_THRESHOLD) {
            return Ticket::EXPENDITURE_CAPEX;
        }

        // Rule 2: Judul mengandung keyword aset tetap/infrastruktur → CAPEX
        $titleLower = strtolower($title);
        foreach (self::CAPEX_KEYWORDS as $keyword) {
            if (str_contains($titleLower, $keyword)) {
                return Ticket::EXPENDITURE_CAPEX;
            }
        }

        // Default: pengadaan operasional rutin → OPEX
        return Ticket::EXPENDITURE_OPEX;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GATE 3: VENDOR & REQUESTER ELIGIBILITY VERIFICATION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Memverifikasi kelayakan requestor dan vendor:
     * - Requestor harus memiliki division_id yang valid (bukan null/admin tanpa divisi).
     * - Vendor harus terdaftar dalam registry mitra aktif BNI.
     *
     * Catatan: Tabel `vendors` akan diimplementasikan pada iterasi berikutnya.
     * Saat ini validasi vendor menggunakan pengecekan string dasar.
     *
     * @throws ValidationException Jika requestor atau vendor tidak lolos eligibility.
     */
    private function runGate3EligibilityCheck(User $requestor, string $vendorName): void
    {
        // Check 1: Requestor harus memiliki assignment divisi yang valid
        if (is_null($requestor->division_id)) {
            throw ValidationException::withMessages([
                'user_id' => ['Requestor tidak memiliki assignment divisi yang valid untuk mengajukan pengadaan.'],
            ]);
        }

        // Check 2: Nama vendor tidak boleh kosong atau hanya whitespace
        if (empty(trim($vendorName))) {
            throw ValidationException::withMessages([
                'vendor_name' => ['Nama vendor tidak valid. Pastikan vendor terdaftar di registry mitra BNI.'],
            ]);
        }

        // TODO (Iterasi Berikutnya): Implementasikan pengecekan ke tabel `vendors`
        // setelah tabel registry vendor dibuat.
        // Contoh:
        // if (! Vendor::where('name', $vendorName)->where('is_active', true)->exists()) {
        //     throw ValidationException::withMessages([
        //         'vendor_name' => ['Vendor tidak ditemukan dalam registry mitra aktif BNI.'],
        //     ]);
        // }
    }
}
