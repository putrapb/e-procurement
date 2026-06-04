<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PurchaseOrderService
{
    /**
     * Generate PDF untuk Purchase Order dan simpan ke public storage.
     *
     * @param  PurchaseOrder  $po
     * @param  Ticket         $ticket
     * @return string                 Public URL atau path ke PDF
     */
    public function generatePoPdf(PurchaseOrder $po, Ticket $ticket): string
    {
        try {
            // Load view PDF dengan data PO dan Ticket
            $pdf = Pdf::loadView('tickets.po_pdf', [
                'po' => $po,
                'ticket' => $ticket,
            ]);

            // Dapatkan output raw PDF
            $pdfContent = $pdf->output();

            // Tentukan nama file: purchase_orders/PO-YYYYMMDD-XXXX.pdf
            $filename = 'purchase_orders/' . $po->po_number . '.pdf';

            // Simpan ke storage 'public'
            Storage::disk('public')->put($filename, $pdfContent);

            // Dapatkan URL public untuk didownload
            return Storage::disk('public')->url($filename);

        } catch (\Exception $e) {
            Log::error('Gagal generate PDF PO untuk ' . $po->po_number . ': ' . $e->getMessage());
            throw $e;
        }
    }
}
