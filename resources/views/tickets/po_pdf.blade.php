<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order - {{ $po->po_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-logo-cell {
            width: 50%;
            vertical-align: middle;
        }
        .header-logo {
            font-size: 24px;
            font-weight: bold;
            color: #005E6A; /* BNI Teal */
        }
        .header-logo span {
            color: #F15A24; /* BNI Orange */
        }
        .header-info-cell {
            width: 50%;
            text-align: right;
            vertical-align: middle;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #005E6A;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        .divider {
            height: 3px;
            background-color: #005E6A;
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .info-cell {
            width: 50%;
            vertical-align: top;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #005E6A;
            text-transform: uppercase;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 8px;
            width: 90%;
        }
        .info-content {
            line-height: 1.5;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .item-table th {
            background-color: #005E6A;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 8px 10px;
            font-size: 11px;
            text-transform: uppercase;
        }
        .item-table td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .item-table tr:nth-child(even) td {
            background-color: #f9fafb;
        }
        .total-section {
            width: 100%;
            margin-top: 10px;
        }
        .total-table {
            width: 40%;
            float: right;
            border-collapse: collapse;
        }
        .total-table td {
            padding: 6px 10px;
        }
        .total-label {
            font-weight: bold;
            text-align: right;
        }
        .total-value {
            text-align: right;
            font-weight: bold;
            color: #005E6A;
            font-size: 14px;
        }
        .notes-section {
            width: 55%;
            float: left;
            background-color: #f3f4f6;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
        }
        .notes-title {
            font-weight: bold;
            color: #4b5563;
            margin-bottom: 5px;
            font-size: 11px;
            text-transform: uppercase;
        }
        .notes-content {
            color: #6b7280;
            font-size: 11px;
        }
        .footer-space {
            height: 120px;
            clear: both;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        .signature-cell {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .signature-space {
            height: 60px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
        .signature-title {
            color: #6b7280;
            font-size: 11px;
        }
    </style>
</head>
<body>

    <!-- Header logo and document title -->
    <table class="header-table">
        <tr>
            <td class="header-logo-cell">
                <div class="header-logo">BNI <span>e-Procurement</span></div>
                <div style="font-size: 10px; color: #6b7280; margin-top: 3px;">
                    Divisi Teknologi Informasi - Gedung Landmark Tower BNI<br>
                    Jl. Jenderal Sudirman No.1, Jakarta Pusat
                </div>
            </td>
            <td class="header-info-cell">
                <div class="title">Purchase Order</div>
                <div style="font-weight: bold; font-size: 13px; color: #4b5563;">
                    No: {{ $po->po_number }}
                </div>
                <div style="color: #6b7280; font-size: 11px; margin-top: 2px;">
                    Tanggal: {{ $po->created_at->format('d F Y') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Vendor and delivery details -->
    <table class="info-table">
        <tr>
            <td class="info-cell">
                <div class="section-title">Vendor / Penyedia Jasa</div>
                <div class="info-content">
                    <strong>{{ $ticket->vendor_name }}</strong><br>
                    Mitra Penyedia Barang/Jasa Terdaftar<br>
                    Jakarta, Indonesia
                </div>
            </td>
            <td class="info-cell">
                <div class="section-title">Pemohon & Tujuan Pengiriman</div>
                <div class="info-content">
                    <strong>{{ $ticket->division->name }}</strong><br>
                    Diajukan oleh: {{ $ticket->user->name }} ({{ $ticket->user->position }})<br>
                    Tujuan: Divisi TI Lantai 15, Gedung Landmark Tower BNI
                </div>
            </td>
        </tr>
    </table>

    <!-- Items detail -->
    <table class="item-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">No</th>
                <th style="width: 50%;">Deskripsi Barang / Jasa</th>
                <th style="width: 15%; text-align: center;">Klasifikasi</th>
                <th style="width: 30%; text-align: right;">Estimasi Harga</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">1</td>
                <td>
                    <strong>{{ $ticket->title }}</strong>
                    @if($ticket->description)
                        <div style="font-size: 10px; color: #6b7280; margin-top: 4px; line-height: 1.4;">
                            {{ $ticket->description }}
                        </div>
                    @endif
                </td>
                <td style="text-align: center;">
                    <span style="font-weight: bold; color: {{ $ticket->expenditure_type === 'CAPEX' ? '#005E6A' : '#F15A24' }}">
                        {{ $ticket->expenditure_type }}
                    </span>
                </td>
                <td style="text-align: right; font-weight: bold;">
                    Rp {{ number_format($ticket->budget_estimated, 2, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Notes & Totals -->
    <div class="total-section">
        <div class="notes-section">
            <div class="notes-title">Catatan Syarat & Ketentuan</div>
            <div class="notes-content">
                @if($po->notes)
                    {{ $po->notes }}
                @else
                    1. Pengadaan ini didanai oleh anggaran divisi IT yang sah.<br>
                    2. Pembayaran akan dilakukan setelah barang/jasa diterima dengan melampirkan Berita Acara Serah Terima (BAST).<br>
                    3. Nomor PO ini harus dicantumkan pada seluruh dokumen penagihan dan kwitansi (invoice).
                @endif
            </div>
        </div>

        <table class="total-table">
            <tr>
                <td class="total-label">Subtotal:</td>
                <td style="text-align: right;">Rp {{ number_format($ticket->budget_estimated, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-label">Pajak (0%):</td>
                <td style="text-align: right;">Rp 0,00</td>
            </tr>
            <tr style="border-top: 1px solid #e5e7eb;">
                <td class="total-label" style="font-size: 13px; padding-top: 10px;">Total PO:</td>
                <td class="total-value" style="padding-top: 10px;">
                    Rp {{ number_format($ticket->budget_estimated, 2, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-space"></div>

    <!-- Approvals / Signatures -->
    <table class="signature-table">
        <tr>
            <td class="signature-cell">
                <div class="signature-title">Disiapkan Oleh (PFA Officer),</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $po->generatedBy->name }}</div>
                <div class="signature-title">Procurement Fixed Assets</div>
            </td>
            <td class="signature-cell">
                <div class="signature-title">Disetujui Oleh (Head Division IT),</div>
                <div class="signature-space"></div>
                <!-- Menampilkan nama Head Division IT BNI (diambil dari database seeder) -->
                <div class="signature-name">Dr. Ratna Megawati, M.Kom</div>
                <div class="signature-title">Head Division IT BNI</div>
            </td>
        </tr>
    </table>

</body>
</html>
