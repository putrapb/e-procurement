<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder — Orkestrator utama untuk seluruh seeder.
 *
 * URUTAN EKSEKUSI WAJIB (berdasarkan dependency foreign key):
 *   1. DivisionSeeder  — tabel 'divisions' harus ada dulu
 *   2. UserSeeder      — membutuhkan 'divisions.id' untuk foreign key
 *
 * Catatan untuk tim: Jangan ubah urutan ini. Pelanggaran urutan akan
 * menyebabkan foreign key constraint violation pada Supabase PostgreSQL.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Jalankan seluruh seeder aplikasi secara berurutan.
     */
    public function run(): void
    {
        $this->command->info('🚀 Memulai proses seeding database E-Procurement BNI...');
        $this->command->newLine();

        $this->call([
            DivisionSeeder::class, // [1] Harus pertama — tidak ada dependency
            UserSeeder::class,     // [2] Membutuhkan divisions
        ]);

        $this->command->newLine();
        $this->command->info('🎉 Seeding selesai! Database siap untuk development & testing.');
    }
}

