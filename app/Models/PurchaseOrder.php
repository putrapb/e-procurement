<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PurchaseOrder — Model untuk Purchase Order yang di-generate oleh PFA.
 *
 * Setiap ticket yang sudah di-approve oleh Head Division akan mendapatkan
 * satu PO yang berisi nomor PO unik dan file PDF yang bisa di-download.
 */
class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'po_number',
        'generated_by',
        'pdf_path',
        'notes',
    ];

    // ── Relasi ───────────────────────────────────────────────────────────────

    /**
     * PO ini untuk satu ticket pengadaan.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * PO ini di-generate oleh seorang PFA.
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
