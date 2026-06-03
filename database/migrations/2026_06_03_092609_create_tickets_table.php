<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Skema tabel utama sistem E-Procurement BNI.
     * Setiap kolom dirancang untuk mendukung mekanisme 4-Gate Smart Validation Engine:
     *   - Gate 1: budget_estimated + status (budget_locked) + division_id
     *   - Gate 2: expenditure_type (auto-classified: CAPEX / OPEX)
     *   - Gate 3: vendor_name + user_id (eligibility verification)
     *   - Gate 4: document_path (Izin Prinsip PDF → Supabase S3)
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            // ── Relasi Korporat ─────────────────────────────────────────────
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('Requestor — officer yang mengajukan ticket pengadaan');

            $table->foreignId('division_id')
                  ->constrained('divisions')
                  ->cascadeOnDelete()
                  ->comment('Divisi asal requestor; digunakan Gate 1 untuk cross-reference pagu');

            // ── Identitas Pengadaan ─────────────────────────────────────────
            $table->string('title', 255)
                  ->comment('Judul item atau layanan yang diadakan');

            $table->text('description')
                  ->nullable()
                  ->comment('Spesifikasi teknis dan breakdown kebutuhan pengadaan');

            // ── Gate 1: Budget Checking & Smart Locking ─────────────────────
            $table->decimal('budget_estimated', 15, 2)
                  ->comment('Estimasi nilai transaksi (Rupiah); divalidasi terhadap remaining_budget divisi');

            // ── Gate 2: Automated CAPEX / OPEX Classification ───────────────
            $table->enum('expenditure_type', ['CAPEX', 'OPEX'])
                  ->nullable()
                  ->comment('Klasifikasi akuntansi; ditulis otomatis oleh ProcurementValidationService, BUKAN input manual');

            // ── Gate 3: Vendor Eligibility ──────────────────────────────────
            $table->string('vendor_name', 255)
                  ->comment('Nama perusahaan vendor eksternal; diverifikasi terhadap registry mitra aktif BNI');

            // ── Gate 4: Document Completeness (Izin Prinsip PDF → S3) ────────
            $table->string('document_path')->nullable()
                  ->comment('URL publik S3 Supabase ke file PDF Izin Prinsip; null = ticket tetap dalam status draft');

            // ── Status Lifecycle ────────────────────────────────────────────
            $table->enum('status', [
                'draft',              // Default: dokumen belum lengkap / Gate 4 belum dipenuhi
                'pending_validation', // Diajukan, menunggu proses validasi 4-Gate
                'budget_locked',      // Gate 1 lolos: pagu divisi telah dikunci secara atomik
                'approved',           // Seluruh 4-Gate lolos: ticket disetujui
                'rejected',           // Salah satu gate gagal: ticket ditolak
            ])->default('draft')
              ->comment('Status lifecycle ticket dalam alur 4-Gate Smart Validation Engine');

            $table->timestamps();

            // ── Indexes untuk Performa Query ────────────────────────────────
            // Komposit: budget validation query selalu filter per divisi + status
            $table->index(['division_id', 'status'], 'idx_tickets_division_status');
            // Komposit: dashboard requestor selalu filter per user + status
            $table->index(['user_id', 'status'], 'idx_tickets_user_status');
            // Single: filter global per status (admin view)
            $table->index('status', 'idx_tickets_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
