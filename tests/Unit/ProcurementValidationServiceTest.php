<?php

use App\Models\Division;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ProcurementValidationService;
use Illuminate\Validation\ValidationException;

// ============================================================================
// GATE 1: BUDGET CHECKING & SMART LOCKING
// ============================================================================

describe('Gate 1 — Budget Checking & Smart Locking', function () {

    test('ticket berhasil dibuat dan pagu divisi berkurang saat budget mencukupi', function () {
        // Arrange
        $division = Division::factory()->create([
            'yearly_budget_limit' => 5_000_000_000.00,
            'remaining_budget'    => 5_000_000_000.00,
        ]);
        $officer = User::factory()->forDivision($division)->create();

        $payload = [
            'title'            => 'Pengadaan Laptop Tim IT',
            'budget_estimated' => 50_000_000.00,
            'vendor_name'      => 'PT Mitra Teknologi Utama',
            'document_path'    => 'tickets/izin-prinsip-test.pdf',
        ];

        // Act
        $service = app(ProcurementValidationService::class);
        $ticket  = $service->runValidationPipeline($officer, $payload);

        // Assert: ticket terbuat dengan status budget_locked
        expect($ticket)->toBeInstanceOf(Ticket::class)
            ->and($ticket->status)->toBe(Ticket::STATUS_BUDGET_LOCKED)
            ->and($ticket->user_id)->toBe($officer->id)
            ->and($ticket->division_id)->toBe($division->id);

        // Assert: sisa pagu divisi berkurang sesuai nilai yang diajukan
        expect($division->fresh()->remaining_budget)
            ->toBe('4950000000.00'); // 5 Miliar - 50 Juta
    });

    test('Gate 1 memblok ticket dan melempar ValidationException saat budget melebihi pagu divisi', function () {
        // Arrange: divisi dengan sisa pagu sangat kecil
        $division = Division::factory()->withLimitedBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        $payload = [
            'title'            => 'Core Infrastructure Mainframe Asset',
            'budget_estimated' => 950_000_000.00, // 950 Juta >> sisa pagu 100 Ribu
            'vendor_name'      => 'PT Enterprise Vendor Utama',
            'document_path'    => 'tickets/izin-prinsip-test.pdf',
        ];

        // Assert: harus melempar ValidationException pada field budget_estimated
        expect(fn () => app(ProcurementValidationService::class)->runValidationPipeline($officer, $payload))
            ->toThrow(ValidationException::class);

        // Assert: tidak ada ticket yang tersimpan di database
        expect(Ticket::count())->toBe(0);
    });

    test('Gate 1 memblok ticket saat pagu divisi sudah habis sepenuhnya (remaining = 0)', function () {
        // Arrange: pagu habis total
        $division = Division::factory()->withExhaustedBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        $payload = [
            'title'            => 'Pembelian Pulpen Kantor',
            'budget_estimated' => 1.00, // Bahkan Rp 1 pun harus ditolak
            'vendor_name'      => 'PT Alat Tulis Nusantara',
            'document_path'    => 'tickets/izin-prinsip-test.pdf',
        ];

        expect(fn () => app(ProcurementValidationService::class)->runValidationPipeline($officer, $payload))
            ->toThrow(ValidationException::class);

        expect(Ticket::count())->toBe(0);
    });

    test('pagu divisi tidak berkurang jika ticket gagal di gate manapun (atomik rollback)', function () {
        // Arrange: pagu cukup, tapi requestor tidak punya divisi (Gate 3 akan gagal)
        $division     = Division::factory()->withFullBudget()->create();
        $budgetBefore = $division->remaining_budget;

        // Officer tanpa divisi (asAdmin = division_id null)
        $adminUser = User::factory()->asAdmin()->create();

        $payload = [
            'title'            => 'Pengadaan Apapun',
            'budget_estimated' => 100_000_000.00,
            'vendor_name'      => 'PT Vendor Apapun',
            'document_path'    => 'tickets/izin-prinsip-test.pdf',
        ];

        expect(fn () => app(ProcurementValidationService::class)->runValidationPipeline($adminUser, $payload))
            ->toThrow(ValidationException::class);

        // Assert: pagu tidak berubah karena transaksi di-rollback
        expect($division->fresh()->remaining_budget)->toBe($budgetBefore);
        expect(Ticket::count())->toBe(0);
    });

});

// ============================================================================
// GATE 2: AUTOMATED CAPEX / OPEX CLASSIFICATION
// ============================================================================

describe('Gate 2 — Automated CAPEX / OPEX Classification', function () {

    test('tiket diklasifikasikan sebagai CAPEX secara otomatis jika nilai >= Rp 500 Juta', function () {
        // Arrange
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        $payload = [
            'title'            => 'Pengadaan Sistem ERP Korporat',
            'budget_estimated' => 500_000_000.00, // Tepat di threshold CAPEX
            'vendor_name'      => 'PT SAP Indonesia',
            'document_path'    => 'tickets/izin-prinsip-test.pdf',
        ];

        $ticket = app(ProcurementValidationService::class)->runValidationPipeline($officer, $payload);

        expect($ticket->expenditure_type)->toBe(Ticket::EXPENDITURE_CAPEX);
    });

    test('tiket diklasifikasikan sebagai CAPEX jika nilai melebihi Rp 500 Juta', function () {
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        $payload = [
            'title'            => 'Upgrade Infrastruktur Data Center',
            'budget_estimated' => 2_500_000_000.00, // 2,5 Miliar — jelas CAPEX
            'vendor_name'      => 'PT Data Pusat Nusantara',
            'document_path'    => 'tickets/izin-prinsip-test.pdf',
        ];

        $ticket = app(ProcurementValidationService::class)->runValidationPipeline($officer, $payload);

        expect($ticket->expenditure_type)->toBe(Ticket::EXPENDITURE_CAPEX);
    });

    test('tiket diklasifikasikan sebagai CAPEX berdasarkan keyword judul meskipun nilainya kecil', function () {
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        // Nilai di bawah threshold TAPI judulnya mengandung keyword CAPEX ("server")
        $payload = [
            'title'            => 'Pengadaan Server Development Baru',
            'budget_estimated' => 100_000_000.00, // 100 Juta < threshold, tapi "server" = CAPEX
            'vendor_name'      => 'PT Server Indo',
            'document_path'    => 'tickets/izin-prinsip-test.pdf',
        ];

        $ticket = app(ProcurementValidationService::class)->runValidationPipeline($officer, $payload);

        expect($ticket->expenditure_type)->toBe(Ticket::EXPENDITURE_CAPEX);
    });

    test('tiket diklasifikasikan sebagai OPEX untuk pengadaan operasional rutin di bawah threshold', function () {
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        $payload = [
            'title'            => 'Lisensi Microsoft 365 Tahunan',
            'budget_estimated' => 25_000_000.00, // 25 Juta — OPEX
            'vendor_name'      => 'PT Microsoft Indonesia',
            'document_path'    => 'tickets/izin-prinsip-test.pdf',
        ];

        $ticket = app(ProcurementValidationService::class)->runValidationPipeline($officer, $payload);

        expect($ticket->expenditure_type)->toBe(Ticket::EXPENDITURE_OPEX);
    });

    test('Gate 2 menulis expenditure_type otomatis — tidak bisa dioverride manual oleh user', function () {
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        // Simulasi user mencoba mengirim expenditure_type manual (diabaikan oleh service)
        $payload = [
            'title'            => 'Pembelian Alat Tulis Kantor',
            'budget_estimated' => 5_000_000.00, // 5 Juta = pasti OPEX
            'vendor_name'      => 'PT Sinar Mas Stationery',
            'document_path'    => 'tickets/izin-prinsip-test.pdf',
            'expenditure_type' => 'CAPEX', // ← User mencoba force CAPEX, harus diabaikan
        ];

        $ticket = app(ProcurementValidationService::class)->runValidationPipeline($officer, $payload);

        // Service harus tetap menetapkan OPEX berdasarkan logika Gate 2
        expect($ticket->expenditure_type)->toBe(Ticket::EXPENDITURE_OPEX);
    });

});

// ============================================================================
// GATE 3: VENDOR & REQUESTER ELIGIBILITY
// ============================================================================

describe('Gate 3 — Vendor & Requester Eligibility', function () {

    test('Gate 3 memblok ticket jika requestor tidak memiliki assignment divisi (admin role)', function () {
        $adminUser = User::factory()->asAdmin()->create(); // division_id = null

        $payload = [
            'title'            => 'Pengadaan Apapun',
            'budget_estimated' => 10_000_000.00,
            'vendor_name'      => 'PT Vendor Valid',
            'document_path'    => 'tickets/izin-prinsip-test.pdf',
        ];

        expect(fn () => app(ProcurementValidationService::class)->runValidationPipeline($adminUser, $payload))
            ->toThrow(ValidationException::class);

        expect(Ticket::count())->toBe(0);
    });

});

// ============================================================================
// GATE 4: DOCUMENT COMPLETENESS (IZIN PRINSIP)
// ============================================================================

describe('Gate 4 — Document Completeness (Izin Prinsip)', function () {

    test('ticket tetap di status draft jika document_path tidak disertakan', function () {
        // Arrange
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        $payload = [
            'title'            => 'Pengadaan Laptop Tim IT',
            'budget_estimated' => 50_000_000.00,
            'vendor_name'      => 'PT Mitra Teknologi Utama',
            // 'document_path' tidak ada — sengaja dikosongkan
        ];

        $ticket = app(ProcurementValidationService::class)->runValidationPipeline($officer, $payload);

        // Assert: ticket ada tapi statusnya draft (Gate 4 belum terpenuhi)
        expect($ticket)->toBeInstanceOf(Ticket::class)
            ->and($ticket->status)->toBe(Ticket::STATUS_DRAFT)
            ->and($ticket->document_path)->toBeNull();
    });

    test('ticket masuk budget_locked saat document_path tersedia dan semua gate lolos', function () {
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        $payload = [
            'title'            => 'Pengadaan Switch Jaringan Cisco',
            'budget_estimated' => 200_000_000.00,
            'vendor_name'      => 'PT Cisco Systems Indonesia',
            'document_path'    => 'tickets/izin-prinsip-abc123.pdf', // Gate 4 terpenuhi
        ];

        $ticket = app(ProcurementValidationService::class)->runValidationPipeline($officer, $payload);

        expect($ticket->status)->toBe(Ticket::STATUS_BUDGET_LOCKED)
            ->and($ticket->document_path)->not->toBeNull();
    });

    test('helper hasDocument() mengembalikan false jika document_path null', function () {
        $ticket = Ticket::factory()->asDraft()->make(); // make() tidak simpan ke DB

        expect($ticket->hasDocument())->toBeFalse();
    });

    test('helper hasDocument() mengembalikan true jika document_path tersedia', function () {
        $ticket = Ticket::factory()->asBudgetLocked()->make();

        expect($ticket->hasDocument())->toBeTrue();
    });

});
