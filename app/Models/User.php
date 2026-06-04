<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // ── Role Constants ──────────────────────────────────────────────────────
    const ROLE_STAFF     = 'staff';
    const ROLE_HEAD_DEPT = 'head_dept';
    const ROLE_HEAD_DIV  = 'head_div';
    const ROLE_PFA       = 'pfa';

    /**
     * Kolom yang dapat diisi secara massal.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'employee_id',
        'position',
        'division_id',
    ];

    /**
     * Kolom yang disembunyikan saat serialisasi ke JSON.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Cast tipe data kolom.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ── Relasi ───────────────────────────────────────────────────────────────

    /**
     * User berasal dari satu departemen korporat.
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * User direferensikan dari tabel HR (employees).
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Seorang staff dapat memiliki banyak ticket pengadaan (sebagai requestor).
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    // ── Role Helpers ─────────────────────────────────────────────────────────

    /**
     * Apakah user ini adalah Staff (pembuat ticket)?
     */
    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    /**
     * Apakah user ini adalah Head Department (monitor & forward)?
     */
    public function isHeadDept(): bool
    {
        return $this->role === self::ROLE_HEAD_DEPT;
    }

    /**
     * Apakah user ini adalah Head Division (decision maker)?
     */
    public function isHeadDiv(): bool
    {
        return $this->role === self::ROLE_HEAD_DIV;
    }

    /**
     * Apakah user ini adalah PFA (Procurement Fixed Assets)?
     */
    public function isPfa(): bool
    {
        return $this->role === self::ROLE_PFA;
    }

    /**
     * Apakah user memiliki salah satu role yang diberikan?
     */
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles);
    }
}
