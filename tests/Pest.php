<?php

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

// Feature tests: full HTTP stack + database refresh
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Unit tests: juga butuh RefreshDatabase karena menggunakan Eloquent factories
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Custom Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Global Helper Functions — Kurangi boilerplate di setiap test file
|--------------------------------------------------------------------------
*/

/**
 * Buat officer yang sudah terhubung ke divisi tertentu.
 * Shortcut untuk: Division::factory()->create() + User::factory()->forDivision()->create()
 */
function createOfficer(?Division $division = null): User
{
    $division ??= Division::factory()->withFullBudget()->create();
    return User::factory()->forDivision($division)->create();
}

/**
 * Buat divisi dengan sisa pagu yang ditentukan secara eksplisit.
 * Berguna untuk skenario test boundary condition Gate 1.
 */
function createDivisionWithBudget(float $remaining): Division
{
    return Division::factory()->create([
        'yearly_budget_limit' => 10_000_000_000.00,
        'remaining_budget'    => $remaining,
    ]);
}

