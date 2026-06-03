{{--
    tickets/index.blade.php
    STUB VIEW — Placeholder untuk test. Akan diganti dengan UI penuh setelah design.md selesai.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Daftar Ticket Pengadaan
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <a href="{{ route('tickets.create') }}" class="inline-block mb-4 px-4 py-2 bg-blue-600 text-white rounded">
                Ajukan Ticket Baru
            </a>

            @forelse ($tickets as $ticket)
                <div class="mb-2 p-4 bg-white rounded shadow">
                    <a href="{{ route('tickets.show', $ticket) }}">{{ $ticket->title }}</a>
                    <span class="ml-2 text-sm text-gray-500">{{ $ticket->status }}</span>
                    <span class="ml-2 text-sm text-gray-500">{{ $ticket->expenditure_type }}</span>
                </div>
            @empty
                <p class="text-gray-500">Belum ada ticket pengadaan.</p>
            @endforelse

        </div>
    </div>
</x-app-layout>
