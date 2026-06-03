<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan relasi division_id ke tabel users.
     * - Nullable: memungkinkan akun admin/super-admin tanpa assignment divisi.
     * - Mendukung Gate 1 (budget checking per divisi) dan Gate 3 (eligibility requestor).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('division_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('divisions')
                  ->nullOnDelete()
                  ->comment('Relasi ke divisi korporat; null untuk role admin/super-admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn('division_id');
        });
    }
};
