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
     * State default: ticket dalam status pending_review, dokumen terunggah, nilai OPEX.
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
            'document_path'    => 'tickets/izin-prinsip-' . fake()->uuid() . '.pdf',
            'status'           => Ticket::STATUS_PENDING_REVIEW,
        ];
    }

    // ── States Berdasarkan Status Lifecycle ───────────────────────────────────

    /**
     * State: Ticket tanpa melampirkan dokumen Izin Prinsip.
     */
    public function withoutDocument(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_path' => null,
        ]);
    }

    /**
     * State: Ticket dalam status pending_review.
     */
    public function asPendingReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Ticket::STATUS_PENDING_REVIEW,
        ]);
    }

    /**
     * State: Ticket dalam status revision.
     */
    public function asRevision(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Ticket::STATUS_REVISION,
        ]);
    }

    /**
     * State: Ticket dalam status need_to_validate.
     */
    public function asNeedToValidate(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Ticket::STATUS_NEED_TO_VALIDATE,
        ]);
    }

    /**
     * State: Ticket dalam status pending_dept_head.
     */
    public function asPendingDeptHead(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Ticket::STATUS_PENDING_DEPT_HEAD,
        ]);
    }

    /**
     * State: Ticket dalam status pending_div_head.
     */
    public function asPendingDivHead(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Ticket::STATUS_PENDING_DIV_HEAD,
        ]);
    }

    /**
     * State: Ticket disetujui (status approved).
     */
    public function asApproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Ticket::STATUS_APPROVED,
        ]);
    }

    /**
     * State: Ticket ditolak (status declined).
     */
    public function asDeclined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Ticket::STATUS_DECLINED,
        ]);
    }

    /**
     * State: Ticket PO dihasilkan (status po_generated).
     */
    public function asPoGenerated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Ticket::STATUS_PO_GENERATED,
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
