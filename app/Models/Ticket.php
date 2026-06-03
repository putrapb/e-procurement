<?php

namespace App\Models;

use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    // ── Status Lifecycle Constants ────────────────────────────────────────────
    // Digunakan di seluruh codebase agar tidak ada "magic string" yang tersebar.

    /** Dokumen belum lengkap / Gate 4 belum dipenuhi. Default awal ticket. */
    const STATUS_DRAFT              = 'draft';

    /** Ticket diajukan, antri proses validasi 4-Gate Engine. */
    const STATUS_PENDING_VALIDATION = 'pending_validation';

    /** Gate 1 lolos: pagu divisi telah dikunci secara atomik. */
    const STATUS_BUDGET_LOCKED      = 'budget_locked';

    /** Seluruh 4-Gate lolos: ticket disetujui untuk proses pengadaan. */
    const STATUS_APPROVED           = 'approved';

    /** Salah satu gate gagal: ticket ditolak dan pagu dikembalikan (jika ada). */
    const STATUS_REJECTED           = 'rejected';

    // ── Expenditure Type Constants (Gate 2) ───────────────────────────────────
    const EXPENDITURE_CAPEX = 'CAPEX';
    const EXPENDITURE_OPEX  = 'OPEX';

    /**
     * Kolom yang dapat diisi secara massal.
     */
    protected $fillable = [
        'user_id',
        'division_id',
        'title',
        'description',
        'budget_estimated',
        'expenditure_type',
        'vendor_name',
        'document_path',
        'status',
    ];

    /**
     * Cast tipe data kolom.
     */
    protected function casts(): array
    {
        return [
            'budget_estimated' => 'decimal:2',
        ];
    }

    // ── Relasi ───────────────────────────────────────────────────────────────

    /**
     * Ticket dimiliki oleh seorang user (requestor/officer).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ticket berasal dari satu divisi korporat.
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    // ── Helper Methods ────────────────────────────────────────────────────────

    /**
     * Gate 4 check: apakah dokumen Izin Prinsip sudah diupload ke S3?
     * Ticket tanpa document_path harus tetap berada di status 'draft'.
     */
    public function hasDocument(): bool
    {
        return !empty($this->document_path);
    }

    /**
     * Periksa apakah ticket masih berada di status draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Periksa apakah pagu divisi untuk ticket ini sudah dikunci (Gate 1 lolos).
     */
    public function isBudgetLocked(): bool
    {
        return $this->status === self::STATUS_BUDGET_LOCKED;
    }
}

