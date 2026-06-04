<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Employee — Model referensi karyawan dari simulasi database HR BNI.
 *
 * Tabel ini BUKAN tabel user aplikasi, melainkan tabel referensi
 * yang digunakan saat proses sign-up untuk memvalidasi bahwa calon user
 * benar-benar terdaftar sebagai karyawan di bawah Divisi IT BNI.
 */
class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'nip',
        'name',
        'email',
        'position',
        'role',
        'division_id',
        'is_registered',
    ];

    protected function casts(): array
    {
        return [
            'is_registered' => 'boolean',
        ];
    }

    // ── Relasi ───────────────────────────────────────────────────────────────

    /**
     * Karyawan ini berada di satu departemen.
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Karyawan ini mungkin sudah memiliki akun user di aplikasi.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
