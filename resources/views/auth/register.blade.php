<x-guest-layout>
    <div class="mb-4 text-center">
        <h2 class="text-lg font-bold text-gray-800">Registrasi E-Procurement BNI</h2>
        <p class="text-xs text-gray-500 mt-1">Gunakan NIP dan email korporat Anda yang terdaftar di database HR</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- NIP (Nomor Induk Pegawai) -->
        <div>
            <x-input-label for="nip" value="Nomor Induk Pegawai (NIP)" />
            <x-text-input id="nip" class="block mt-1 w-full" type="text" name="nip" :value="old('nip')" required autofocus autocomplete="off" placeholder="Contoh: BNI-2024-001" />
            <x-input-error :messages="$errors->get('nip')" class="mt-2" />
        </div>

        <!-- Name -->
        <div class="mt-4">
            <x-input-label for="name" value="Nama Lengkap" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autocomplete="name" placeholder="Sesuai data karyawan" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" value="Email Korporat" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="nama@bni.co.id" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Konfirmasi Password" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md" href="{{ route('login') }}">
                Sudah punya akun?
            </a>

            <x-primary-button class="ms-4" style="background-color: #005E6A;">
                Daftar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
