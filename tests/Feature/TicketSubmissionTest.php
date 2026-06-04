<?php

use App\Models\Division;
use App\Models\Employee;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

// ============================================================================
// GUEST PROTECTION
// ============================================================================

describe('Authentication — Route Protection', function () {

    test('guest tidak dapat mengakses halaman daftar ticket', function () {
        $this->get(route('tickets.index'))->assertRedirect(route('login'));
    });

    test('guest tidak dapat mengakses form pengajuan ticket', function () {
        $this->get(route('tickets.create'))->assertRedirect(route('login'));
    });

    test('guest tidak dapat melakukan POST pengajuan ticket', function () {
        $this->post(route('tickets.store'), [])->assertRedirect(route('login'));
    });

});

// ============================================================================
// REGISTRATION WITH NIP & HR DATABASE MATCHING
// ============================================================================

describe('Registration — NIP HR Matching', function () {

    test('karyawan berhasil registrasi jika NIP dan email terdaftar di HR', function () {
        $division = Division::factory()->create(['code' => 'DIV-IT']);
        $employee = Employee::create([
            'nip'           => 'BNI-2024-999',
            'name'          => 'John Doe',
            'email'         => 'john.doe@bni.co.id',
            'position'      => 'Staff IT',
            'role'          => 'staff',
            'division_id'   => $division->id,
            'is_registered' => false,
        ]);

        $response = $this->post(route('register'), [
            'nip'                   => 'BNI-2024-999',
            'name'                  => 'John Doe',
            'email'                 => 'john.doe@bni.co.id',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@bni.co.id',
            'role'  => 'staff',
        ]);
        expect($employee->fresh()->is_registered)->toBeTrue();
    });

    test('registrasi ditolak jika NIP dan email tidak terdaftar di HR', function () {
        $response = $this->post(route('register'), [
            'nip'                   => 'BNI-INVALID',
            'name'                  => 'Unknown Karyawan',
            'email'                 => 'unknown@bni.co.id',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['nip']);
        $this->assertDatabaseMissing('users', [
            'email' => 'unknown@bni.co.id',
        ]);
    });

});

// ============================================================================
// STORE VALIDATION
// ============================================================================

describe('Form Validation — Ticket Creation', function () {

    test('form validation menolak field wajib yang kosong', function () {
        $division = Division::factory()->create();
        $staff    = User::factory()->forDivision($division)->create(['role' => 'staff']);

        $this->actingAs($staff)
             ->post(route('tickets.store'), [])
             ->assertSessionHasErrors(['title', 'budget_estimated', 'vendor_name', 'document_path']);
    });

});

// ============================================================================
// E2E PIPELINE FLOW
// ============================================================================

describe('End-to-End Pipeline Approval Flow', function () {

    test('alur lengkap persetujuan multi-role dari submission hingga PO terbuat', function () {
        Storage::fake('public');

        // 1. Setup Divisi dan Pengguna per Role
        $division = Division::factory()->create([
            'yearly_budget_limit' => 10_000_000_000.00,
            'remaining_budget'    => 10_000_000_000.00,
        ]);
        $staff    = User::factory()->forDivision($division)->create(['role' => 'staff']);
        $pfa      = User::factory()->forDivision($division)->create(['role' => 'pfa']);
        $headDept = User::factory()->forDivision($division)->create(['role' => 'head_dept']);
        $headDiv  = User::factory()->forDivision($division)->create(['role' => 'head_div']);

        $file = UploadedFile::fake()->create('izin-prinsip.pdf', 500, 'application/pdf');

        // ─────────────────────────────────────────────────────────────
        // LANGKAH 1: Staff membuat tiket baru (status = pending_review)
        // ─────────────────────────────────────────────────────────────
        $response = $this->actingAs($staff)
                         ->post(route('tickets.store'), [
                             'title'            => 'Server HPE ProLiant IT',
                             'description'      => 'Server untuk data center utama IT.',
                             'budget_estimated' => 600_000_000.00, // Capex threshold
                             'vendor_name'      => 'PT HPE Indonesia',
                             'document_path'    => $file,
                         ]);

        $ticket = Ticket::first();
        expect($ticket)->not->toBeNull();
        $response->assertRedirect(route('tickets.show', $ticket));
        expect($ticket->status)->toBe(Ticket::STATUS_PENDING_REVIEW);

        // ─────────────────────────────────────────────────────────────
        // LANGKAH 2: PFA menyetujui dokumen (status = need_to_validate)
        // ─────────────────────────────────────────────────────────────
        $response = $this->actingAs($pfa)
                         ->post(route('tickets.review-approve', $ticket));

        $response->assertRedirect(route('tickets.show', $ticket));
        expect($ticket->fresh()->status)->toBe(Ticket::STATUS_NEED_TO_VALIDATE);

        // ─────────────────────────────────────────────────────────────
        // LANGKAH 3: Staff menjalankan 4-Gate Validation (status = pending_dept_head)
        // ─────────────────────────────────────────────────────────────
        $response = $this->actingAs($staff)
                         ->post(route('tickets.validate', $ticket));

        $response->assertRedirect(route('tickets.show', $ticket));
        $ticket = $ticket->fresh();
        expect($ticket->status)->toBe(Ticket::STATUS_PENDING_DEPT_HEAD);
        expect($ticket->expenditure_type)->toBe(Ticket::EXPENDITURE_CAPEX); // Auto classified!

        // ─────────────────────────────────────────────────────────────
        // LANGKAH 4: Head Dept meneruskan tiket (status = pending_div_head)
        // ─────────────────────────────────────────────────────────────
        $response = $this->actingAs($headDept)
                         ->post(route('tickets.forward', $ticket));

        $response->assertRedirect(route('tickets.show', $ticket));
        expect($ticket->fresh()->status)->toBe(Ticket::STATUS_PENDING_DIV_HEAD);

        // ─────────────────────────────────────────────────────────────
        // LANGKAH 5: Head Div menyetujui tiket (status = approved, pagu dikunci)
        // ─────────────────────────────────────────────────────────────
        $response = $this->actingAs($headDiv)
                         ->post(route('tickets.approve', $ticket));

        $response->assertRedirect(route('tickets.show', $ticket));
        expect($ticket->fresh()->status)->toBe(Ticket::STATUS_APPROVED);
        // Sisa pagu IT terpotong 600 juta
        expect($division->fresh()->remaining_budget)->toBe('9400000000.00');

        // ─────────────────────────────────────────────────────────────
        // LANGKAH 6: PFA generate PO (status = po_generated & PDF dibikin)
        // ─────────────────────────────────────────────────────────────
        $response = $this->actingAs($pfa)
                         ->post(route('tickets.generate-po', $ticket), [
                             'notes' => 'Catatan PO Server IT',
                         ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $ticket = $ticket->fresh();
        expect($ticket->status)->toBe(Ticket::STATUS_PO_GENERATED);
        expect($ticket->purchaseOrder)->not->toBeNull();
        expect($ticket->purchaseOrder->pdf_path)->not->toBeNull();
    });

});

// ============================================================================
// POLICY RESTRICTIONS
// ============================================================================

describe('Policy & Middleware Otorisasi Akses', function () {

    test('Staff tidak dapat menyetujui dokumen PFA (403)', function () {
        $division = Division::factory()->create();
        $staff    = User::factory()->forDivision($division)->create(['role' => 'staff']);
        $ticket   = Ticket::factory()->forUser($staff)->asPendingReview()->create();

        $this->actingAs($staff)
             ->post(route('tickets.review-approve', $ticket))
             ->assertForbidden();
    });

    test('Staff tidak dapat mem-forward tiket Head Dept (403)', function () {
        $division = Division::factory()->create();
        $staff    = User::factory()->forDivision($division)->create(['role' => 'staff']);
        $ticket   = Ticket::factory()->forUser($staff)->asPendingDeptHead()->create();

        $this->actingAs($staff)
             ->post(route('tickets.forward', $ticket))
             ->assertForbidden();
    });

    test('Head Dept tidak dapat meng-approve keputusan Head Div (403)', function () {
        $division = Division::factory()->create();
        $staff    = User::factory()->forDivision($division)->create(['role' => 'staff']);
        $headDept = User::factory()->forDivision($division)->create(['role' => 'head_dept']);
        $ticket   = Ticket::factory()->forUser($staff)->asPendingDivHead()->create();

        $this->actingAs($headDept)
             ->post(route('tickets.approve', $ticket))
             ->assertForbidden();
    });

    test('Staff tidak dapat mengakses detail tiket milik staff lain', function () {
        $division = Division::factory()->create();
        $staff1   = User::factory()->forDivision($division)->create(['role' => 'staff']);
        $staff2   = User::factory()->forDivision($division)->create(['role' => 'staff']);
        $ticket   = Ticket::factory()->forUser($staff1)->create();

        $this->actingAs($staff2)
             ->get(route('tickets.show', $ticket))
             ->assertForbidden();
    });

    test('PFA/Head Dept/Head Div dapat melihat detail tiket milik siapa saja', function () {
        $division = Division::factory()->create();
        $staff    = User::factory()->forDivision($division)->create(['role' => 'staff']);
        $pfa      = User::factory()->forDivision($division)->create(['role' => 'pfa']);
        $ticket   = Ticket::factory()->forUser($staff)->create();

        $this->actingAs($pfa)
             ->get(route('tickets.show', $ticket))
             ->assertOk();
    });

});
