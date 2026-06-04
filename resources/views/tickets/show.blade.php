<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <a href="{{ route('tickets.index') }}" class="text-sm font-semibold text-bni-teal hover:underline flex items-center gap-1">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Daftar Pengajuan
                </a>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight mt-2">
                    Detail Tiket: #EP-{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Dibuat oleh: {{ $ticket->user->name }} • Divisi {{ $ticket->division->name }}</p>
            </div>
            
            <!-- Badge Status Utama -->
            <div>
                @switch($ticket->status)
                    @case('draft')
                        <span class="px-4 py-2 text-sm font-bold rounded-full bg-gray-100 text-gray-800 border border-gray-200 shadow-sm">
                            Status: DRAFT
                        </span>
                        @break
                    @case('pending_validation')
                        <span class="px-4 py-2 text-sm font-bold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200 shadow-sm">
                            Status: PENDING
                        </span>
                        @break
                    @case('budget_locked')
                        <span class="px-4 py-2 text-sm font-bold rounded-full bg-blue-100 text-blue-800 border border-blue-200 shadow-sm">
                            Status: BUDGET LOCKED
                        </span>
                        @break
                    @case('approved')
                        <span class="px-4 py-2 text-sm font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-sm">
                            Status: APPROVED
                        </span>
                        @break
                    @case('rejected')
                        <span class="px-4 py-2 text-sm font-bold rounded-full bg-rose-100 text-rose-800 border border-rose-200 shadow-sm">
                            Status: REJECTED
                        </span>
                        @break
                @endswitch
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Main Details Card -->
                <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-6 md:p-8 lg:col-span-2 space-y-6">
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Judul Pengadaan</h3>
                        <p class="text-xl font-bold text-gray-800">{{ $ticket->title }}</p>
                    </div>

                    @if ($ticket->description)
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Deskripsi Spesifikasi</h3>
                            <p class="text-sm text-gray-600 leading-relaxed bg-gray-50 p-4 rounded-lg border border-gray-100">{{ $ticket->description }}</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100">
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Vendor / Penyedia</h3>
                            <p class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                                <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                {{ $ticket->vendor_name }}
                            </p>
                        </div>

                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Klasifikasi Akuntansi (Gate 2)</h3>
                            <p class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                                @if ($ticket->expenditure_type === 'CAPEX')
                                    <span class="px-2.5 py-0.5 text-xs font-bold rounded-md bg-cyan-50 text-cyan-700 border border-cyan-200">
                                        Capital Expenditure (CAPEX)
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 text-xs font-bold rounded-md bg-slate-50 text-slate-600 border border-slate-200">
                                        Operational Expenditure (OPEX)
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100">
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Estimasi Anggaran</h3>
                            <p class="text-lg font-extrabold text-gray-800">
                                Rp {{ number_format($ticket->budget_estimated, 2, ',', '.') }}
                            </p>
                        </div>

                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Diajukan Pada</h3>
                            <p class="text-sm text-gray-600 font-semibold">
                                {{ $ticket->created_at->format('d M Y, H:i') }} WIB
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Info & Actions -->
                <div class="space-y-6">
                    
                    <!-- Dokumen Izin Prinsip Card -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-6 space-y-4">
                        <h3 class="text-sm font-bold text-gray-800">Dokumen Izin Prinsip (Gate 4)</h3>
                        
                        @if ($ticket->document_path)
                            <div class="bg-emerald-50 rounded-lg p-3 flex items-center gap-2.5 border border-emerald-100">
                                <div class="text-emerald-600">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="text-xs text-emerald-800 font-semibold">
                                    Dokumen Izin Prinsip Valid (.pdf)
                                </div>
                            </div>
                            <a href="{{ $ticket->document_path }}" target="_blank"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-bni-teal text-white text-sm font-semibold rounded-lg hover:bg-opacity-90 transition-colors gap-2 shadow-sm">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Unduh / Lihat PDF
                            </a>
                        @else
                            <div class="bg-rose-50 rounded-lg p-3 flex items-center gap-2.5 border border-rose-100">
                                <div class="text-rose-600">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="text-xs text-rose-800 font-semibold leading-relaxed">
                                    Dokumen belum diunggah. Tiket berstatus Draft.
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Panel Otoritas Admin (Approval Flow) -->
                    @can('approve', $ticket)
                        @if ($ticket->status === 'budget_locked')
                            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-bni-orange border-opacity-40 p-6 space-y-4">
                                <div class="flex items-center gap-2 text-bni-orange">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <h3 class="text-sm font-bold text-gray-800">Aksi Otoritas Administrator</h3>
                                </div>
                                <p class="text-xs text-gray-500 leading-relaxed">
                                    Sebagai Administrator, Anda berhak mengevaluasi pengajuan ini. Menolak tiket akan secara otomatis mengembalikan anggaran ke divisi pemohon.
                                </p>
                                
                                <div class="flex flex-col gap-2 pt-2">
                                    <!-- Form Setujui (Approve) -->
                                    <form method="POST" action="{{ route('tickets.approve', $ticket) }}" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin MENYETUJUI pengajuan pengadaan ini?')">
                                        @csrf
                                        <button type="submit" 
                                                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition-colors gap-2 shadow-sm">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Setujui Pengadaan
                                        </button>
                                    </form>

                                    <!-- Form Tolak (Reject & Refund) -->
                                    <form method="POST" action="{{ route('tickets.reject', $ticket) }}" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin MENOLAK pengajuan ini? Sisa pagu anggaran divisi pemohon akan otomatis dikembalikan sebesar nilai tiket.')">
                                        @csrf
                                        <button type="submit" 
                                                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-rose-600 text-white text-sm font-semibold rounded-lg hover:bg-rose-700 transition-colors gap-2 shadow-sm">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Tolak Pengadaan (Refund)
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="bg-gray-50 overflow-hidden shadow-sm rounded-xl border border-gray-200 p-6">
                                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Status Approval</h3>
                                <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                                    Tiket ini sedang dalam status <span class="font-bold text-gray-700">{{ strtoupper($ticket->status) }}</span>. Aksi persetujuan hanya tersedia saat status tiket berada dalam kondisi <strong>BUDGET LOCKED</strong>.
                                </p>
                            </div>
                        @endif
                    @endcan
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
