<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel Purchase Orders (PO) yang di-generate oleh PFA
     * setelah tiket pengadaan di-approve oleh Head Division.
     *
     * Setiap ticket yang approved hanya memiliki satu PO.
     * PFA dapat men-generate PDF PO untuk di-download oleh staff.
     */
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                  ->constrained('tickets')
                  ->cascadeOnDelete()
                  ->comment('Ticket pengadaan yang sudah di-approve');

            $table->string('po_number', 30)->unique()
                  ->comment('Nomor PO otomatis: PO-YYYYMMDD-XXXX');

            $table->foreignId('generated_by')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('PFA yang men-generate PO ini');

            $table->string('pdf_path')->nullable()
                  ->comment('Path ke file PDF PO yang sudah di-generate');

            $table->text('notes')->nullable()
                  ->comment('Catatan tambahan dari PFA pada PO');

            $table->timestamps();

            // Index untuk lookup cepat per ticket
            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
