<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * State default: user tanpa assignment divisi (cocok untuk admin/super-admin).
     * division_id = null agar tidak memerlukan Division sebelum User dibuat.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'division_id'       => null, // Default: tidak terhubung ke divisi (admin role)
        ];
    }

    // ── States ────────────────────────────────────────────────────────────────

    /**
     * State: Email belum terverifikasi (bawaan Breeze).
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * State: User dikaitkan ke divisi tertentu (sebagai procurement officer).
     * Mendukung Gate 1 (budget check per divisi) dan Gate 3 (eligibility requestor).
     *
     * Penggunaan: User::factory()->forDivision($division)->create()
     */
    public function forDivision(Division $division): static
    {
        return $this->state(fn (array $attributes) => [
            'division_id' => $division->id,
        ]);
    }

    /**
     * State: User sebagai admin korporat (division_id = null).
     * Admin tidak memiliki divisi dan tidak bisa mengajukan ticket pengadaan.
     * Digunakan untuk test Gate 3 FAIL (requestor tanpa divisi).
     */
    public function asAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'division_id' => null,
        ]);
    }
}

