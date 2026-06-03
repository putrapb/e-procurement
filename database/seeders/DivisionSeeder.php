<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

/**
 * DivisionSeeder
 *
 * Melakukan seed data divisi korporat BNI dengan pagu anggaran tahunan
 * yang realistis. Data ini dibutuhkan sebelum seeder User dan Ticket
 * dijalankan karena keduanya memiliki foreign key ke tabel divisions.
 *
 * Mendukung Gate 1: setiap divisi memiliki pagu dan sisa pagu yang spesifik.
 */
class DivisionSeeder extends Seeder
{
    /**
     * Jalankan seeder divisi korporat BNI.
     */
    public function run(): void
    {
        $divisions = [
            [
                'name'                => 'Divisi Teknologi Informasi',
                'code'                => 'DIV-IT',
                'yearly_budget_limit' => 15_000_000_000.00, // Rp 15 Miliar (infrastruktur IT besar)
                'remaining_budget'    => 15_000_000_000.00,
            ],
            [
                'name'                => 'Divisi Operasional',
                'code'                => 'DIV-OPS',
                'yearly_budget_limit' => 8_000_000_000.00,  // Rp 8 Miliar
                'remaining_budget'    => 8_000_000_000.00,
            ],
            [
                'name'                => 'Divisi Keuangan & Akuntansi',
                'code'                => 'DIV-FIN',
                'yearly_budget_limit' => 5_000_000_000.00,  // Rp 5 Miliar
                'remaining_budget'    => 5_000_000_000.00,
            ],
            [
                'name'                => 'Divisi Sumber Daya Manusia',
                'code'                => 'DIV-HRD',
                'yearly_budget_limit' => 3_500_000_000.00,  // Rp 3,5 Miliar
                'remaining_budget'    => 3_500_000_000.00,
            ],
            [
                'name'                => 'Divisi Pengadaan & Logistik',
                'code'                => 'DIV-LOG',
                'yearly_budget_limit' => 12_000_000_000.00, // Rp 12 Miliar (pengadaan terbesar)
                'remaining_budget'    => 12_000_000_000.00,
            ],
            [
                'name'                => 'Divisi Kepatuhan & Hukum',
                'code'                => 'DIV-COM',
                'yearly_budget_limit' => 2_000_000_000.00,  // Rp 2 Miliar
                'remaining_budget'    => 2_000_000_000.00,
            ],
            [
                'name'                => 'Divisi Pemasaran & Komunikasi',
                'code'                => 'DIV-MKT',
                'yearly_budget_limit' => 6_000_000_000.00,  // Rp 6 Miliar
                'remaining_budget'    => 6_000_000_000.00,
            ],
            [
                'name'                => 'Divisi Manajemen Risiko',
                'code'                => 'DIV-RSK',
                'yearly_budget_limit' => 1_500_000_000.00,  // Rp 1,5 Miliar
                'remaining_budget'    => 1_500_000_000.00,
            ],
        ];

        foreach ($divisions as $division) {
            // updateOrCreate: aman dijalankan berulang kali (idempotent)
            // tidak akan duplikat jika seeder dijalankan ulang
            Division::updateOrCreate(
                ['code' => $division['code']], // Unique key untuk lookup
                $division
            );
        }

        $this->command->info('✅ DivisionSeeder: ' . count($divisions) . ' divisi BNI berhasil di-seed.');
    }
}
