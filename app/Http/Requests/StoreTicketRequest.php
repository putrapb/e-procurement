<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreTicketRequest
 *
 * Form Request untuk validasi input pengajuan ticket pengadaan baru.
 * Layer ini hanya bertanggung jawab atas validasi format & keberadaan field
 * (sintaksis). Validasi logika bisnis (semantik) seperti budget check dan
 * CAPEX/OPEX classification dijalankan oleh ProcurementValidationService.
 */
class StoreTicketRequest extends FormRequest
{
    /**
     * Tentukan apakah user berhak membuat request ini.
     * Otorisasi berbasis role akan diimplementasikan via Policy pada iterasi berikutnya.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Aturan validasi input untuk pengajuan ticket baru.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Identitas Pengadaan
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:5000'],

            // Gate 1: Nilai budget harus numerik positif, format desimal 2 angka
            'budget_estimated' => ['required', 'numeric', 'min:1', 'decimal:0,2'],

            // Gate 3: Nama vendor wajib ada
            'vendor_name'      => ['required', 'string', 'max:255'],

            // Gate 4: Izin Prinsip — opsional saat store (jika null, status = draft)
            // Upload dilakukan via endpoint terpisah (UploadDocumentController)
            'document_path'    => ['nullable', 'string', 'max:2048'],
        ];
    }

    /**
     * Pesan error kustom dalam Bahasa Indonesia untuk tampilan UI.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required'            => 'Judul pengadaan wajib diisi.',
            'title.max'                 => 'Judul pengadaan tidak boleh melebihi 255 karakter.',
            'budget_estimated.required' => 'Estimasi anggaran wajib diisi.',
            'budget_estimated.numeric'  => 'Estimasi anggaran harus berupa angka.',
            'budget_estimated.min'      => 'Estimasi anggaran harus bernilai positif (minimal Rp 1).',
            'budget_estimated.decimal'  => 'Estimasi anggaran maksimal 2 angka di belakang koma.',
            'vendor_name.required'      => 'Nama vendor/pemasok wajib diisi.',
            'vendor_name.max'           => 'Nama vendor tidak boleh melebihi 255 karakter.',
        ];
    }
}
