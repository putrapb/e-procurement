<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>E-Procurement BNI - Portal Pengadaan TI</title>

        <!-- Google Fonts: Inter & Outfit -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body {
                font-family: 'Inter', sans-serif;
            }
            .font-outfit {
                font-family: 'Outfit', sans-serif;
            }
            .text-bni-teal {
                color: #005E6A;
            }
            .bg-bni-teal {
                background-color: #005E6A;
            }
            .border-bni-teal {
                border-color: #005E6A;
            }
            .text-bni-orange {
                color: #F15A24;
            }
            .bg-bni-orange {
                background-color: #F15A24;
            }
            .hover-bni-teal:hover {
                background-color: #004b55;
            }
            .hover-bni-orange:hover {
                background-color: #d8481b;
            }
        </style>
    </head>
    <body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col justify-between">

        <!-- Header / Navigation -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    <!-- BNI Brand Logo -->
                    <div class="flex items-center gap-3">
                        <!-- Custom BNI Logo representation -->
                        <div class="h-10 w-10 bg-bni-teal rounded-xl flex items-center justify-center text-white font-outfit font-black text-xl shadow-md">
                            B
                        </div>
                        <div>
                            <span class="font-outfit font-black text-2xl tracking-tight text-bni-teal">e-Procurement</span>
                            <span class="text-bni-orange font-outfit font-bold text-sm block -mt-1 tracking-wider">DIVISI TEKNOLOGI INFORMASI</span>
                        </div>
                    </div>

                    <!-- Auth Links -->
                    <div class="flex items-center gap-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" 
                               class="inline-flex items-center justify-center px-5 py-2.5 bg-bni-teal hover-bni-teal text-white font-bold text-sm rounded-xl transition-all shadow-sm">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" 
                               class="text-sm font-bold text-slate-600 hover:text-bni-teal transition-colors">
                                Masuk (Login)
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" 
                                   class="inline-flex items-center justify-center px-5 py-2.5 bg-bni-orange hover-bni-orange text-white font-bold text-sm rounded-xl transition-all shadow-sm">
                                    Daftar Akun Baru
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Hero Section -->
        <main class="flex-grow">
            <!-- Hero -->
            <div class="relative overflow-hidden bg-white border-b border-slate-200 py-16 lg:py-24">
                <!-- Background decor -->
                <div class="absolute inset-y-0 right-0 w-1/2 bg-slate-50 rounded-l-[100px] hidden lg:block -z-10"></div>
                <div class="absolute top-20 left-10 w-72 h-72 bg-teal-50 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-pulse -z-10"></div>
                <div class="absolute bottom-10 right-20 w-80 h-80 bg-orange-50 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-pulse -z-10"></div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                        <div class="lg:col-span-7 space-y-6">
                            <div class="inline-flex items-center gap-2 px-3 py-1 bg-teal-50 text-bni-teal font-bold text-xs rounded-full border border-teal-100 uppercase tracking-wider">
                                ⚡ E-Procurement BNI v2.0
                            </div>
                            
                            <h1 class="font-outfit font-black text-4xl sm:text-5xl lg:text-6xl text-slate-900 leading-tight">
                                Transparansi & Kecepatan Pengadaan <span class="text-bni-teal">Divisi IT BNI</span>
                            </h1>
                            
                            <p class="text-slate-600 text-lg sm:text-xl leading-relaxed max-w-2xl">
                                Selamat datang di portal resmi E-Procurement BNI khusus untuk unit pegawai ITFM. Sistem ini dirancang untuk orkestrasi alur pengadaan barang dan jasa secara aman, cepat, dan transparan melalui integrasi 4-Gate Smart Validation Engine.
                            </p>

                            <div class="flex flex-col sm:flex-row gap-4 pt-4">
                                @auth
                                    <a href="{{ url('/dashboard') }}" 
                                       class="inline-flex items-center justify-center px-8 py-4 bg-bni-teal hover-bni-teal text-white font-bold rounded-xl transition-all shadow-md text-base text-center">
                                        Pergi ke Dashboard
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" 
                                       class="inline-flex items-center justify-center px-8 py-4 bg-bni-teal hover-bni-teal text-white font-bold rounded-xl transition-all shadow-md text-base text-center">
                                        Masuk ke Aplikasi
                                    </a>
                                    <a href="{{ route('register') }}" 
                                       class="inline-flex items-center justify-center px-8 py-4 bg-white border border-slate-300 hover:border-slate-400 text-slate-700 font-bold rounded-xl transition-all shadow-sm text-base text-center">
                                        Registrasi Karyawan ITFM
                                    </a>
                                @endif
                            </div>

                            <!-- Budget Stat -->
                            <div class="pt-6 border-t border-slate-100 flex items-center gap-8">
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Pagu Bersama IT</p>
                                    <p class="text-3xl font-extrabold text-slate-900 mt-1 font-outfit">Rp 14 Miliar</p>
                                </div>
                                <div class="h-10 w-px bg-slate-200"></div>
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Cakupan Pengadaan</p>
                                    <p class="text-3xl font-extrabold text-bni-orange mt-1 font-outfit">Capex & Opex</p>
                                </div>
                            </div>
                        </div>

                        <!-- Side Pipeline Display -->
                        <div class="lg:col-span-5 bg-white border border-slate-200 shadow-xl rounded-2xl p-8 space-y-6">
                            <h3 class="font-outfit font-extrabold text-xl text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2 text-bni-teal">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                Approval Pipeline
                            </h3>
                            
                            <div class="space-y-4">
                                <div class="flex gap-4">
                                    <div class="h-8 w-8 bg-slate-100 rounded-full flex items-center justify-center font-bold text-xs text-slate-600 shrink-0">1</div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm">Staff Submission</h4>
                                        <p class="text-xs text-slate-500 mt-0.5">Staff ITFM mengajukan tiket pengadaan disertai dokumen Izin Prinsip PDF.</p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <div class="h-8 w-8 bg-slate-100 rounded-full flex items-center justify-center font-bold text-xs text-slate-600 shrink-0">2</div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm">PFA Document Review</h4>
                                        <p class="text-xs text-slate-500 mt-0.5">Petugas PFA meninjau keabsahan dokumen. Dapat menyetujui atau menolak revisi.</p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <div class="h-8 w-8 bg-slate-100 rounded-full flex items-center justify-center font-bold text-xs text-slate-600 shrink-0">3</div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm">4-Gate Smart Validation</h4>
                                        <p class="text-xs text-slate-500 mt-0.5">Sistem memverifikasi kecukupan sisa pagu, mengklasifikasi pengeluaran secara cerdas, dan memvalidasi kelayakan.</p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <div class="h-8 w-8 bg-slate-100 rounded-full flex items-center justify-center font-bold text-xs text-slate-600 shrink-0">4</div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm">Corporate Approval Chain</h4>
                                        <p class="text-xs text-slate-500 mt-0.5">Tiket diteruskan oleh Head Department dan diputuskan secara final oleh Head Division IT (Pagu dikunci secara atomik).</p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <div class="h-8 w-8 bg-slate-100 rounded-full flex items-center justify-center font-bold text-xs text-slate-600 shrink-0">5</div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm">PO PDF Generation</h4>
                                        <p class="text-xs text-slate-500 mt-0.5">PFA men-generate nomor PO unik dan mengunduh berkas PDF Purchase Order resmi.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features Cards Grid -->
            <div class="py-16 bg-slate-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                    <div class="text-center space-y-4">
                        <h2 class="font-outfit font-extrabold text-3xl text-slate-900">Keunggulan 4-Gate Validation Engine</h2>
                        <p class="text-slate-600 max-w-xl mx-auto text-base">Mesin validasi pintar yang menjaga pengadaan tetap dalam koridor regulasi BNI secara otomatis.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <!-- Gate 1 Card -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                            <div class="h-10 w-10 bg-teal-50 text-bni-teal rounded-xl flex items-center justify-center mb-4">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h4 class="font-bold text-slate-800 text-sm">Gate 1: Budget Limit</h4>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">Melakukan pemeriksaan kecukupan anggaran divisi secara real-time dan mengunci pagu secara atomik pada saat persetujuan akhir.</p>
                        </div>

                        <!-- Gate 2 Card -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                            <div class="h-10 w-10 bg-orange-50 text-bni-orange rounded-xl flex items-center justify-center mb-4">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <h4 class="font-bold text-slate-800 text-sm">Gate 2: CAPEX/OPEX</h4>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">Klasifikasi otomatis pengeluaran barang modal (CAPEX) atau pengeluaran operasional (OPEX) berdasarkan nominal transaksi dan kata kunci judul pengadaan.</p>
                        </div>

                        <!-- Gate 3 Card -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                            <div class="h-10 w-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center mb-4">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <h4 class="font-bold text-slate-800 text-sm">Gate 3: Eligibility</h4>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">Pemeriksaan integritas profil requestor dan validitas vendor penyedia barang/jasa eksternal sebelum diajukan ke jajaran manajemen.</p>
                        </div>

                        <!-- Gate 4 Card -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                            <div class="h-10 w-10 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center mb-4">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h4 class="font-bold text-slate-800 text-sm">Gate 4: Document Complete</h4>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">Verifikasi kelengkapan administrasi berupa file PDF Izin Prinsip pengadaan. Dokumen tersimpan aman di media penyimpanan server.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-slate-500 font-medium">
                    &copy; 2026 PT Bank Negara Indonesia (Persero) Tbk. All rights reserved.
                </p>
                <div class="flex items-center gap-6">
                    <span class="text-xs font-bold text-slate-400">Portal Pengadaan Internal IT</span>
                    <span class="text-xs text-slate-400">Laravel v{{ app()->version() }} &bull; PHP v{{ PHP_VERSION }}</span>
                </div>
            </div>
        </footer>

    </body>
</html>
