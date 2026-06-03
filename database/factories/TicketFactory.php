<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Contoh judul pengadaan korporat yang realistis untuk data seed.
     */
    private static array $titlePool = [
        'Pengadaan Laptop Dell Latitude untuk Tim IT',
        'Lisensi Microsoft 365 Business Premium Tahunan',
        'Pemeliharaan Jaringan Fiber Optik Gedung A',
        'Pengadaan Server HPE ProLiant DL380 Gen10',
        'Langganan Layanan Cloud AWS EC2 Instance',
        'Pengadaan Meja dan Kursi Ergonomis Kantor',
        'Jasa Konsultansi Keamanan Siber (Penetration Test)',
        'Pengadaan UPS APC Smart-UPS 3000VA Data Center',
        'Lisensi Antivirus Kaspersky Endpoint Security',
        'Pemasangan CCTV IP Camera Gedung Operasional',
    ];

    /**
     * State default: ticket dalam status draft, belum ada dokumen, nilai OPEX.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'division_id'      => Division::factory(),
            'title'            => fake()->randomElement(self::$titlePool),
            'description'      => fake()->paragraph(3),
            'budget_estimated' => fake()->randomFloat(2, 1_000_000, 100_000_000), // 1 Juta - 100 Juta
            'expenditure_type' => Ticket::EXPENDITURE_OPEX, // Default OPEX
            'vendor_name'      => fake()->company() . ' Indonesia',
            'document_path'    => null, // Default: belum ada dokumen (status draft)
            'status'           => Ticket::STATUS_DRAFT,
        ];
    }

    // ── States Berdasarkan Status Lifecycle ───────────────────────────────────

    /**
     * State: Ticket dalam status draft (belum ada dokumen Izin Prinsip).
     * Gate 4 belum dipenuhi.
     */
    public function asDraft(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_path' => null,
            'status'        => Ticket::STATUS_DRAFT,
        ]);
    }

    /**
     * State: Ticket sudah memiliki dokumen dan menunggu validasi 4-Gate.
     */
    public function asPendingValidation(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_path' => 'tickets/izin-prinsip-' . fake()->uuid() . '.pdf',
            'status'        => Ticket::STATUS_PENDING_VALIDATION,
        ]);
    }

    /**
     * State: Gate 1 lolos — pagu divisi sudah dikunci.
     */
    public function asBudgetLocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_path' => 'tickets/izin-prinsip-' . fake()->uuid() . '.pdf',
            'status'        => Ticket::STATUS_BUDGET_LOCKED,
        ]);
    }

    /**
     * State: Seluruh 4-Gate lolos — ticket disetujui.
     */
    public function asApproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_path' => 'tickets/izin-prinsip-' . fake()->uuid() . '.pdf',
            'status'        => Ticket::STATUS_APPROVED,
        ]);
    }

    /**
     * State: Ticket ditolak (salah satu gate gagal).
     */
    public function asRejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Ticket::STATUS_REJECTED,
        ]);
    }

    // ── States Berdasarkan Expenditure Type (Gate 2) ─────────────────────────

    /**
     * State: Ticket dengan nilai CAPEX (aset tetap / infrastruktur besar).
     * Nilai budget di atas CAPEX_THRESHOLD (Rp 500 Juta).
     */
    public function asCapex(): static
    {
        return $this->state(fn (array $attributes) => [
            'budget_estimated' => fake()->randomFloat(2, 500_000_000, 5_000_000_000),
            'expenditure_type' => Ticket::EXPENDITURE_CAPEX,
        ]);
    }

    /**
     * State: Ticket dengan nilai OPEX (pengeluaran operasional rutin).
     * Nilai budget di bawah CAPEX_THRESHOLD (Rp 500 Juta).
     */
    public function asOpex(): static
    {
        return $this->state(fn (array $attributes) => [
            'budget_estimated' => fake()->randomFloat(2, 1_000_000, 499_999_999),
            'expenditure_type' => Ticket::EXPENDITURE_OPEX,
        ]);
    }

    // ── State Utilitas ────────────────────────────────────────────────────────

    /**
     * State: Kaitkan ticket ke user dan divisi yang sudah ada (bukan buat baru).
     * Digunakan ketika ingin membuat beberapa ticket untuk user yang sama.
     *
     * Penggunaan: Ticket::factory()->forUser($user)->create()
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id'     => $user->id,
            'division_id' => $user->division_id,
        ]);
    }
}
