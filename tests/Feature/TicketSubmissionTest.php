<?php

use App\Models\Division;
use App\Models\Ticket;
use App\Models\User;

// ============================================================================
// AUTENTIKASI — akses tanpa login harus ditolak
// ============================================================================

describe('Autentikasi — Proteksi Route Ticket', function () {

    test('guest tidak dapat mengakses halaman daftar ticket', function () {
        $this->get(route('tickets.index'))
             ->assertRedirect(route('login'));
    });

    test('guest tidak dapat mengakses form pengajuan ticket', function () {
        $this->get(route('tickets.create'))
             ->assertRedirect(route('login'));
    });

    test('guest tidak dapat melakukan POST pengajuan ticket', function () {
        $this->post(route('tickets.store'), [])
             ->assertRedirect(route('login'));
    });

});

// ============================================================================
// FORM VALIDATION — validasi sintaksis (StoreTicketRequest)
// ============================================================================

describe('Form Validation — StoreTicketRequest', function () {

    test('form validation menolak request dengan field wajib yang kosong', function () {
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        $this->actingAs($officer)
             ->post(route('tickets.store'), []) // Semua field kosong
             ->assertSessionHasErrors(['title', 'budget_estimated', 'vendor_name']);
    });

    test('form validation menolak budget_estimated yang bukan angka', function () {
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        $this->actingAs($officer)
             ->post(route('tickets.store'), [
                 'title'            => 'Pengadaan Test',
                 'budget_estimated' => 'bukan-angka',
                 'vendor_name'      => 'PT Vendor Test',
             ])
             ->assertSessionHasErrors(['budget_estimated']);
    });

    test('form validation menolak budget_estimated bernilai nol atau negatif', function () {
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        $this->actingAs($officer)
             ->post(route('tickets.store'), [
                 'title'            => 'Pengadaan Test',
                 'budget_estimated' => 0,
                 'vendor_name'      => 'PT Vendor Test',
             ])
             ->assertSessionHasErrors(['budget_estimated']);
    });

    test('form validation menolak title yang melebihi 255 karakter', function () {
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        $this->actingAs($officer)
             ->post(route('tickets.store'), [
                 'title'            => str_repeat('A', 256), // 256 karakter
                 'budget_estimated' => 10_000_000,
                 'vendor_name'      => 'PT Vendor Test',
             ])
             ->assertSessionHasErrors(['title']);
    });

});

// ============================================================================
// ALUR PENGAJUAN TICKET — happy path dan gate failures via HTTP
// ============================================================================

describe('Alur Pengajuan Ticket — HTTP End-to-End', function () {

    test('officer berhasil mengajukan ticket dan diredirect ke halaman detail', function () {
        $division = Division::factory()->create([
            'yearly_budget_limit' => 5_000_000_000.00,
            'remaining_budget'    => 5_000_000_000.00,
        ]);
        $officer = User::factory()->forDivision($division)->create();

        $response = $this->actingAs($officer)
                         ->post(route('tickets.store'), [
                             'title'            => 'Pengadaan Laptop Tim IT',
                             'description'      => 'Laptop untuk kebutuhan developer tim infrastruktur.',
                             'budget_estimated' => 50_000_000.00,
                             'vendor_name'      => 'PT Mitra Teknologi Utama',
                             'document_path'    => 'tickets/izin-prinsip-test.pdf',
                         ]);

        // Assert: redirect ke halaman detail ticket yang baru dibuat
        $ticket = Ticket::first();
        $response->assertRedirect(route('tickets.show', $ticket));

        // Assert: flash message sukses ada
        $response->assertSessionHas('success');

        // Assert: ticket tersimpan di database
        expect(Ticket::count())->toBe(1);
        expect($ticket->status)->toBe(Ticket::STATUS_BUDGET_LOCKED);
    });

    test('Gate 1 — pengajuan ticket diblok dan kembali ke form saat budget melebihi pagu divisi', function () {
        // Arrange: divisi dengan sisa pagu hanya Rp 100.000
        $division = Division::factory()->withLimitedBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        $response = $this->actingAs($officer)
                         ->post(route('tickets.store'), [
                             'title'            => 'Core Infrastructure Mainframe Asset',
                             'budget_estimated' => 950_000_000.00, // 950 Juta >> Rp 100 Ribu
                             'vendor_name'      => 'PT Enterprise Vendor Utama',
                         ]);

        // Assert: session memiliki validation error pada budget_estimated
        $response->assertSessionHasErrors(['budget_estimated']);

        // Assert: tidak ada ticket tersimpan di database
        expect(Ticket::count())->toBe(0);
    });

    test('ticket berstatus draft saat officer mengajukan tanpa melampirkan dokumen Izin Prinsip', function () {
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        $this->actingAs($officer)
             ->post(route('tickets.store'), [
                 'title'            => 'Pemeliharaan AC Kantor',
                 'budget_estimated' => 15_000_000.00,
                 'vendor_name'      => 'PT Sejuk Mandiri',
                 // Tidak ada document_path
             ])
             ->assertSessionHas('success');

        $ticket = Ticket::first();
        expect($ticket->status)->toBe(Ticket::STATUS_DRAFT)
            ->and($ticket->document_path)->toBeNull();
    });

    test('Gate 2 — ticket CAPEX diklasifikasikan otomatis tanpa input user saat nilai >= Rp 500 Juta', function () {
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        $this->actingAs($officer)
             ->post(route('tickets.store'), [
                 'title'            => 'Pengadaan Sistem Keamanan Gedung',
                 'budget_estimated' => 750_000_000.00, // 750 Juta = CAPEX
                 'vendor_name'      => 'PT Securindo Packatama',
                 'document_path'    => 'tickets/izin-prinsip-test.pdf',
             ]);

        $ticket = Ticket::first();
        expect($ticket->expenditure_type)->toBe(Ticket::EXPENDITURE_CAPEX);
    });

    test('Gate 2 — ticket OPEX diklasifikasikan otomatis untuk pengadaan rutin di bawah threshold', function () {
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();

        $this->actingAs($officer)
             ->post(route('tickets.store'), [
                 'title'            => 'Langganan Zoom Premium Bulanan',
                 'budget_estimated' => 3_000_000.00, // 3 Juta = OPEX
                 'vendor_name'      => 'PT Zoom Video Indonesia',
                 'document_path'    => 'tickets/izin-prinsip-test.pdf',
             ]);

        $ticket = Ticket::first();
        expect($ticket->expenditure_type)->toBe(Ticket::EXPENDITURE_OPEX);
    });

});

// ============================================================================
// AKSES DETAIL TICKET — otorisasi kepemilikan
// ============================================================================

describe('Akses Detail Ticket — Otorisasi Kepemilikan', function () {

    test('officer dapat melihat detail ticket miliknya sendiri', function () {
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();
        $ticket   = Ticket::factory()->forUser($officer)->asApproved()->create();

        $this->actingAs($officer)
             ->get(route('tickets.show', $ticket))
             ->assertOk();
    });

    test('officer tidak dapat melihat ticket milik officer lain (403)', function () {
        $division  = Division::factory()->withFullBudget()->create();
        $ownerUser = User::factory()->forDivision($division)->create();
        $otherUser = User::factory()->forDivision($division)->create();
        $ticket    = Ticket::factory()->forUser($ownerUser)->asDraft()->create();

        $this->actingAs($otherUser)
             ->get(route('tickets.show', $ticket))
             ->assertForbidden();
    });

});
