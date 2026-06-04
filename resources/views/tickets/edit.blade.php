<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-gray-800 leading-tight">Revisi Tiket #{{ $ticket->id }}</h2>
            <p class="text-sm text-gray-500 mt-1">Perbaiki dokumen atau data sesuai catatan penolakan PFA</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            {{-- Catatan Penolakan PFA --}}
            @if($ticket->rejection_note)
                <div class="bg-red-50 border-l-4 border-red-500 rounded-r-xl p-4 mb-6">
                    <h4 class="text-sm font-bold text-red-800">Catatan Penolakan dari PFA</h4>
                    <p class="text-sm text-red-700 mt-1">{{ $ticket->rejection_note }}</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-6 md:p-8">
                <form method="POST" action="{{ route('tickets.update', $ticket) }}" enctype="multipart/form-data"
                      x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    @method('PUT')

                    <!-- Judul Pengadaan -->
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Judul Pengadaan <span class="text-rose-500">*</span></label>
                        <input type="text" id="title" name="title"
                               value="{{ old('title', $ticket->title) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-bni-teal focus:ring-bni-teal @error('title') border-red-400 ring-1 ring-red-400 @enderror"
                               required>
                        @error('title')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Deskripsi -->
                    <div class="mb-6">
                        <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi</label>
                        <textarea id="description" name="description" rows="4"
                                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-bni-teal focus:ring-bni-teal @error('description') border-red-400 ring-1 ring-red-400 @enderror">{{ old('description', $ticket->description) }}</textarea>
                        @error('description')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Vendor Name -->
                    <div class="mb-6">
                        <label for="vendor_name" class="block text-sm font-semibold text-gray-700 mb-2">Nama Vendor <span class="text-rose-500">*</span></label>
                        <input type="text" id="vendor_name" name="vendor_name"
                               value="{{ old('vendor_name', $ticket->vendor_name) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-bni-teal focus:ring-bni-teal @error('vendor_name') border-red-400 ring-1 ring-red-400 @enderror"
                               required>
                        @error('vendor_name')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Budget (read-only) -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Estimasi Anggaran</label>
                        <p class="text-lg font-bold text-gray-800">Rp {{ number_format($ticket->budget_estimated, 2, ',', '.') }}</p>
                        <p class="text-xs text-gray-400 mt-1">Estimasi anggaran tidak dapat diubah pada tahap revisi</p>
                    </div>

                    <!-- Re-upload Dokumen -->
                    <div class="mb-8">
                        <label for="document_path" class="block text-sm font-semibold text-gray-700 mb-2">
                            Upload Ulang Dokumen (PDF)
                            <span class="text-gray-400 text-xs font-normal">(Kosongkan jika tidak ingin mengganti)</span>
                        </label>
                        @if($ticket->document_path)
                            <p class="text-xs text-gray-500 mb-2">
                                Dokumen saat ini:
                                <a href="{{ $ticket->document_path }}" target="_blank" class="text-bni-teal font-semibold hover:underline">Lihat PDF →</a>
                            </p>
                        @endif
                        <input type="file" id="document_path" name="document_path" accept="application/pdf"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-bni-teal file:bg-opacity-10 file:text-bni-teal hover:file:bg-opacity-20">
                        @error('document_path')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="flex items-center justify-end gap-4 border-t border-gray-100 pt-6">
                        <a href="{{ route('tickets.show', $ticket) }}"
                           class="px-5 py-2.5 text-sm font-semibold text-gray-600 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Batal
                        </a>
                        <button type="submit" :disabled="loading"
                                style="background-color: #005E6A;"
                                class="inline-flex items-center px-6 py-2.5 text-white font-bold text-sm rounded-lg shadow-md hover:opacity-90 transition-all disabled:opacity-50 gap-2">
                            <span x-text="loading ? 'Mengirim...' : 'Kirim Revisi'">Kirim Revisi</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
