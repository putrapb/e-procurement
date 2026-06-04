<?php

use App\Models\Employee;
use App\Models\Division;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $division = Division::factory()->create();
    $employee = Employee::create([
        'nip'           => 'BNI-2024-TEST',
        'name'          => 'Test User',
        'email'         => 'test@bni.co.id',
        'position'      => 'Staff IT',
        'role'          => 'staff',
        'division_id'   => $division->id,
        'is_registered' => false,
    ]);

    $response = $this->post('/register', [
        'nip'                   => 'BNI-2024-TEST',
        'name'                  => 'Test User',
        'email'                 => 'test@bni.co.id',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    expect($employee->fresh()->is_registered)->toBeTrue();
});
