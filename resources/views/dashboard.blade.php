<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 leading-tight">
            Dashboard E-Procurement BNI
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Selamat Datang -->
            <div class="bg-gradient-to-r from-bni-teal to-cyan-700 overflow-hidden shadow-sm rounded-xl p-8 text-white">
                <h3 class="text-xl font-bold">Selamat datang, {{ auth()->user()->name }}!</h3>
                <p class="mt-2 text-sm text-white text-opacity-80 leading-relaxed max-w-2xl">
                    Sistem E-Procurement BNI — Platform pengadaan barang & jasa korporat yang terotomatisasi
                    dengan <strong>4-Gate Smart Validation Engine</strong> dan multi-role approval pipeline.
                </p>
                <div class="flex items-center gap-3 mt-3">
                    @php
                        $roleBadge = match(auth()->user()->role) {
                            'staff' => ['label' => 'Staff', 'bg' => 'bg-white bg-opacity-20'],
                            'head_dept' => ['label' => 'Head Department', 'bg' => 'bg-indigo-500 bg-opacity-70'],
                            'head_div' => ['label' => 'Head Division', 'bg' => 'bg-purple-500 bg-opacity-70'],
                            'pfa' => ['label' => 'Procurement Fixed Assets', 'bg' => 'bg-orange-500 bg-opacity-70'],
                            default => ['label' => 'User', 'bg' => 'bg-gray-500 bg-opacity-70'],
                        };
                    @endphp
                    <span class="inline-block px-3 py-1 {{ $roleBadge['bg'] }} rounded-full text-xs font-bold">
                        {{ $roleBadge['label'] }}
                    </span>
                    @if(auth()->user()->division)
                        <span class="inline-block px-3 py-1 bg-white bg-opacity-15 rounded-full text-xs font-semibold">
                            {{ auth()->user()->division->name }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- Pagu Anggaran (Untuk Staff & Head Dept & Head Div) -->
            @if(auth()->user()->division)
                <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-6">
                    <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="h-5 w-5 text-bni-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Pagu Anggaran Divisi
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Limit Tahunan</p>
                            <p class="text-lg font-extrabold text-gray-800 mt-1">Rp {{ number_format(auth()->user()->division->yearly_budget_limit, 2, ',', '.') }}</p>
                        </div>
                        <div class="bg-emerald-50 rounded-lg p-4 border border-emerald-100">
                            <p class="text-xs text-emerald-600 uppercase font-bold tracking-wider">Sisa Tersedia</p>
                            <p class="text-lg font-extrabold text-emerald-800 mt-1">Rp {{ number_format(auth()->user()->division->remaining_budget, 2, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Quick Actions (berdasarkan role) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Semua role bisa lihat daftar tiket -->
                <a href="{{ route('tickets.index') }}"
                   class="group bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-6 hover:border-bni-teal hover:shadow-md transition-all">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-bni-teal bg-opacity-10 rounded-lg group-hover:bg-opacity-20 transition-colors">
                            <svg class="h-6 w-6 text-bni-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 group-hover:text-bni-teal transition-colors">
                                @if(auth()->user()->isStaff()) Tiket Pengadaan Saya
                                @else Monitor Seluruh Tiket
                                @endif
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                @if(auth()->user()->isStaff()) Lihat riwayat pengajuan pengadaan Anda
                                @elseif(auth()->user()->isPfa()) Review dokumen & generate PO
                                @elseif(auth()->user()->isHeadDept()) Tinjau & teruskan tiket ke Head Division
                                @elseif(auth()->user()->isHeadDiv()) Review & ambil keputusan pengadaan
                                @endif
                            </p>
                        </div>
                    </div>
                </a>

                <!-- Hanya Staff yang bisa buat tiket baru -->
                @if(auth()->user()->isStaff())
                    <a href="{{ route('tickets.create') }}"
                       class="group bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-6 hover:border-bni-orange hover:shadow-md transition-all">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-bni-orange bg-opacity-10 rounded-lg group-hover:bg-opacity-20 transition-colors">
                                <svg class="h-6 w-6 text-bni-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-800 group-hover:text-bni-orange transition-colors">Ajukan Pengadaan Baru</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Buat tiket pengadaan barang atau jasa baru</p>
                            </div>
                        </div>
                    </a>
                @endif
            </div>

            <!-- Info Pipeline -->
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-6">
                <h3 class="text-sm font-bold text-gray-800 mb-4">Alur Approval Pipeline</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-8 gap-2">
                    @php
                        $steps = [
                            ['label' => 'Pending Review', 'color' => 'yellow'],
                            ['label' => 'Revision', 'color' => 'orange'],
                            ['label' => 'Validate', 'color' => 'blue'],
                            ['label' => 'Dept Head', 'color' => 'indigo'],
                            ['label' => 'Div Head', 'color' => 'purple'],
                            ['label' => 'Declined', 'color' => 'red'],
                            ['label' => 'Approved', 'color' => 'emerald'],
                            ['label' => 'PO Generated', 'color' => 'teal'],
                        ];
                    @endphp
                    @foreach($steps as $i => $step)
                        <div class="bg-{{ $step['color'] }}-50 rounded-lg p-2 border border-{{ $step['color'] }}-100 text-center">
                            <div class="text-{{ $step['color'] }}-600 font-extrabold text-sm">{{ $i + 1 }}</div>
                            <p class="text-[10px] font-semibold text-{{ $step['color'] }}-800 mt-0.5 leading-tight">{{ $step['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
