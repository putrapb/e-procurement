<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">Detail Tiket #{{ $ticket->id }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $ticket->title }}</p>
            </div>
            <a href="{{ route('tickets.index') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-700">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-lg">
                    <p class="text-sm font-medium text-emerald-700">{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                    <p class="text-sm font-medium text-red-700">{{ session('error') }}</p>
                </div>
            @endif

            {{-- Status Progress Bar --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6">
                <h3 class="text-sm font-bold text-gray-700 mb-4">Progress Pipeline</h3>
                @php
                    $allStatuses = [
                        'pending_review' => 'Pending Review',
                        'revision' => 'Revision',
                        'need_to_validate' => 'Validate',
                        'pending_dept_head' => 'Dept Head',
                        'pending_div_head' => 'Div Head',
                        'approved' => 'Approved',
                        'po_generated' => 'PO Generated',
                    ];
                    $statusOrder = array_keys($allStatuses);
                    $currentIdx = array_search($ticket->status, $statusOrder);
                    if ($ticket->status === 'declined') $currentIdx = -1;
                @endphp
                <div class="flex items-center gap-1 overflow-x-auto">
                    @foreach($allStatuses as $key => $label)
                        @php
                            $idx = array_search($key, $statusOrder);
                            $isActive = $ticket->status === $key;
                            $isPast = $currentIdx !== false && $idx < $currentIdx;
                            $bgClass = $isActive ? 'bg-bni-teal text-white' : ($isPast ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400');
                        @endphp
                        <div class="flex-1 text-center py-2 px-1 rounded-lg text-[10px] font-bold {{ $bgClass }} transition-colors">
                            {{ $label }}
                        </div>
                    @endforeach
                </div>
                @if($ticket->status === 'declined')
                    <div class="mt-2 text-center py-2 bg-red-100 text-red-700 rounded-lg text-xs font-bold">
                        ❌ DECLINED
                    </div>
                @endif
            </div>

            {{-- Ticket Info --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Requestor</p>
                        <p class="text-sm font-semibold text-gray-800 mt-1">{{ $ticket->user->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Departemen</p>
                        <p class="text-sm font-semibold text-gray-800 mt-1">{{ $ticket->division->name }} ({{ $ticket->division->code }})</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Estimasi Anggaran</p>
                        <p class="text-lg font-extrabold text-gray-800 mt-1">Rp {{ number_format($ticket->budget_estimated, 2, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Vendor</p>
                        <p class="text-sm font-semibold text-gray-800 mt-1">{{ $ticket->vendor_name }}</p>
                    </div>
                    @if($ticket->expenditure_type)
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Klasifikasi (Gate 2)</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold mt-1 {{ $ticket->expenditure_type === 'CAPEX' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                {{ $ticket->expenditure_type }}
                            </span>
                        </div>
                    @endif
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Status</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold mt-1 {{ $ticket->status_color }}">
                            {{ $ticket->status_label }}
                        </span>
                    </div>
                </div>

                @if($ticket->description)
                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <p class="text-xs text-gray-400 uppercase font-bold tracking-wider mb-2">Deskripsi</p>
                        <p class="text-sm text-gray-700 leading-relaxed">{{ $ticket->description }}</p>
                    </div>
                @endif
            </div>

            {{-- Dokumen --}}
            @if($ticket->document_path)
                <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6">
                    <p class="text-xs text-gray-400 uppercase font-bold tracking-wider mb-3">Dokumen Izin Prinsip</p>
                    <a href="{{ $ticket->document_path }}" target="_blank"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-semibold text-bni-teal hover:bg-gray-100 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Lihat / Download PDF
                    </a>
                </div>
            @endif

            {{-- Rejection Note --}}
            @if($ticket->rejection_note)
                <div class="bg-red-50 border-l-4 border-red-500 rounded-r-xl p-4">
                    <h4 class="text-sm font-bold text-red-800">Catatan Penolakan</h4>
                    <p class="text-sm text-red-700 mt-1">{{ $ticket->rejection_note }}</p>
                </div>
            @endif

            {{-- Purchase Order (jika sudah di-generate) --}}
            @if($ticket->purchaseOrder)
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6">
                    <h4 class="text-sm font-bold text-emerald-800 mb-3">Purchase Order</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-emerald-600 uppercase font-bold">Nomor PO</p>
                            <p class="text-lg font-extrabold text-emerald-800 mt-1">{{ $ticket->purchaseOrder->po_number }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-emerald-600 uppercase font-bold">Tanggal Generate</p>
                            <p class="text-sm font-semibold text-emerald-800 mt-1">{{ $ticket->purchaseOrder->created_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>
                    @if($ticket->purchaseOrder->notes)
                        <p class="text-sm text-emerald-700 mt-3">{{ $ticket->purchaseOrder->notes }}</p>
                    @endif
                    @if($ticket->purchaseOrder->pdf_path)
                        <div class="mt-4 pt-4 border-t border-emerald-200">
                            <a href="{{ $ticket->purchaseOrder->pdf_path }}" target="_blank"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-bold transition-colors">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Unduh Purchase Order (PDF)
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ═══════════════════════════════════════════════════════════════ --}}
            {{-- ACTION BUTTONS (role-based) --}}
            {{-- ═══════════════════════════════════════════════════════════════ --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-4">
                <h3 class="text-sm font-bold text-gray-700">Aksi yang Tersedia</h3>

                {{-- STAFF: Edit (saat revision) --}}
                @if(auth()->user()->isStaff() && $ticket->isRevision() && auth()->id() === $ticket->user_id)
                    <a href="{{ route('tickets.edit', $ticket) }}"
                       class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-orange-500 text-white font-bold text-sm rounded-lg hover:bg-orange-600 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Revisi Dokumen & Kirim Ulang
                    </a>
                @endif

                {{-- STAFF: Run 4-Gate Validation (saat need_to_validate) --}}
                @if(auth()->user()->isStaff() && $ticket->needsValidation() && auth()->id() === $ticket->user_id)
                    <form action="{{ route('tickets.validate', $ticket) }}" method="POST"
                          x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        <button type="submit" :disabled="loading"
                                style="background-color: #005E6A;"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 text-white font-bold text-sm rounded-lg hover:opacity-90 transition-colors disabled:opacity-50">
                            <svg x-show="!loading" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span x-text="loading ? 'Memproses Validasi...' : 'Jalankan 4-Gate Smart Validation'">Jalankan 4-Gate Smart Validation</span>
                        </button>
                    </form>
                @endif

                {{-- PFA: Review Document (saat pending_review) --}}
                @if(auth()->user()->isPfa() && $ticket->isPendingReview())
                    <div class="flex gap-3">
                        <form action="{{ route('tickets.review-approve', $ticket) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit"
                                    class="w-full px-4 py-3 bg-emerald-600 text-white font-bold text-sm rounded-lg hover:bg-emerald-700 transition-colors">
                                ✓ Dokumen Sesuai
                            </button>
                        </form>

                        <form action="{{ route('tickets.review-reject', $ticket) }}" method="POST" class="flex-1"
                              x-data="{ showNote: false }">
                            @csrf
                            <button type="button" @click="showNote = !showNote"
                                    class="w-full px-4 py-3 bg-red-600 text-white font-bold text-sm rounded-lg hover:bg-red-700 transition-colors">
                                ✗ Tolak Dokumen
                            </button>
                            <div x-show="showNote" x-transition class="mt-3 space-y-2">
                                <textarea name="rejection_note" rows="3" required
                                          placeholder="Jelaskan alasan penolakan dokumen..."
                                          class="w-full rounded-lg border-gray-300 text-sm focus:border-red-500 focus:ring-red-500"></textarea>
                                <button type="submit" class="px-4 py-2 bg-red-700 text-white text-xs font-bold rounded-lg">
                                    Kirim Penolakan
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                {{-- PFA: Generate PO (saat approved) --}}
                @if(auth()->user()->isPfa() && $ticket->isApproved())
                    <form action="{{ route('tickets.generate-po', $ticket) }}" method="POST"
                          x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        <textarea name="notes" rows="2" placeholder="Catatan PO (opsional)..."
                                  class="w-full rounded-lg border-gray-300 text-sm mb-3 focus:border-bni-teal focus:ring-bni-teal"></textarea>
                        <button type="submit" :disabled="loading"
                                style="background-color: #005E6A;"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 text-white font-bold text-sm rounded-lg hover:opacity-90 transition-colors disabled:opacity-50">
                            <span x-text="loading ? 'Generating...' : 'Generate Purchase Order'">Generate Purchase Order</span>
                        </button>
                    </form>
                @endif

                {{-- HEAD DEPT: Forward (saat pending_dept_head) --}}
                @if(auth()->user()->isHeadDept() && $ticket->isPendingDeptHead())
                    <form action="{{ route('tickets.forward', $ticket) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-indigo-600 text-white font-bold text-sm rounded-lg hover:bg-indigo-700 transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            Teruskan ke Head Division
                        </button>
                    </form>
                @endif

                {{-- HEAD DIV: Approve / Decline (saat pending_div_head) --}}
                @if(auth()->user()->isHeadDiv() && $ticket->isPendingDivHead())
                    <div class="flex gap-3">
                        <form action="{{ route('tickets.approve', $ticket) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit"
                                    class="w-full px-4 py-3 bg-emerald-600 text-white font-bold text-sm rounded-lg hover:bg-emerald-700 transition-colors">
                                ✓ Setujui Pengadaan
                            </button>
                        </form>

                        <form action="{{ route('tickets.decline', $ticket) }}" method="POST" class="flex-1"
                              x-data="{ showNote: false }">
                            @csrf
                            <button type="button" @click="showNote = !showNote"
                                    class="w-full px-4 py-3 bg-red-600 text-white font-bold text-sm rounded-lg hover:bg-red-700 transition-colors">
                                ✗ Tolak Pengadaan
                            </button>
                            <div x-show="showNote" x-transition class="mt-3 space-y-2">
                                <textarea name="rejection_note" rows="3" required
                                          placeholder="Jelaskan alasan penolakan pengadaan..."
                                          class="w-full rounded-lg border-gray-300 text-sm focus:border-red-500 focus:ring-red-500"></textarea>
                                <button type="submit" class="px-4 py-2 bg-red-700 text-white text-xs font-bold rounded-lg">
                                    Kirim Penolakan
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                {{-- Tidak ada aksi yang tersedia --}}
                @php
                    $hasAction = false;
                    if (auth()->user()->isStaff() && ($ticket->isRevision() || $ticket->needsValidation()) && auth()->id() === $ticket->user_id) $hasAction = true;
                    if (auth()->user()->isPfa() && ($ticket->isPendingReview() || $ticket->isApproved())) $hasAction = true;
                    if (auth()->user()->isHeadDept() && $ticket->isPendingDeptHead()) $hasAction = true;
                    if (auth()->user()->isHeadDiv() && $ticket->isPendingDivHead()) $hasAction = true;
                @endphp
                @unless($hasAction)
                    <p class="text-sm text-gray-400 italic">Tidak ada aksi yang tersedia untuk role Anda pada status tiket ini.</p>
                @endunless
            </div>

        </div>
    </div>
</x-app-layout>
