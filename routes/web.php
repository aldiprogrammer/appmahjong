<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HargaJamMainController;
use App\Http\Controllers\Admin\JamMainController;
use App\Http\Controllers\Admin\KasirController;
use App\Http\Controllers\Admin\KategoriMenuController;
use App\Http\Controllers\Admin\MejaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Admin\SettingBookingRoomController;
use App\Http\Controllers\Admin\StatusVoucherController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\GoogleAuthController;
use App\Http\Controllers\User\PageController;
use App\Http\Controllers\User\PesananController;
use App\Http\Controllers\User\PointController;
use App\Http\Controllers\User\RankingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'landing'])->name('landing');
Route::get('/home', [PageController::class, 'home'])->name('home');
Route::get('/pesan', [PageController::class, 'pesan'])->name('pesan');
Route::post('/pesan', [PesananController::class, 'store'])->name('pesan.store');
Route::get('/aturan', [PageController::class, 'aturan'])->name('aturan');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::middleware('pengguna.auth')->group(function () {
    Route::get('/booking', [PageController::class, 'booking'])->name('booking');
    Route::post('/booking', [PageController::class, 'storeBooking'])->name('booking.store');
    Route::get('/room-booking', [PageController::class, 'roomBooking'])->name('room.booking');
    Route::post('/room-booking/{kodeRoom}/join', [PageController::class, 'joinRoomBooking'])->name('room.booking.join');
    Route::get('/booking/pembayaran/{kodePembayaran}', [PageController::class, 'pembayaran'])->name('booking.pembayaran');
    Route::post('/booking/pembayaran/{kodePembayaran}', [PageController::class, 'storePembayaran'])->name('booking.pembayaran.store');
    Route::delete('/booking/pembayaran/{kodePembayaran}/sesi/{booking}', [PageController::class, 'destroyPembayaranSesi'])->name('booking.pembayaran.sesi.destroy');
    Route::get('/riwayat', [PageController::class, 'riwayat'])->name('riwayat');
    Route::get('/riwayat/pesanan', [PesananController::class, 'riwayat'])->name('riwayat.pesanan');
    Route::get('/voucher', [PageController::class, 'voucher'])->name('voucher');
    Route::get('/ranking', [RankingController::class, 'index'])->name('ranking');
    Route::delete('/riwayat/{booking}', [PageController::class, 'destroyRiwayat'])->name('riwayat.destroy');
    Route::get('/riwayat/{booking}/tiket', [PageController::class, 'downloadTiket'])->name('riwayat.tiket');
    Route::get('/riwayat/{booking}/download', [PageController::class, 'downloadTiket'])->name('riwayat.download');
    Route::get('/profil', [PageController::class, 'profil'])->name('profil');
    Route::post('/profil/whatsapp', [PageController::class, 'updateNowa'])->name('profil.nowa');
    Route::post('/profil/keluar', [AuthController::class, 'logout'])->name('profil.logout');
    Route::get('/point', [PointController::class, 'index'])->name('point');
    Route::post('/point/scan', [PointController::class, 'scan'])->name('point.scan');
    Route::post('/point/scan/{scan}/kartu', [PointController::class, 'perbaruiKartu'])->name('point.kartu');
    Route::post('/point/scan/{scan}/konfirmasi', [PointController::class, 'konfirmasi'])->name('point.konfirmasi');
    Route::delete('/point/scan/{scan}', [PointController::class, 'batalkan'])->name('point.batalkan');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.store');

    Route::middleware('admin.auth')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/kasir', [KasirController::class, 'index'])->name('kasir.index');
        Route::get('/kasir/notifikasi', [KasirController::class, 'notifikasi'])->name('kasir.notifikasi');
        Route::patch('/kasir/{pesanan}/status', [KasirController::class, 'updateStatus'])->name('kasir.status');
        Route::resource('mejas', MejaController::class)->except(['create', 'edit', 'show']);
        Route::resource('jam-mains', JamMainController::class)->except(['create', 'edit', 'show']);
        Route::resource('harga-jam-mains', HargaJamMainController::class)->except(['create', 'edit', 'show']);
        Route::resource('kategori-menus', KategoriMenuController::class)->except(['create', 'edit', 'show']);
        Route::patch('menus/{menu}/status', [MenuController::class, 'updateStatus'])->name('menus.status');
        Route::resource('menus', MenuController::class)->except(['create', 'edit', 'show']);
        Route::resource('admins', AdminController::class)->except(['create', 'edit', 'show']);
        Route::patch('bookings/{booking}/approve', [BookingController::class, 'approve'])->name('bookings.approve');
        Route::patch('bookings/{booking}/reject', [BookingController::class, 'reject'])->name('bookings.reject');
        Route::patch('bookings/{booking}/verifikasi-pembayaran', [BookingController::class, 'verifikasiPembayaran'])->name('bookings.verifikasi-pembayaran');
        Route::resource('bookings', BookingController::class)->only(['index', 'destroy']);
        Route::patch('penggunas/{pengguna}/status', [PenggunaController::class, 'updateStatus'])->name('penggunas.status');
        Route::resource('penggunas', PenggunaController::class)->only(['index', 'destroy']);
        Route::resource('status-vouchers', StatusVoucherController::class)->except(['create', 'edit', 'show']);
        Route::patch('setting-booking-rooms/{settingBookingRoom}/status', [SettingBookingRoomController::class, 'updateStatus'])->name('setting-booking-rooms.status');
        Route::resource('setting-booking-rooms', SettingBookingRoomController::class)->except(['create', 'edit', 'show']);
        Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::get('/vouchers/{voucher}', [VoucherController::class, 'show'])->name('vouchers.show');
        Route::patch('/vouchers/{voucher}/status', [VoucherController::class, 'updateStatus'])->name('vouchers.status');
        Route::delete('/vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
    });
});
