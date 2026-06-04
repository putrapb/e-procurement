<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel referensi karyawan korporat BNI (simulasi database HR).
     *
     * Digunakan saat proses Sign-Up untuk memvalidasi bahwa user
     * benar-benar terdaftar sebagai karyawan ITIFM (IT Infrastructure Management).
     * Role dan division assignment otomatis diambil dari tabel ini.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // ── Identitas Karyawan ───────────────────────────────────────────
            $table->string('nip', 20)->unique()
                  ->comment('Nomor Induk Pegawai — primary identifier dari sistem HR');
            $table->string('name')
                  ->comment('Nama lengkap karyawan sesuai data HR');
            $table->string('email')->unique()
                  ->comment('Email korporat BNI (@bni.co.id)');

            // ── Jabatan & Role ───────────────────────────────────────────────
            $table->string('position')
                  ->comment('Jabatan resmi: Staff IT Infra, Head Department ITIFM, dll');
            $table->enum('role', ['staff', 'head_dept', 'head_div', 'pfa'])
                  ->comment('Role sistem: staff, head_dept, head_div, pfa');

            // ── Relasi Divisi ────────────────────────────────────────────────
            $table->foreignId('division_id')
                  ->constrained('divisions')
                  ->cascadeOnDelete()
                  ->comment('Departemen karyawan di bawah Divisi IT');

            // ── Status Registrasi ────────────────────────────────────────────
            $table->boolean('is_registered')->default(false)
                  ->comment('Apakah karyawan ini sudah membuat akun di aplikasi E-Procurement');

            $table->timestamps();

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index('role');
            $table->index('is_registered');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
