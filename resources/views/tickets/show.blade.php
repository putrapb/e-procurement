{{--
    tickets/show.blade.php
    STUB VIEW — Placeholder untuk test. Akan diganti dengan UI penuh setelah design.md selesai.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detail Ticket: {{ $ticket->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if (session('success'))
                    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                <table class="w-full text-sm text-left">
                    <tbody>
                        <tr class="border-b">
                            <th class="py-2 pr-4 font-medium text-gray-600 w-1/4">ID</th>
                            <td class="py-2">{{ $ticket->id }}</td>
                        </tr>
                        <tr class="border-b">
                            <th class="py-2 pr-4 font-medium text-gray-600">Status</th>
                            <td class="py-2">
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    {{ $ticket->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $ticket->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $ticket->status === 'budget_locked' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $ticket->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $ticket->status === 'pending_validation' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                ">
                                    {{ strtoupper($ticket->status) }}
                                </span>
                            </td>
                        </tr>
                        <tr class="border-b">
                            <th class="py-2 pr-4 font-medium text-gray-600">Jenis Belanja</th>
                            <td class="py-2">{{ $ticket->expenditure_type ?? '— Menunggu Klasifikasi' }}</td>
                        </tr>
                        <tr class="border-b">
                            <th class="py-2 pr-4 font-medium text-gray-600">Estimasi Anggaran</th>
                            <td class="py-2">Rp {{ number_format($ticket->budget_estimated, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="border-b">
                            <th class="py-2 pr-4 font-medium text-gray-600">Vendor</th>
                            <td class="py-2">{{ $ticket->vendor_name }}</td>
                        </tr>
                        <tr class="border-b">
                            <th class="py-2 pr-4 font-medium text-gray-600">Divisi</th>
                            <td class="py-2">{{ $ticket->division->name ?? '—' }}</td>
                        </tr>
                        <tr class="border-b">
                            <th class="py-2 pr-4 font-medium text-gray-600">Requestor</th>
                            <td class="py-2">{{ $ticket->user->name }}</td>
                        </tr>
                        <tr class="border-b">
                            <th class="py-2 pr-4 font-medium text-gray-600">Dokumen Izin Prinsip</th>
                            <td class="py-2">
                                @if ($ticket->document_path)
                                    <a href="{{ $ticket->document_path }}" target="_blank" class="text-blue-600 underline">
                                        Lihat Dokumen PDF
                                    </a>
                                @else
                                    <span class="text-red-500 text-xs">Belum diupload — ticket berstatus draft</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="py-2 pr-4 font-medium text-gray-600">Tanggal Pengajuan</th>
                            <td class="py-2">{{ $ticket->created_at->format('d M Y, H:i') }} WIB</td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-6">
                    <a href="{{ route('tickets.index') }}" class="text-gray-600 hover:underline">
                        ← Kembali ke Daftar Ticket
                    </a>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
