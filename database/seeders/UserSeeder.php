<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * UserSeeder
 *
 * Membuat akun demo untuk keperluan development dan pengujian.
 * Harus dijalankan SETELAH DivisionSeeder karena membutuhkan
 * data divisi yang sudah ada untuk foreign key division_id.
 *
 * Akun yang di-seed:
 * - 1 Admin (tanpa divisi)
 * - 1 Officer per divisi (8 divisi = 8 officer)
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Akun Admin (tanpa divisi) ─────────────────────────────────────
        // Tidak dapat mengajukan ticket — digunakan untuk manajemen sistem
        User::updateOrCreate(
            ['email' => 'admin@bni.co.id'],
            [
                'name'        => 'Admin Sistem E-Procurement',
                'password'    => Hash::make('Admin@BNI2025!'),
                'division_id' => null, // Admin tidak terikat divisi
            ]
        );

        // ── 2. Akun Officer per Divisi ────────────────────────────────────────
        // Setiap divisi mendapat satu akun officer demo untuk pengajuan ticket
        $officerAccounts = [
            [
                'code'     => 'DIV-IT',
                'name'     => 'Budi Santoso',
                'email'    => 'officer.it@bni.co.id',
                'password' => 'Officer@IT2025!',
            ],
            [
                'code'     => 'DIV-OPS',
                'name'     => 'Siti Rahayu',
                'email'    => 'officer.ops@bni.co.id',
                'password' => 'Officer@OPS2025!',
            ],
            [
                'code'     => 'DIV-FIN',
                'name'     => 'Agus Permana',
                'email'    => 'officer.fin@bni.co.id',
                'password' => 'Officer@FIN2025!',
            ],
            [
                'code'     => 'DIV-HRD',
                'name'     => 'Dewi Kusuma',
                'email'    => 'officer.hrd@bni.co.id',
                'password' => 'Officer@HRD2025!',
            ],
            [
                'code'     => 'DIV-LOG',
                'name'     => 'Rizky Pratama',
                'email'    => 'officer.log@bni.co.id',
                'password' => 'Officer@LOG2025!',
            ],
            [
                'code'     => 'DIV-COM',
                'name'     => 'Nadia Putri',
                'email'    => 'officer.com@bni.co.id',
                'password' => 'Officer@COM2025!',
            ],
            [
                'code'     => 'DIV-MKT',
                'name'     => 'Hendra Wijaya',
                'email'    => 'officer.mkt@bni.co.id',
                'password' => 'Officer@MKT2025!',
            ],
            [
                'code'     => 'DIV-RSK',
                'name'     => 'Yuli Astuti',
                'email'    => 'officer.rsk@bni.co.id',
                'password' => 'Officer@RSK2025!',
            ],
        ];

        $officerCount = 0;

        foreach ($officerAccounts as $account) {
            $division = Division::where('code', $account['code'])->first();

            if (! $division) {
                $this->command->warn("⚠️  Divisi {$account['code']} tidak ditemukan. Jalankan DivisionSeeder terlebih dahulu.");
                continue;
            }

            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name'        => $account['name'],
                    'password'    => Hash::make($account['password']),
                    'division_id' => $division->id,
                ]
            );

            $officerCount++;
        }

        $this->command->info("✅ UserSeeder: 1 admin + {$officerCount} officer berhasil di-seed.");
        $this->command->line('   📋 Akun Admin  : admin@bni.co.id | Admin@BNI2025!');
        $this->command->line('   📋 Akun Officer: officer.it@bni.co.id | Officer@IT2025! (dan seterusnya)');
    }
}
