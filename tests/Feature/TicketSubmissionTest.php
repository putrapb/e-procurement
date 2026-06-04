<?php

use App\Models\Division;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

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
        Storage::fake('s3');
        
        $division = Division::factory()->create([
            'yearly_budget_limit' => 5_000_000_000.00,
            'remaining_budget'    => 5_000_000_000.00,
        ]);
        $officer = User::factory()->forDivision($division)->create();
        $file = UploadedFile::fake()->create('izin-prinsip-test.pdf', 500, 'application/pdf');

        $response = $this->actingAs($officer)
                         ->post(route('tickets.store'), [
                             'title'            => 'Pengadaan Laptop Tim IT',
                             'description'      => 'Laptop untuk kebutuhan developer tim infrastruktur.',
                             'budget_estimated' => 50_000_000.00,
                             'vendor_name'      => 'PT Mitra Teknologi Utama',
                             'document_path'    => $file,
                         ]);

        // Assert: redirect ke halaman detail ticket yang baru dibuat
        $ticket = Ticket::first();
        $response->assertRedirect(route('tickets.show', $ticket));

        // Assert: flash message sukses ada
        $response->assertSessionHas('success');

        // Assert: ticket tersimpan di database
        expect(Ticket::count())->toBe(1);
        expect($ticket->status)->toBe(Ticket::STATUS_BUDGET_LOCKED);
        expect($ticket->document_path)->not->toBeNull();
        Storage::disk('s3')->assertExists('tickets/' . $file->hashName());
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
        Storage::fake('s3');
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();
        $file = UploadedFile::fake()->create('izin-prinsip-test.pdf', 500, 'application/pdf');

        $this->actingAs($officer)
             ->post(route('tickets.store'), [
                 'title'            => 'Pengadaan Sistem Keamanan Gedung',
                 'budget_estimated' => 750_000_000.00, // 750 Juta = CAPEX
                 'vendor_name'      => 'PT Securindo Packatama',
                 'document_path'    => $file,
             ]);

        $ticket = Ticket::first();
        expect($ticket->expenditure_type)->toBe(Ticket::EXPENDITURE_CAPEX);
    });

    test('Gate 2 — ticket OPEX diklasifikasikan otomatis untuk pengadaan rutin di bawah threshold', function () {
        Storage::fake('s3');
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();
        $file = UploadedFile::fake()->create('izin-prinsip-test.pdf', 500, 'application/pdf');

        $this->actingAs($officer)
             ->post(route('tickets.store'), [
                 'title'            => 'Langganan Zoom Premium Bulanan',
                 'budget_estimated' => 3_000_000.00, // 3 Juta = OPEX
                 'vendor_name'      => 'PT Zoom Video Indonesia',
                 'document_path'    => $file,
             ]);

        $ticket = Ticket::first();
        expect($ticket->expenditure_type)->toBe(Ticket::EXPENDITURE_OPEX);
    });

    test('Gate 4 — rollback transaksi database jika upload file ke S3 gagal/down', function () {
        // Simulasi S3 error
        Storage::shouldReceive('disk')->with('s3')->andThrow(new \Exception('S3 Connection Timeout'));

        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();
        $file = UploadedFile::fake()->create('izin-prinsip-test.pdf', 500, 'application/pdf');

        $response = $this->actingAs($officer)
                         ->post(route('tickets.store'), [
                             'title'            => 'Pengadaan Server Baru',
                             'budget_estimated' => 100_000_000.00,
                             'vendor_name'      => 'PT Vendor Server',
                             'document_path'    => $file,
                         ]);

        // Assert: session memiliki error pada document_path
        $response->assertSessionHasErrors(['document_path']);

        // Assert: pagu tidak berkurang (rollback transaksi)
        $division->refresh();
        expect($division->remaining_budget)->toBe($division->yearly_budget_limit);

        // Assert: ticket tidak terbuat di database
        expect(Ticket::count())->toBe(0);
    });

});

// ============================================================================
// AKSES DETAIL TICKET — otorisasi kepemilikan dan divisi (TicketPolicy)
// ============================================================================

describe('Akses Detail Ticket — Otorisasi Kepemilikan dan Divisi', function () {

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

    test('officer tidak dapat mengakses halaman edit ticket milik officer lain', function () {
        $division  = Division::factory()->withFullBudget()->create();
        $ownerUser = User::factory()->forDivision($division)->create();
        $otherUser = User::factory()->forDivision($division)->create();
        $ticket    = Ticket::factory()->forUser($ownerUser)->create();

        $this->actingAs($otherUser)
             ->get(route('tickets.edit', $ticket))
             ->assertForbidden();
    });

    test('officer tidak dapat melakukan update ticket milik officer lain', function () {
        $division  = Division::factory()->withFullBudget()->create();
        $ownerUser = User::factory()->forDivision($division)->create();
        $otherUser = User::factory()->forDivision($division)->create();
        $ticket    = Ticket::factory()->forUser($ownerUser)->create();

        $this->actingAs($otherUser)
             ->put(route('tickets.update', $ticket), [
                 'title'       => 'Judul Baru',
                 'vendor_name' => 'PT Vendor Baru',
             ])
             ->assertForbidden();
    });

    test('officer tidak dapat menghapus ticket milik officer lain', function () {
        $division  = Division::factory()->withFullBudget()->create();
        $ownerUser = User::factory()->forDivision($division)->create();
        $otherUser = User::factory()->forDivision($division)->create();
        $ticket    = Ticket::factory()->forUser($ownerUser)->create();

        $this->actingAs($otherUser)
             ->delete(route('tickets.destroy', $ticket))
             ->assertForbidden();
    });

    test('officer dari divisi berbeda tidak dapat mengakses ticket meskipun user id dimanipulasi', function () {
        $div1  = Division::factory()->withFullBudget()->create();
        $div2  = Division::factory()->withFullBudget()->create();
        $user1 = User::factory()->forDivision($div1)->create();
        $user2 = User::factory()->forDivision($div2)->create();
        
        // Buat tiket dengan user_id user1 tapi division_id div2 (kondisi tidak konsisten / manipulasi)
        $ticket = Ticket::factory()->create([
            'user_id'     => $user1->id,
            'division_id' => $div2->id,
        ]);

        // user1 mencoba mengakses, tapi division_id tidak cocok
        $this->actingAs($user1)
             ->get(route('tickets.show', $ticket))
             ->assertForbidden();
    });

    test('admin (tanpa division_id) dapat melihat, mengedit, mengupdate, dan menghapus tiket mana saja', function () {
        $division = Division::factory()->withFullBudget()->create();
        $officer  = User::factory()->forDivision($division)->create();
        $ticket   = Ticket::factory()->forUser($officer)->create();
        $admin    = User::factory()->create(['division_id' => null]); // Admin

        // Admin view
        $this->actingAs($admin)
             ->get(route('tickets.show', $ticket))
             ->assertOk();

        // Admin edit page
        $this->actingAs($admin)
             ->get(route('tickets.edit', $ticket))
             ->assertOk();

        // Admin update
        $this->actingAs($admin)
             ->put(route('tickets.update', $ticket), [
                 'title'       => 'Diupdate Admin',
                 'vendor_name' => 'PT BNI Utama',
             ])
             ->assertRedirect(route('tickets.show', $ticket));

        // Admin delete
        $this->actingAs($admin)
             ->delete(route('tickets.destroy', $ticket))
             ->assertRedirect(route('tickets.index'));
             
        expect(Ticket::count())->toBe(0);
    });

});

// ============================================================================
// ALUR PERSETUJUAN — Approval, Rejection, dan Budget Refund
// ============================================================================

describe('Alur Persetujuan (Approval Flow) — Admin Actions', function () {

    test('admin berhasil menyetujui tiket berstatus budget_locked', function () {
        $division = Division::factory()->create([
            'yearly_budget_limit' => 1_000_000_000.00,
            'remaining_budget'    => 900_000_000.00, // sudah dipotong 100 juta
        ]);
        $officer = User::factory()->forDivision($division)->create();
        $admin   = User::factory()->create(['division_id' => null]);
        
        $ticket = Ticket::factory()->forUser($officer)->create([
            'division_id'      => $division->id,
            'budget_estimated' => 100_000_000.00,
            'status'           => Ticket::STATUS_BUDGET_LOCKED,
        ]);

        $response = $this->actingAs($admin)
                         ->post(route('tickets.approve', $ticket));

        $response->assertRedirect(route('tickets.show', $ticket))
                 ->assertSessionHas('success');

        $ticket->refresh();
        expect($ticket->status)->toBe(Ticket::STATUS_APPROVED);

        // Budget tetap berkurang, tidak berubah
        $division->refresh();
        expect((float) $division->remaining_budget)->toBe(900_000_000.00);
    });

    test('admin berhasil menolak tiket berstatus budget_locked dan melakukan refund pagu divisi', function () {
        $division = Division::factory()->create([
            'yearly_budget_limit' => 1_000_000_000.00,
            'remaining_budget'    => 900_000_000.00, // sudah dipotong 100 juta
        ]);
        $officer = User::factory()->forDivision($division)->create();
        $admin   = User::factory()->create(['division_id' => null]);
        
        $ticket = Ticket::factory()->forUser($officer)->create([
            'division_id'      => $division->id,
            'budget_estimated' => 100_000_000.00,
            'status'           => Ticket::STATUS_BUDGET_LOCKED,
        ]);

        $response = $this->actingAs($admin)
                         ->post(route('tickets.reject', $ticket));

        $response->assertRedirect(route('tickets.show', $ticket))
                 ->assertSessionHas('success');

        $ticket->refresh();
        expect($ticket->status)->toBe(Ticket::STATUS_REJECTED);

        // Budget dikembalikan (900jt + 100jt = 1M)
        $division->refresh();
        expect((float) $division->remaining_budget)->toBe(1_000_000_000.00);
    });

    test('officer biasa ditolak (403) saat mencoba menyetujui atau menolak tiket', function () {
        $division = Division::factory()->create([
            'yearly_budget_limit' => 1_000_000_000.00,
            'remaining_budget'    => 900_000_000.00,
        ]);
        $officer  = User::factory()->forDivision($division)->create();
        $ticket = Ticket::factory()->forUser($officer)->create([
            'division_id'      => $division->id,
            'budget_estimated' => 100_000_000.00,
            'status'           => Ticket::STATUS_BUDGET_LOCKED,
        ]);

        // Coba approve
        $this->actingAs($officer)
             ->post(route('tickets.approve', $ticket))
             ->assertForbidden();

        // Coba reject
        $this->actingAs($officer)
             ->post(route('tickets.reject', $ticket))
             ->assertForbidden();

        // Status tiket tidak berubah
        $ticket->refresh();
        expect($ticket->status)->toBe(Ticket::STATUS_BUDGET_LOCKED);

        // Budget tidak berubah
        $division->refresh();
        expect((float) $division->remaining_budget)->toBe(900_000_000.00);
    });

});
