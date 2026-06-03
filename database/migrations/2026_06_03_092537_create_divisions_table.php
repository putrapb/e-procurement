<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel ini mendukung Gate 1: Budget Checking & Smart Locking.
     * Setiap divisi memiliki pagu tahunan (yearly_budget_limit) dan sisa pagu
     * (remaining_budget) yang akan dikunci secara atomik saat ticket diajukan.
     */
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();

            // Identitas divisi
            $table->string('name', 100)->unique()->comment('Nama resmi divisi korporat BNI');
            $table->string('code', 20)->unique()->comment('Kode divisi singkat, misal: DIV-IT, DIV-OPS');

            // Gate 1: Pagu Anggaran Divisi (dalam Rupiah, presisi korporat)
            $table->decimal('yearly_budget_limit', 15, 2)->default(0.00)
                  ->comment('Total pagu anggaran tahunan yang dialokasikan untuk divisi ini');
            $table->decimal('remaining_budget', 15, 2)->default(0.00)
                  ->comment('Sisa pagu anggaran yang tersedia; dikunci atomik saat ticket lolos Gate 1');

            $table->timestamps();

            // Index untuk performa query pada saat validasi budget di Gate 1
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('divisions');
    }
};
