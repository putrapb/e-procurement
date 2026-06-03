<?php

namespace Database\Factories;

use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Division>
 */
class DivisionFactory extends Factory
{
    /**
     * Nama-nama divisi korporat BNI yang realistis.
     * Digunakan untuk menghasilkan data seed yang representatif.
     */
    private static array $divisionPool = [
        ['name' => 'Divisi Teknologi Informasi',        'code' => 'DIV-IT'],
        ['name' => 'Divisi Operasional',                'code' => 'DIV-OPS'],
        ['name' => 'Divisi Keuangan & Akuntansi',       'code' => 'DIV-FIN'],
        ['name' => 'Divisi Sumber Daya Manusia',        'code' => 'DIV-HRD'],
        ['name' => 'Divisi Pengadaan & Logistik',       'code' => 'DIV-LOG'],
        ['name' => 'Divisi Kepatuhan & Hukum',          'code' => 'DIV-COM'],
        ['name' => 'Divisi Pemasaran & Komunikasi',     'code' => 'DIV-MKT'],
        ['name' => 'Divisi Manajemen Risiko',           'code' => 'DIV-RSK'],
    ];

    /**
     * State default: pagu tahunan Rp 2 Miliar, sisa pagu penuh.
     * Cocok untuk skenario pengajuan ticket yang harus berhasil (Gate 1 pass).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $division = fake()->unique()->randomElement(self::$divisionPool);
        $yearlyLimit = 2_000_000_000.00; // Rp 2 Miliar

        return [
            'name'                 => $division['name'],
            'code'                 => $division['code'],
            'yearly_budget_limit'  => $yearlyLimit,
            'remaining_budget'     => $yearlyLimit, // Sisa pagu = penuh di awal tahun
        ];
    }

    // ── States untuk Skenario Testing ────────────────────────────────────────

    /**
     * State: Pagu sangat besar (Rp 10 Miliar) — sisa penuh.
     * Digunakan untuk test Gate 1 PASS pada nilai pengadaan besar.
     */
    public function withFullBudget(): static
    {
        return $this->state(fn (array $attributes) => [
            'yearly_budget_limit' => 10_000_000_000.00,
            'remaining_budget'    => 10_000_000_000.00,
        ]);
    }

    /**
     * State: Sisa pagu sangat kecil (Rp 100 Ribu).
     * Digunakan untuk test Gate 1 FAIL (budget breach scenario).
     */
    public function withLimitedBudget(): static
    {
        return $this->state(fn (array $attributes) => [
            'yearly_budget_limit' => 2_000_000_000.00,
            'remaining_budget'    => 100_000.00, // Sisa pagu hampir habis
        ]);
    }

    /**
     * State: Sisa pagu nol (Rp 0).
     * Digunakan untuk test Gate 1 FAIL — pagu sudah sepenuhnya habis.
     */
    public function withExhaustedBudget(): static
    {
        return $this->state(fn (array $attributes) => [
            'yearly_budget_limit' => 2_000_000_000.00,
            'remaining_budget'    => 0.00,
        ]);
    }
}
