<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HargaJamMainController;
use App\Http\Controllers\Admin\JamMainController;
use App\Http\Controllers\Admin\MejaController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');

Route::middleware('pengguna.auth')->group(function () {
    Route::get('/booking', [PageController::class, 'booking'])->name('booking');
    Route::post('/booking', [PageController::class, 'storeBooking'])->name('booking.store');
    Route::get('/riwayat', [PageController::class, 'riwayat'])->name('riwayat');
    Route::get('/profil', [PageController::class, 'profil'])->name('profil');
    Route::post('/profil/keluar', [AuthController::class, 'logout'])->name('profil.logout');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('mejas', MejaController::class)->except(['create', 'edit', 'show']);
    Route::resource('jam-mains', JamMainController::class)->except(['create', 'edit', 'show']);
    Route::resource('harga-jam-mains', HargaJamMainController::class)->except(['create', 'edit', 'show']);
    Route::resource('admins', AdminController::class)->except(['create', 'edit', 'show']);
    Route::patch('bookings/{booking}/approve', [BookingController::class, 'approve'])->name('bookings.approve');
    Route::patch('bookings/{booking}/reject', [BookingController::class, 'reject'])->name('bookings.reject');
    Route::resource('bookings', BookingController::class)->only(['index', 'destroy']);
    Route::patch('penggunas/{pengguna}/status', [PenggunaController::class, 'updateStatus'])->name('penggunas.status');
    Route::resource('penggunas', PenggunaController::class)->only(['index', 'destroy']);
});
