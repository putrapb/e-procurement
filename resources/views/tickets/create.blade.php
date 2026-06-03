{{--
    tickets/create.blade.php
    STUB VIEW — Placeholder untuk test. Akan diganti dengan UI penuh setelah design.md selesai.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ajukan Ticket Pengadaan Baru
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                        <ul class="list-disc pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('tickets.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="title" class="block font-medium text-sm text-gray-700">Judul Pengadaan</label>
                        <input type="text" id="title" name="title"
                               value="{{ old('title') }}"
                               class="mt-1 block w-full rounded border-gray-300" required>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block font-medium text-sm text-gray-700">Deskripsi</label>
                        <textarea id="description" name="description"
                                  class="mt-1 block w-full rounded border-gray-300">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label for="budget_estimated" class="block font-medium text-sm text-gray-700">Estimasi Anggaran (Rp)</label>
                        <input type="number" id="budget_estimated" name="budget_estimated"
                               value="{{ old('budget_estimated') }}"
                               step="0.01" min="1"
                               class="mt-1 block w-full rounded border-gray-300" required>
                    </div>

                    <div class="mb-4">
                        <label for="vendor_name" class="block font-medium text-sm text-gray-700">Nama Vendor</label>
                        <input type="text" id="vendor_name" name="vendor_name"
                               value="{{ old('vendor_name') }}"
                               class="mt-1 block w-full rounded border-gray-300" required>
                    </div>

                    <div class="mb-4">
                        <label for="document_path" class="block font-medium text-sm text-gray-700">
                            Path Dokumen Izin Prinsip (S3 URL)
                            <span class="text-gray-400 text-xs">(Opsional — jika kosong, ticket masuk status draft)</span>
                        </label>
                        <input type="text" id="document_path" name="document_path"
                               value="{{ old('document_path') }}"
                               class="mt-1 block w-full rounded border-gray-300">
                    </div>

                    <div class="flex items-center gap-4">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
                            Ajukan Ticket
                        </button>
                        <a href="{{ route('tickets.index') }}" class="text-gray-600">Batal</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
