<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// ── Authenticated Routes ─────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // ── Profile (Breeze bawaan) ──────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Shared: Semua role bisa lihat daftar & detail tiket ──────────────────
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

    // ── Staff Routes ─────────────────────────────────────────────────────────
    Route::middleware('role:staff')->group(function () {
        Route::get('/tickets-create', [TicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
        Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
        Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
        Route::post('/tickets/{ticket}/validate', [TicketController::class, 'runValidation'])->name('tickets.validate');
    });

    // ── PFA Routes ───────────────────────────────────────────────────────────
    Route::middleware('role:pfa')->group(function () {
        Route::post('/tickets/{ticket}/review-approve', [TicketController::class, 'reviewApprove'])->name('tickets.review-approve');
        Route::post('/tickets/{ticket}/review-reject', [TicketController::class, 'reviewReject'])->name('tickets.review-reject');
        Route::post('/tickets/{ticket}/generate-po', [TicketController::class, 'generatePO'])->name('tickets.generate-po');
    });

    // ── Head Department Routes ───────────────────────────────────────────────
    Route::middleware('role:head_dept')->group(function () {
        Route::post('/tickets/{ticket}/forward', [TicketController::class, 'forward'])->name('tickets.forward');
    });

    // ── Head Division Routes ─────────────────────────────────────────────────
    Route::middleware('role:head_div')->group(function () {
        Route::post('/tickets/{ticket}/approve', [TicketController::class, 'approve'])->name('tickets.approve');
        Route::post('/tickets/{ticket}/decline', [TicketController::class, 'decline'])->name('tickets.decline');
    });
});

require __DIR__.'/auth.php';
