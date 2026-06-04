<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // ── Profile (Breeze bawaan) ────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Tickets (E-Procurement 4-Gate Engine) ─────────────────────────────
    // Mendaftarkan seluruh resourceful routes untuk manajemen tiket pengadaan.
    Route::resource('tickets', TicketController::class);

    // Approval & Rejection (Aksi Admin)
    Route::post('/tickets/{ticket}/approve', [TicketController::class, 'approve'])->name('tickets.approve');
    Route::post('/tickets/{ticket}/reject', [TicketController::class, 'reject'])->name('tickets.reject');
});

require __DIR__.'/auth.php';

