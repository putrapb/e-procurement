<?php

use App\Models\Division;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ProcurementValidationService;
use Illuminate\Validation\ValidationException;

// ============================================================================
// GATE 1: BUDGET CHECKING
// ============================================================================

describe('Gate 1 — Budget Checking', function () {

    test('validasi berhasil saat budget mencukupi', function () {
        // Arrange
        $division = Division::factory()->create([
            'yearly_budget_limit' => 5_000_000_000.00,
            'remaining_budget'    => 5_000_000_000.00,
        ]);
        $staff = User::factory()->forDivision($division)->create(['role' => 'staff']);

        $ticket = Ticket::factory()->forUser($staff)->create([
            'budget_estimated' => 50_000_000.00,
            'status'           => Ticket::STATUS_NEED_TO_VALIDATE,
        ]);

        // Act
        $service = app(ProcurementValidationService::class);
        $updatedTicket = $service->runValidationOnTicket($ticket);

        // Assert: ticket status berubah ke pending_dept_head (lolos validasi)
        expect($updatedTicket->status)->toBe(Ticket::STATUS_PENDING_DEPT_HEAD);
        
        // Pagu divisi tidak berkurang saat validasi (baru dikunci saat approved)
        expect($division->fresh()->remaining_budget)->toBe('5000000000.00');
    });

    test('Gate 1 memblok ticket dan melempar ValidationException saat budget melebihi pagu divisi', function () {
        // Arrange
        $division = Division::factory()->create([
            'yearly_budget_limit' => 1_000_000.00,
            'remaining_budget'    => 100_000.00, // sisa 100 ribu
        ]);
        $staff = User::factory()->forDivision($division)->create(['role' => 'staff']);

        $ticket = Ticket::factory()->forUser($staff)->create([
            'budget_estimated' => 950_000_000.00, // 950 Juta
            'status'           => Ticket::STATUS_NEED_TO_VALIDATE,
        ]);

        // Assert: harus melempar ValidationException
        expect(fn () => app(ProcurementValidationService::class)->runValidationOnTicket($ticket))
            ->toThrow(ValidationException::class);
    });

});

// ============================================================================
// GATE 2: AUTOMATED CAPEX / OPEX CLASSIFICATION
// ============================================================================

describe('Gate 2 — Automated CAPEX / OPEX Classification', function () {

    test('tiket diklasifikasikan sebagai CAPEX secara otomatis jika nilai >= Rp 500 Juta', function () {
        $division = Division::factory()->create([
            'yearly_budget_limit' => 10_000_000_000.00,
            'remaining_budget'    => 10_000_000_000.00,
        ]);
        $staff = User::factory()->forDivision($division)->create(['role' => 'staff']);

        $ticket = Ticket::factory()->forUser($staff)->create([
            'title'            => 'Pengadaan Sistem ERP',
            'budget_estimated' => 500_000_000.00, // threshold CAPEX
            'status'           => Ticket::STATUS_NEED_TO_VALIDATE,
        ]);

        $updatedTicket = app(ProcurementValidationService::class)->runValidationOnTicket($ticket);

        expect($updatedTicket->expenditure_type)->toBe(Ticket::EXPENDITURE_CAPEX);
    });

    test('tiket diklasifikasikan sebagai CAPEX berdasarkan keyword judul meskipun nilainya kecil', function () {
        $division = Division::factory()->create([
            'yearly_budget_limit' => 10_000_000_000.00,
            'remaining_budget'    => 10_000_000_000.00,
        ]);
        $staff = User::factory()->forDivision($division)->create(['role' => 'staff']);

        // Judul mengandung kata "server" -> CAPEX
        $ticket = Ticket::factory()->forUser($staff)->create([
            'title'            => 'Beli Server Development',
            'budget_estimated' => 100_000_000.00, // 100 Juta < 500 Juta
            'status'           => Ticket::STATUS_NEED_TO_VALIDATE,
        ]);

        $updatedTicket = app(ProcurementValidationService::class)->runValidationOnTicket($ticket);

        expect($updatedTicket->expenditure_type)->toBe(Ticket::EXPENDITURE_CAPEX);
    });

    test('tiket diklasifikasikan sebagai OPEX untuk pengadaan operasional rutin di bawah threshold', function () {
        $division = Division::factory()->create([
            'yearly_budget_limit' => 10_000_000_000.00,
            'remaining_budget'    => 10_000_000_000.00,
        ]);
        $staff = User::factory()->forDivision($division)->create(['role' => 'staff']);

        $ticket = Ticket::factory()->forUser($staff)->create([
            'title'            => 'Lisensi Zoom Bulanan',
            'budget_estimated' => 25_000_000.00,
            'status'           => Ticket::STATUS_NEED_TO_VALIDATE,
        ]);

        $updatedTicket = app(ProcurementValidationService::class)->runValidationOnTicket($ticket);

        expect($updatedTicket->expenditure_type)->toBe(Ticket::EXPENDITURE_OPEX);
    });

});

// ============================================================================
// GATE 3: ELIGIBILITY CHECK
// ============================================================================

describe('Gate 3 — Eligibility Check', function () {

    test('Gate 3 memblok ticket jika requestor tidak memiliki assignment divisi', function () {
        $staffWithoutDivision = User::factory()->create([
            'role'        => 'staff',
            'division_id' => null,
        ]);

        $division = Division::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id'          => $staffWithoutDivision->id,
            'division_id'      => $division->id,
            'budget_estimated' => 10_000_000.00,
            'status'           => Ticket::STATUS_NEED_TO_VALIDATE,
        ]);

        expect(fn () => app(ProcurementValidationService::class)->runValidationOnTicket($ticket))
            ->toThrow(ValidationException::class);
    });

});

// ============================================================================
// BUDGET LOCK & REFUND (APPROVED & DECLINED)
// ============================================================================

describe('Budget Locking & Refund On Approval/Decline', function () {

    test('lockBudgetOnApproval berhasil memotong pagu divisi secara atomik', function () {
        $division = Division::factory()->create([
            'yearly_budget_limit' => 5_000_000_000.00,
            'remaining_budget'    => 5_000_000_000.00,
        ]);
        $staff = User::factory()->forDivision($division)->create(['role' => 'staff']);

        $ticket = Ticket::factory()->forUser($staff)->create([
            'division_id'      => $division->id,
            'budget_estimated' => 200_000_000.00,
        ]);

        app(ProcurementValidationService::class)->lockBudgetOnApproval($ticket);

        expect($division->fresh()->remaining_budget)->toBe('4800000000.00'); // 5M - 200jt
    });

    test('refundBudget berhasil mengembalikan pagu divisi', function () {
        $division = Division::factory()->create([
            'yearly_budget_limit' => 5_000_000_000.00,
            'remaining_budget'    => 4_800_000_000.00, // sudah terpotong 200jt
        ]);
        $staff = User::factory()->forDivision($division)->create(['role' => 'staff']);

        $ticket = Ticket::factory()->forUser($staff)->create([
            'division_id'      => $division->id,
            'budget_estimated' => 200_000_000.00,
        ]);

        app(ProcurementValidationService::class)->refundBudget($ticket);

        expect($division->fresh()->remaining_budget)->toBe('5000000000.00'); // kembali ke 5M
    });

});
