<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    @if(auth()->user()->isStaff()) Tiket Pengadaan Saya
                    @else Monitor Tiket Pengadaan
                    @endif
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    @if(auth()->user()->isStaff()) Riwayat seluruh pengajuan pengadaan yang Anda buat
                    @elseif(auth()->user()->isPfa()) Review dokumen dan generate Purchase Order
                    @elseif(auth()->user()->isHeadDept()) Tinjau dan teruskan tiket ke Head Division
                    @elseif(auth()->user()->isHeadDiv()) Ambil keputusan akhir pengadaan
                    @endif
                </p>
            </div>
            @if(auth()->user()->isStaff())
                <a href="{{ route('tickets.create') }}"
                   style="background-color: #005E6A;"
                   class="inline-flex items-center px-4 py-2.5 text-white font-semibold text-sm rounded-lg shadow-sm hover:opacity-90 transition-all gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Ajukan Baru
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mb-4 bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-lg">
                    <p class="text-sm font-medium text-emerald-700">{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                    <p class="text-sm font-medium text-red-700">{{ session('error') }}</p>
                </div>
            @endif

            {{-- Ticket Table --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200">
                @if($tickets->isEmpty())
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3 class="mt-4 text-sm font-semibold text-gray-600">Belum ada tiket pengadaan</h3>
                        @if(auth()->user()->isStaff())
                            <a href="{{ route('tickets.create') }}" class="mt-2 inline-block text-sm font-semibold text-bni-teal hover:underline">Buat pengajuan pertama →</a>
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Judul Pengadaan</th>
                                    @if(!auth()->user()->isStaff())
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Requestor</th>
                                    @endif
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Divisi</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Estimasi</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($tickets as $ticket)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500">#{{ $ticket->id }}</td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-semibold text-gray-800 truncate max-w-[200px]">{{ $ticket->title }}</div>
                                            <div class="text-xs text-gray-400">{{ $ticket->vendor_name }}</div>
                                        </td>
                                        @if(!auth()->user()->isStaff())
                                            <td class="px-6 py-4 text-sm text-gray-600">{{ $ticket->user->name ?? '-' }}</td>
                                        @endif
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $ticket->division->code ?? '-' }}</td>
                                        <td class="px-6 py-4 text-right text-sm font-medium text-gray-800">Rp {{ number_format($ticket->budget_estimated, 0, ',', '.') }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $ticket->status_color }}">
                                                {{ $ticket->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center text-xs text-gray-400">{{ $ticket->created_at->format('d M Y') }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <a href="{{ route('tickets.show', $ticket) }}" class="text-sm font-semibold text-bni-teal hover:underline">Detail →</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $tickets->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
