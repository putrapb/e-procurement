<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder — Orkestrator utama untuk seluruh seeder.
 *
 * URUTAN EKSEKUSI WAJIB (berdasarkan dependency foreign key):
 *   1. DepartmentAndEmployeeSeeder — tabel 'divisions' + 'employees' harus ada dulu
 *
 * Catatan: DivisionSeeder dan UserSeeder lama sudah diganti oleh
 * DepartmentAndEmployeeSeeder yang mencakup 4 departemen IT BNI
 * dan data karyawan dari simulasi database HR.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Memulai proses seeding database E-Procurement BNI...');
        $this->command->newLine();

        $this->call([
            DepartmentAndEmployeeSeeder::class, // Departemen IT + Karyawan HR
        ]);

        $this->command->newLine();
        $this->command->info('🎉 Seeding selesai! Database siap untuk development & testing.');
    }
}
