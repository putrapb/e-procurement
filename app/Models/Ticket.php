<?php

namespace App\Models;

use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    // ── Status Lifecycle Constants (8-Step Multi-Role Pipeline) ───────────────
    const STATUS_PENDING_REVIEW    = 'pending_review';     // Staff submit → menunggu PFA
    const STATUS_REVISION          = 'revision';           // PFA tolak dokumen → staff revisi
    const STATUS_NEED_TO_VALIDATE  = 'need_to_validate';   // PFA approve → staff run 4-Gate
    const STATUS_PENDING_DEPT_HEAD = 'pending_dept_head';  // 4-Gate lolos → menunggu Head Dept
    const STATUS_PENDING_DIV_HEAD  = 'pending_div_head';   // Head Dept forward → menunggu Head Div
    const STATUS_DECLINED          = 'declined';           // Head Div tolak
    const STATUS_APPROVED          = 'approved';           // Head Div approve → pagu dikunci
    const STATUS_PO_GENERATED      = 'po_generated';       // PFA generate PO

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
        'rejection_note',
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
     * Ticket dimiliki oleh seorang staff (requestor).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ticket berasal dari satu departemen korporat.
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Ticket yang sudah approved memiliki satu Purchase Order.
     */
    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class);
    }

    // ── Status Helper Methods ────────────────────────────────────────────────

    public function isPendingReview(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    public function isRevision(): bool
    {
        return $this->status === self::STATUS_REVISION;
    }

    public function needsValidation(): bool
    {
        return $this->status === self::STATUS_NEED_TO_VALIDATE;
    }

    public function isPendingDeptHead(): bool
    {
        return $this->status === self::STATUS_PENDING_DEPT_HEAD;
    }

    public function isPendingDivHead(): bool
    {
        return $this->status === self::STATUS_PENDING_DIV_HEAD;
    }

    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPoGenerated(): bool
    {
        return $this->status === self::STATUS_PO_GENERATED;
    }

    /**
     * Gate 4 check: apakah dokumen Izin Prinsip sudah diupload?
     */
    public function hasDocument(): bool
    {
        return !empty($this->document_path);
    }

    /**
     * Readable label untuk status ticket.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_REVIEW    => 'Pending Review',
            self::STATUS_REVISION          => 'Revision',
            self::STATUS_NEED_TO_VALIDATE  => 'Need to Validate',
            self::STATUS_PENDING_DEPT_HEAD => 'Pending Dept Head',
            self::STATUS_PENDING_DIV_HEAD  => 'Pending Div Head',
            self::STATUS_DECLINED          => 'Declined',
            self::STATUS_APPROVED          => 'Approved',
            self::STATUS_PO_GENERATED      => 'PO Generated',
            default                        => ucfirst($this->status),
        };
    }

    /**
     * Warna badge untuk status ticket (Tailwind class).
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_REVIEW    => 'bg-yellow-100 text-yellow-800',
            self::STATUS_REVISION          => 'bg-orange-100 text-orange-800',
            self::STATUS_NEED_TO_VALIDATE  => 'bg-blue-100 text-blue-800',
            self::STATUS_PENDING_DEPT_HEAD => 'bg-indigo-100 text-indigo-800',
            self::STATUS_PENDING_DIV_HEAD  => 'bg-purple-100 text-purple-800',
            self::STATUS_DECLINED          => 'bg-red-100 text-red-800',
            self::STATUS_APPROVED          => 'bg-emerald-100 text-emerald-800',
            self::STATUS_PO_GENERATED      => 'bg-teal-100 text-teal-800',
            default                        => 'bg-gray-100 text-gray-800',
        };
    }
}
