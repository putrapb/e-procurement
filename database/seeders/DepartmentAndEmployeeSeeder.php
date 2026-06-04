<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Employee;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk Divisi IT BNI dan karyawan dari simulasi database HR.
 *
 * Struktur organisasi:
 *   Divisi IT (1 pagu tunggal)
 *   ├── Dept. IT Infrastructure Management (ITIFM)
 *   ├── Dept. IT Application Development (ITAD)
 *   ├── Dept. IT Security & Governance (ITSG)
 *   └── Dept. IT Operations & Support (ITOS)
 *
 * Semua departemen berbagi SATU pagu anggaran Divisi IT.
 * Departemen hanya sebagai label organisasi di field `position`.
 */
class DepartmentAndEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1 Divisi IT dengan 1 pagu tunggal ──────────────────────────────
        $divisiIT = Division::updateOrCreate(
            ['code' => 'DIV-IT'],
            [
                'name'                => 'Divisi IT',
                'code'                => 'DIV-IT',
                'yearly_budget_limit' => 14_000_000_000.00,  // 14 Miliar
                'remaining_budget'    => 14_000_000_000.00,
            ]
        );

        // ── Karyawan dummy dari database HR ──────────────────────────────────
        // Semua karyawan berada di bawah 1 Divisi IT (division_id sama).
        // Departemen dibedakan lewat field `position`.
        $employees = [
            // ── Staff (per departemen, tapi 1 divisi) ────────────────────────
            [
                'nip'           => 'BNI-2024-001',
                'name'          => 'Ahmad Rizki Pratama',
                'email'         => 'ahmad.rizki@bni.co.id',
                'position'      => 'Staff IT Infrastructure (ITIFM)',
                'role'          => 'staff',
                'division_id'   => $divisiIT->id,
            ],
            [
                'nip'           => 'BNI-2024-002',
                'name'          => 'Siti Nurhaliza',
                'email'         => 'siti.nurhaliza@bni.co.id',
                'position'      => 'Staff IT App Development (ITAD)',
                'role'          => 'staff',
                'division_id'   => $divisiIT->id,
            ],
            [
                'nip'           => 'BNI-2024-003',
                'name'          => 'Budi Santoso',
                'email'         => 'budi.santoso@bni.co.id',
                'position'      => 'Staff IT Security (ITSG)',
                'role'          => 'staff',
                'division_id'   => $divisiIT->id,
            ],
            [
                'nip'           => 'BNI-2024-004',
                'name'          => 'Dewi Anggraini',
                'email'         => 'dewi.anggraini@bni.co.id',
                'position'      => 'Staff IT Operations (ITOS)',
                'role'          => 'staff',
                'division_id'   => $divisiIT->id,
            ],

            // ── Head Department ───────────────────────────────────────────────
            [
                'nip'           => 'BNI-2024-010',
                'name'          => 'Ir. Hendra Wijaya',
                'email'         => 'hendra.wijaya@bni.co.id',
                'position'      => 'Head Department ITIFM',
                'role'          => 'head_dept',
                'division_id'   => $divisiIT->id,
            ],

            // ── Head Division ────────────────────────────────────────────────
            [
                'nip'           => 'BNI-2024-020',
                'name'          => 'Dr. Ratna Megawati, M.Kom',
                'email'         => 'ratna.megawati@bni.co.id',
                'position'      => 'Head Division IT',
                'role'          => 'head_div',
                'division_id'   => $divisiIT->id,
            ],

            // ── Procurement Fixed Assets (PFA) ───────────────────────────────
            [
                'nip'           => 'BNI-2024-030',
                'name'          => 'Fajar Nugroho',
                'email'         => 'fajar.nugroho@bni.co.id',
                'position'      => 'Procurement Fixed Assets Officer',
                'role'          => 'pfa',
                'division_id'   => $divisiIT->id,
            ],
        ];

        foreach ($employees as $emp) {
            Employee::updateOrCreate(['nip' => $emp['nip']], $emp);
        }
    }
}
