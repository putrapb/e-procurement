<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    E-Procurement BNI
                </h2>
                <p class="text-sm text-gray-500 mt-1">Portal Manajemen Pengadaan Barang & Jasa Divisi</p>
            </div>
            
            <!-- Dashboard Anggaran Divisi -->
            @if(auth()->user()->division)
                <div class="bg-white px-5 py-3 rounded-lg shadow-sm border border-gray-200 flex items-center gap-4">
                    <div class="p-2 bg-emerald-50 rounded-full text-emerald-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Sisa Pagu {{ auth()->user()->division->name }}</p>
                        <p class="text-lg font-bold text-gray-800">
                            Rp {{ number_format(auth()->user()->division->remaining_budget, 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Dari Limit Tahunan: Rp {{ number_format(auth()->user()->division->yearly_budget_limit, 2, ',', '.') }}
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Action Bar -->
            <div class="mb-6 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-700">Daftar Pengajuan Tiket</h3>
                <a href="{{ route('tickets.create') }}" 
                   class="inline-flex items-center px-4 py-2.5 bg-bni-teal text-white font-semibold text-sm rounded-lg shadow-sm hover:bg-opacity-90 focus:outline-none transition-colors gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    Ajukan Pengadaan Baru
                </a>
            </div>

            <!-- List Tiket -->
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200">
                @if($tickets->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-500">ID / Detail</th>
                                    <th scope="col" class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-500">Klasifikasi</th>
                                    <th scope="col" class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-500">Vendor</th>
                                    <th scope="col" class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-500">Estimasi Anggaran</th>
                                    <th scope="col" class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-500">Dokumen</th>
                                    <th scope="col" class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-500">Status</th>
                                    <th scope="col" class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-500 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($tickets as $ticket)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-gray-900">#EP-{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</div>
                                            <a href="{{ route('tickets.show', $ticket) }}" class="text-sm text-bni-teal hover:underline font-semibold block mt-0.5">
                                                {{ Str::limit($ticket->title, 40) }}
                                            </a>
                                            <span class="text-xs text-gray-400 block mt-1">Tanggal: {{ $ticket->created_at->format('d M Y') }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($ticket->expenditure_type === 'CAPEX')
                                                <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-cyan-50 text-cyan-700 border border-cyan-200">
                                                    CAPEX
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-slate-50 text-slate-600 border border-slate-200">
                                                    OPEX
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-700 font-medium">{{ $ticket->vendor_name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-bold text-gray-800">
                                                Rp {{ number_format($ticket->budget_estimated, 2, ',', '.') }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($ticket->document_path)
                                                <a href="{{ $ticket->document_path }}" target="_blank" 
                                                   class="inline-flex items-center px-2 py-1 bg-gray-50 text-gray-600 hover:text-bni-teal hover:bg-teal-50 border border-gray-200 text-xs rounded font-semibold gap-1 transition-colors">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    PDF Izin Prinsip
                                                </a>
                                            @else
                                                <span class="text-xs text-gray-400 italic">Belum Upload</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @switch($ticket->status)
                                                @case('draft')
                                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 border border-gray-200">
                                                        Draft
                                                    </span>
                                                    @break
                                                @case('pending_validation')
                                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">
                                                        Pending
                                                    </span>
                                                    @break
                                                @case('budget_locked')
                                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 border border-blue-200">
                                                        Budget Locked
                                                    </span>
                                                    @break
                                                @case('approved')
                                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                        Approved
                                                    </span>
                                                    @break
                                                @case('rejected')
                                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-rose-100 text-rose-800 border border-rose-200">
                                                        Rejected
                                                    </span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('tickets.show', $ticket) }}" class="text-gray-500 hover:text-gray-900 transition-colors">Detail</a>
                                                
                                                @can('update', $ticket)
                                                    <span class="text-gray-300">|</span>
                                                    <a href="{{ route('tickets.edit', $ticket) }}" class="text-bni-teal hover:underline transition-colors">Edit</a>
                                                @endcan
                                                
                                                @can('delete', $ticket)
                                                    <span class="text-gray-300">|</span>
                                                    <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan/menghapus pengajuan ini? Pagu anggaran akan dikembalikan.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-rose-600 hover:text-rose-900 font-semibold transition-colors">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if ($tickets->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $tickets->links() }}
                        </div>
                    @endif
                @else
                    <!-- Empty State -->
                    <div class="p-12 text-center">
                        <div class="mx-auto w-16 h-16 bg-gray-50 text-gray-400 rounded-full flex items-center justify-center mb-4">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-700">Belum Ada Pengajuan</h3>
                        <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">Anda belum mengajukan tiket pengadaan apa pun untuk divisi ini. Gunakan tombol di atas untuk membuat pengajuan pengadaan baru.</p>
                        <a href="{{ route('tickets.create') }}" 
                           class="inline-flex items-center px-4 py-2 mt-4 bg-bni-teal text-white text-sm font-semibold rounded-lg hover:bg-opacity-90 transition-colors">
                            Mulai Pengajuan
                        </a>
                    </div>
                @endif
            </div>
            
        </div>
    </div>
</x-app-layout>
