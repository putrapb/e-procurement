<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Skema tabel utama sistem E-Procurement BNI.
     *
     * Mendukung flow multi-role approval pipeline:
     *   Staff submit → PFA review → Staff validate (4-Gate) →
     *   Head Dept forward → Head Div decide → PFA generate PO
     *
     * Kolom-kolom mendukung 4-Gate Smart Validation Engine:
     *   - Gate 1: budget_estimated + division_id (budget lock saat Head Div approve)
     *   - Gate 2: expenditure_type (auto-classified: CAPEX / OPEX)
     *   - Gate 3: vendor_name + user_id (eligibility verification)
     *   - Gate 4: document_path (Izin Prinsip PDF → S3/local)
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            // ── Relasi Korporat ─────────────────────────────────────────────
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('Requestor — staff yang mengajukan ticket pengadaan');

            $table->foreignId('division_id')
                  ->constrained('divisions')
                  ->cascadeOnDelete()
                  ->comment('Departemen asal requestor; digunakan Gate 1 untuk cross-reference pagu');

            // ── Identitas Pengadaan ─────────────────────────────────────────
            $table->string('title', 255)
                  ->comment('Judul item atau layanan yang diadakan');

            $table->text('description')
                  ->nullable()
                  ->comment('Spesifikasi teknis dan breakdown kebutuhan pengadaan');

            // ── Gate 1: Budget Checking & Smart Locking ─────────────────────
            $table->decimal('budget_estimated', 15, 2)
                  ->comment('Estimasi nilai transaksi (Rupiah); pagu dikunci saat Head Div approve');

            // ── Gate 2: Automated CAPEX / OPEX Classification ───────────────
            $table->enum('expenditure_type', ['CAPEX', 'OPEX'])
                  ->nullable()
                  ->comment('Klasifikasi akuntansi; ditulis otomatis oleh 4-Gate Engine, BUKAN input manual');

            // ── Gate 3: Vendor Eligibility ──────────────────────────────────
            $table->string('vendor_name', 255)
                  ->comment('Nama perusahaan vendor eksternal');

            // ── Gate 4: Document Completeness (Izin Prinsip PDF → S3) ────────
            $table->string('document_path')->nullable()
                  ->comment('URL/path ke file PDF Izin Prinsip; diperlukan untuk review PFA');

            // ── Status Lifecycle (8-Step Multi-Role Pipeline) ────────────────
            $table->enum('status', [
                'pending_review',    // Staff submit → menunggu PFA review dokumen
                'revision',          // PFA tolak dokumen → staff harus revisi & re-upload
                'need_to_validate',  // PFA approve dokumen → staff bisa run 4-Gate
                'pending_dept_head', // 4-Gate lolos → menunggu Head Dept forward
                'pending_div_head',  // Head Dept forward → menunggu Head Div decide
                'declined',          // Head Div tolak pengadaan
                'approved',          // Head Div approve → pagu dikunci di sini
                'po_generated',      // PFA sudah generate PO/Invoice
            ])->default('pending_review')
              ->comment('Status lifecycle ticket dalam alur multi-role approval pipeline');

            // ── Catatan Penolakan ───────────────────────────────────────────
            $table->text('rejection_note')->nullable()
                  ->comment('Alasan penolakan oleh PFA (dokumen) atau Head Div (keputusan)');

            $table->timestamps();

            // ── Indexes untuk Performa Query ────────────────────────────────
            $table->index(['division_id', 'status'], 'idx_tickets_division_status');
            $table->index(['user_id', 'status'], 'idx_tickets_user_status');
            $table->index('status', 'idx_tickets_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
