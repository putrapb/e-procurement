<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                Ajukan Pengadaan Baru
            </h2>
            <p class="text-sm text-gray-500 mt-1">Formulir Pengajuan Tiket E-Procurement Divisi BNI</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Panduan Gate Validasi -->
            <div class="bg-blue-50 border-l-4 border-blue-600 rounded-r-xl p-4 mb-6 shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0 text-blue-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-bold text-blue-800">Panduan Klasifikasi Anggaran Korporat (Gate 2)</h4>
                        <p class="text-xs text-blue-700 mt-1 leading-relaxed">
                            Sistem E-Procurement BNI mengklasifikasikan anggaran secara otomatis:
                            <br>• <strong>CAPEX (Capital Expenditure):</strong> Nilai pengadaan &ge; Rp 500.000.000 atau mengandung kata kunci aset tetap (seperti: <em>server, hardware, infrastruktur, gedung, jaringan, network</em>).
                            <br>• <strong>OPEX (Operational Expenditure):</strong> Pengadaan rutin di bawah threshold (seperti: ATK, pemeliharaan rutin, lisensi bulanan).
                        </p>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-6 md:p-8">
                
                <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" 
                      x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    <!-- Judul Pengadaan -->
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Judul Pengadaan <span class="text-rose-500">*</span></label>
                        <input type="text" id="title" name="title"
                               value="{{ old('title') }}"
                               placeholder="Contoh: Pengadaan Server Data Center IT Core"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-bni-teal focus:ring-bni-teal transition-colors @error('title') border-red-400 ring-1 ring-red-400 @enderror"
                               required>
                        @error('title')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Deskripsi -->
                    <div class="mb-6">
                        <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi Spesifikasi & Kebutuhan</label>
                        <textarea id="description" name="description" rows="4"
                                  placeholder="Rincian spesifikasi teknis barang/jasa yang diajukan..."
                                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-bni-teal focus:ring-bni-teal transition-colors @error('description') border-red-400 ring-1 ring-red-400 @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Estimasi Anggaran & Nama Vendor -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Estimasi Anggaran -->
                        <div>
                            <label for="budget_estimated" class="block text-sm font-semibold text-gray-700 mb-2">Estimasi Anggaran (Nominal Rupiah) <span class="text-rose-500">*</span></label>
                            <div class="relative mt-1 rounded-lg shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 text-sm font-medium">Rp</span>
                                </div>
                                <input type="number" id="budget_estimated" name="budget_estimated"
                                       value="{{ old('budget_estimated') }}"
                                       step="0.01" min="1"
                                       placeholder="150000000"
                                       class="block w-full rounded-lg border-gray-300 pl-10 focus:border-bni-teal focus:ring-bni-teal transition-colors @error('budget_estimated') border-red-400 ring-1 ring-red-400 @enderror"
                                       required>
                            </div>
                            @error('budget_estimated')
                                <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Nama Vendor -->
                        <div>
                            <label for="vendor_name" class="block text-sm font-semibold text-gray-700 mb-2">Nama Vendor / Penyedia Jasa <span class="text-rose-500">*</span></label>
                            <input type="text" id="vendor_name" name="vendor_name"
                                   value="{{ old('vendor_name') }}"
                                   placeholder="Contoh: PT Enterprise System Utama"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-bni-teal focus:ring-bni-teal transition-colors @error('vendor_name') border-red-400 ring-1 ring-red-400 @enderror"
                                   required>
                            @error('vendor_name')
                                <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Dokumen Izin Prinsip -->
                    <div class="mb-8">
                        <label for="document_path" class="block text-sm font-semibold text-gray-700 mb-2">
                            Dokumen Izin Prinsip (PDF)
                            <span class="text-gray-400 text-xs font-normal">(Format PDF, Maks 10MB)</span>
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-bni-teal transition-colors @error('document_path') border-red-300 @enderror">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20a4 4 0 004 4h24a4 4 0 004-4V20L28 8z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M28 8v12h12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="document_path" class="relative cursor-pointer bg-white rounded-md font-semibold text-bni-teal hover:text-opacity-80 focus-within:outline-none">
                                        <span>Unggah berkas PDF</span>
                                        <input id="document_path" name="document_path" type="file" accept="application/pdf" class="sr-only">
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500">Izin Prinsip yang telah ditandatangani pemimpin divisi</p>
                                <p class="text-xs text-gray-400 italic font-semibold mt-1">Jika dikosongkan, pengajuan akan disimpan dengan status 'Draft'</p>
                            </div>
                        </div>
                        
                        <!-- Interaktivitas Alpine.js untuk menampilkan nama file terpilih -->
                        <div x-data="{ fileName: '' }" class="mt-2 text-center text-xs font-semibold text-gray-600">
                            <input type="file" id="dummy_uploader" class="hidden" @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''">
                            <span x-text="fileName ? 'File terpilih: ' + fileName : ''"></span>
                        </div>

                        @error('document_path')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="flex items-center justify-end gap-4 border-t border-gray-100 pt-6">
                        <a href="{{ route('tickets.index') }}" 
                           :class="loading ? 'pointer-events-none opacity-50' : ''"
                           class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-gray-900 transition-colors">
                            Batal
                        </a>
                        
                        <button type="submit" 
                                :disabled="loading"
                                class="inline-flex items-center px-5 py-2.5 bg-bni-teal text-white font-semibold text-sm rounded-lg shadow-sm hover:bg-opacity-90 focus:outline-none transition-all disabled:opacity-50 disabled:cursor-not-allowed gap-2"
                                :class="loading ? 'bg-opacity-50' : ''">
                            <!-- Spinner -->
                            <svg x-show="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" style="display: none;">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="loading ? 'Mengunggah Izin Prinsip ke S3...' : 'Ajukan Pengadaan'">Ajukan Pengadaan</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
    
    <!-- Script pembantu Alpine.js untuk menampilkan file name upload di atas -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const realUploader = document.getElementById('document_path');
            const dummySpan = document.querySelector('[x-text^="fileName"]');
            
            realUploader.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    const name = e.target.files[0].name;
                    const sizeMB = (e.target.files[0].size / (1024 * 1024)).toFixed(2);
                    dummySpan.textContent = `File terpilih: ${name} (${sizeMB} MB)`;
                    dummySpan.classList.remove('hidden');
                } else {
                    dummySpan.textContent = '';
                }
            });
        });
    </script>
</x-app-layout>
