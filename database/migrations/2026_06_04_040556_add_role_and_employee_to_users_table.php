<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom role, employee_id, dan position ke tabel users.
     *
     * Role menentukan akses dan aksi yang bisa dilakukan user di sistem:
     * - staff:     Membuat & mengelola ticket pengadaan
     * - head_dept: Monitoring & meneruskan ticket ke Head Division
     * - head_div:  Decision maker (approve/decline ticket)
     * - pfa:       Review dokumen & generate PO (Procurement Fixed Assets)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['staff', 'head_dept', 'head_div', 'pfa'])
                  ->default('staff')
                  ->after('email')
                  ->comment('Role user dalam sistem E-Procurement');

            $table->foreignId('employee_id')
                  ->nullable()
                  ->after('role')
                  ->constrained('employees')
                  ->nullOnDelete()
                  ->comment('Referensi ke data karyawan di tabel HR (employees)');

            $table->string('position')
                  ->nullable()
                  ->after('employee_id')
                  ->comment('Jabatan resmi karyawan, diambil dari data HR saat registrasi');

            // Index untuk query filtering per role
            $table->index('role', 'idx_users_role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
            $table->dropForeign(['employee_id']);
            $table->dropColumn(['role', 'employee_id', 'position']);
        });
    }
};
