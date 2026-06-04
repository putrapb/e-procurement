<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * Flow:
     * 1. Validasi format input (NIP, email, password)
     * 2. Cross-check NIP + email terhadap tabel employees (database HR)
     * 3. Jika cocok → buat akun user dengan role & division dari HR
     * 4. Tandai employee sebagai sudah terdaftar
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nip'      => ['required', 'string', 'max:20'],
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // ── Cross-check terhadap database HR (tabel employees) ───────────
        $employee = Employee::where('nip', $request->nip)
                            ->where('email', $request->email)
                            ->first();

        if (! $employee) {
            throw ValidationException::withMessages([
                'nip' => ['NIP dan Email tidak ditemukan dalam database karyawan BNI. Pastikan Anda menggunakan NIP dan email korporat yang terdaftar.'],
            ]);
        }

        if ($employee->is_registered) {
            throw ValidationException::withMessages([
                'nip' => ['NIP ini sudah terdaftar di sistem E-Procurement. Silakan login dengan akun yang sudah ada.'],
            ]);
        }

        // ── Buat akun user dengan data dari HR ───────────────────────────
        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'role'        => $employee->role,
            'employee_id' => $employee->id,
            'position'    => $employee->position,
            'division_id' => $employee->division_id,
        ]);

        // Tandai karyawan sebagai sudah registrasi
        $employee->update(['is_registered' => true]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
