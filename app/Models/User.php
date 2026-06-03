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

    /**
     * Kolom yang dapat diisi secara massal.
     * division_id ditambahkan untuk relasi ke tabel divisions (Gate 1 & Gate 3).
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
     * User (officer) berasal dari satu divisi korporat.
     * Nullable: admin/super-admin tidak memiliki divisi.
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Seorang user dapat memiliki banyak ticket pengadaan (sebagai requestor).
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}

