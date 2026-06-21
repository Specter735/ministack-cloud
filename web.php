<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StoragePageController;
use App\Http\Controllers\CredentialPageController;
use App\Http\Controllers\Admin\AdminPaymentController;

// halaman utama ke dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// halaman yang hanya bisa diaksesjika user sudah Login
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/storage', [StoragePageController::class, 'index'])->name('storage.index');
    Route::get('/credentials', [CredentialPageController::class, 'index'])->name('credentials.index');
    Route::post('/storage/checkout', [StoragePageController::class, 'checkout'])->name('storage.checkout');
});

// halaman khusus Administrator (acc pembayaran pending)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{payment}/verify', [AdminPaymentController::class, 'verify'])->name('payments.verify');
});

// memanggil semua rute otomatis dari file auth
require __DIR__.'/auth.php';