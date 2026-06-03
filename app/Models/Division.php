<?php

namespace App\Models;

use Database\Factories\DivisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    /** @use HasFactory<DivisionFactory> */
    use HasFactory;

    /**
     * Kolom yang dapat diisi secara massal.
     */
    protected $fillable = [
        'name',
        'code',
        'yearly_budget_limit',
        'remaining_budget',
    ];

    /**
     * Cast tipe data kolom agar presisi desimal terjaga.
     */
    protected function casts(): array
    {
        return [
            'yearly_budget_limit' => 'decimal:2',
            'remaining_budget'    => 'decimal:2',
        ];
    }

    // ── Relasi ──────────────────────────────────────────────────────────────

    /**
     * Satu divisi memiliki banyak user (officer).
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Satu divisi memiliki banyak ticket pengadaan.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    // ── Helper Gate 1 ────────────────────────────────────────────────────────

    /**
     * Periksa apakah sisa pagu divisi mencukupi untuk nominal yang diminta.
     * Digunakan oleh ProcurementValidationService pada Gate 1.
     */
    public function hasSufficientBudget(float $amount): bool
    {
        return $this->remaining_budget >= $amount;
    }
}
