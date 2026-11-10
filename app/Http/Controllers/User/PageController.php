<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\hargajammain;
use App\Models\jam_main;
use App\Models\Kategorimenu;
use App\Models\Meja;
use App\Models\Menu;
use App\Models\Pengguna;
use App\Models\Settingbookingroom;
use App\Models\Statusvoucher;
use App\Models\Voucher;
use App\Services\BuktiPembayaranService;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function landing(Request $request): Response
    {
        return Inertia::render('User/Landing', [
            'pengguna' => $this->currentPengguna($request),
            'mejas' => Meja::query()->orderBy('nama_meja')->take(4)->get(['id', 'kode_meja', 'nama_meja']),
            'hargas' => hargajammain::query()
                ->orderBy('hari')
                ->orderBy('harga')
                ->take(4)
                ->get(),
        ]);
    }

    public function home(Request $request): Response
    {
        $pengguna = $this->currentPengguna($request);

        return Inertia::render('User/Home', [
            'pengguna' => $pengguna,
            'mejas' => Meja::query()->orderBy('nama_meja')->take(4)->get(['id', 'kode_meja', 'nama_meja']),
            'hargas' => hargajammain::query()
                ->orderBy('hari')
                ->orderBy('harga')
                ->take(4)
                ->get(),
        ]);
    }

    public function pesan(Request $request): Response
    {
        $kategoriMenus = Kategorimenu::query()
            ->whereHas('menus', fn ($query) => $query->where('status', 'aktif'))
            ->orderBy('kategori')
            ->get(['id', 'kategori']);

        $menus = Menu::query()
            ->with('kategori:id,kategori')
            ->where('status', 'aktif')
            ->orderBy('nama')
            ->get()
            ->map(fn (Menu $menu): array => [
                'id' => $menu->id,
                'nama' => $menu->nama,
                'harga' => (int) $menu->harga,
                'kategori_id' => (string) $menu->id_kategori,
                'kategori' => $menu->kategori?->kategori,
                'gambar_url' => $menu->gambar_url,
                'dibuat_di' => $menu->dibuat_di,
                'harga_dingin' => $menu->harga_dingin !== null ? (int) $menu->harga_dingin : null,
                'harga_panas' => $menu->harga_panas !== null ? (int) $menu->harga_panas : null,
            ]);

        return Inertia::render('User/Pesan', [
            'pengguna' => $this->currentPengguna($request),
            'kategoriMenus' => $kategoriMenus,
            'menus' => $menus,
        ]);
    }

    public function aturan(Request $request): Response
    {
        return Inertia::render('User/Aturan', [
            'pengguna' => $this->currentPengguna($request),
        ]);
    }

    public function booking(Request $request): Response
    {
        $pengguna = $this->currentPengguna($request);

        return Inertia::render('User/Booking', [
            'pengguna' => $pengguna,
            'mejas' => Inertia::defer(fn () => Meja::query()->orderBy('nama_meja')->get(['id', 'kode_meja', 'nama_meja'])),
            'jamMains' => Inertia::defer(fn () => jam_main::query()
                ->where('status', 'aktif')
                ->orderBy('jam_mulai')
                ->get(['id', 'jam_mulai', 'jam_selesai', 'status'])),
            'hargas' => Inertia::defer(fn () => hargajammain::query()
                ->orderBy('hari')
                ->orderBy('harga')
                ->get(['id', 'jam_main_id', 'hari', 'harga', 'diskon', 'harga_akhir'])),
            'bookedSlots' => Inertia::defer(fn () => Booking::query()
                ->where('status_hapus', '!=', '1')
                ->where('status_booking', '!=', '400')
                ->where('tgl_main', '>=', Carbon::today()->toDateString())
                ->get(['id_meja', 'tgl_main', 'jam_mulai', 'jam_selesai'])),
            'settingBookingRooms' => Inertia::defer(fn () => Settingbookingroom::query()
                ->where('status', 'aktif')
                ->latest('id')
                ->get(['id', 'pembayaran', 'jumlah_user', 'minimal_user', 'maksimal_user'])),
            'vouchers' => Inertia::defer(function () use ($pengguna) {
                if (! $pengguna) {
                    return [];
                }

                return Voucher::query()
                    ->where('kode_user', $pengguna->kode_user)
                    ->where(function ($query) {
                        $query->whereNull('tanggal_digunakan')->orWhere('tanggal_digunakan', '');
                    })
                    ->get()
                    ->map(function (Voucher $voucher) {
                        $statusVoucher = Statusvoucher::query()->find((int) $voucher->id_statusvoucher);

                        return [
                            'id' => $voucher->id,
                            'kode_voucher' => $voucher->kode_voucher,
                            'tanggal_diterima' => $voucher->tanggal_diterima,
                            'tanggal_digunakan' => $voucher->tanggal_digunakan,
                            'jumah_jam' => $statusVoucher?->jumah_jam,
                        ];
                    })
                    ->values();
            }),
        ]);
    }

    public function storeBooking(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meja_id' => ['required', Rule::exists('mejas', 'id')],
            'jam_main_ids' => ['required', 'array', 'min:1'],
            'jam_main_ids.*' => ['required', 'distinct', Rule::exists('jam_mains', 'id')->where('status', 'aktif')],
            'tgl_main' => ['required', 'date', 'after_or_equal:today'],
            'kode_voucher' => ['nullable', 'string', 'max:50'],
            'tipe_booking' => ['nullable', 'string', Rule::in(['public', 'private'])],
        ]);

        $pengguna = $this->currentPengguna($request);

        if (! $pengguna) {
            return redirect()->guest(route('login'));
        }

        $validated['tipe_booking'] = $this->resolveTipeBooking($validated['tipe_booking'] ?? null);

        $settingBookingRoom = $this->settingBookingRoomAktif();

        $voucher = null;

        if (! empty($validated['kode_voucher'])) {
            $voucher = Voucher::query()
                ->where('kode_voucher', $validated['kode_voucher'])
                ->where('kode_user', $pengguna->kode_user)
                ->first();

            if (! $voucher || filled($voucher->tanggal_digunakan)) {
                throw ValidationException::withMessages([
                    'kode_voucher' => 'Voucher tidak valid atau sudah digunakan.',
                ]);
            }

            $statusVoucher = Statusvoucher::query()->find((int) $voucher->id_statusvoucher);

            if (! $statusVoucher || count($validated['jam_main_ids']) !== (int) $statusVoucher->jumah_jam) {
                throw ValidationException::withMessages([
                    'kode_voucher' => 'Jumlah jam yang Anda pilih kurang atau lebih dari jumlah jam yang sudah ditentukan untuk voucher ini.',
                ]);
            }
        }

        $meja = Meja::query()->findOrFail($validated['meja_id']);
        $tanggal = Carbon::parse($validated['tgl_main']);
        $hari = $this->hariIndonesia($tanggal);

        $jamMains = jam_main::query()
            ->whereIn('id', $validated['jam_main_ids'])
            ->where('status', 'aktif')
            ->get()
            ->keyBy(fn (jam_main $jamMain) => (string) $jamMain->id);

        $hargaPerJam = hargajammain::query()
            ->whereIn('jam_main_id', array_map('strval', $validated['jam_main_ids']))
            ->where('hari', $hari)
            ->get()
            ->keyBy(fn (hargajammain $harga) => (string) $harga->jam_main_id);

        do {
            $kodePembayaran = 'PAY-'.$tanggal->format('Ymd').'-'.strtoupper(Str::random(5));
        } while (Booking::query()->where('kode_pembayaran', $kodePembayaran)->exists());

        $kodeRoom = null;
        $kapasitasUser = null;
        $modePembayaran = null;

        if ($validated['tipe_booking'] === 'public') {
            $kapasitasUser = max(1, (int) ($settingBookingRoom?->jumlah_user ?? 1));
            $modePembayaran = strtolower((string) ($settingBookingRoom?->pembayaran ?? 'all'));

            do {
                $kodeRoom = 'ROOM-'.$tanggal->format('Ymd').'-'.strtoupper(Str::random(5));
            } while (Booking::query()->where('kode_room', $kodeRoom)->exists());
        }

        $kodeBookings = DB::transaction(function () use ($validated, $pengguna, $meja, $tanggal, $jamMains, $hargaPerJam, $kodePembayaran, $voucher, $settingBookingRoom, $kodeRoom, $kapasitasUser, $modePembayaran): array {
            $kodeBookings = [];
            $jumlahUser = max(1, (int) ($settingBookingRoom?->jumlah_user ?? 1));
            $bagiHarga = $validated['tipe_booking'] === 'public'
                && strtolower((string) ($settingBookingRoom?->pembayaran)) === 'all';

            foreach ($validated['jam_main_ids'] as $jamMainId) {
                $jamMain = $jamMains->get((string) $jamMainId);
                $harga = $hargaPerJam->get((string) $jamMainId);

                if (! $jamMain || ! $harga) {
                    throw ValidationException::withMessages([
                        'jam_main_ids' => 'Harga untuk hari dan jam yang dipilih belum tersedia.',
                    ]);
                }

                $idMeja = $meja->kode_meja ?: (string) $meja->id;
                $jamMulai = substr((string) $jamMain->jam_mulai, 0, 5);
                $jamSelesai = substr((string) $jamMain->jam_selesai, 0, 5);

                if ($tanggal->isToday() && $jamMulai <= Carbon::now()->format('H:i')) {
                    throw ValidationException::withMessages([
                        'jam_main_ids' => "Jam {$jamMulai} - {$jamSelesai} sudah lewat untuk hari ini.",
                    ]);
                }

                $sudahDibooking = Booking::query()
                    ->where('status_hapus', '!=', '1')
                    ->where('status_booking', '!=', '400')
                    ->where('id_meja', $idMeja)
                    ->where('tgl_main', $tanggal->toDateString())
                    ->where('jam_mulai', $jamMulai)
                    ->where('jam_selesai', $jamSelesai)
                    ->exists();

                if ($sudahDibooking) {
                    throw ValidationException::withMessages([
                        'jam_main_ids' => "Jam {$jamMulai} - {$jamSelesai} sudah terbooking untuk meja ini pada tanggal tersebut.",
                    ]);
                }

                do {
                    $kodeBooking = 'BK-'.$tanggal->format('Ymd').'-'.strtoupper(Str::random(4));
                } while (Booking::query()->where('kode_booking', $kodeBooking)->exists());

                $totalHarga = (string) $harga->harga_akhir;

                if ($bagiHarga) {
                    $totalHarga = (string) (int) round(((int) $harga->harga_akhir) / $jumlahUser);
                }

                Booking::create([
                    'kode_booking' => $kodeBooking,
                    'kode_pembayaran' => $kodePembayaran,
                    'kode_voucher' => $voucher ? $voucher->kode_voucher : null,
                    'tipe_booking' => $validated['tipe_booking'],
                    'kode_room' => $kodeRoom,
                    'kapasitas_user' => $kapasitasUser,
                    'mode_pembayaran' => $modePembayaran,
                    'id_pengguna' => (string) $pengguna->id,
                    'id_meja' => $idMeja,
                    'jam_mulai' => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                    'tgl_main' => $tanggal->toDateString(),
                    'tgl_booking' => Carbon::now()->toDateString(),
                    'sistem_pembayaran' => '',
                    'harga' => (string) $harga->harga,
                    'total_harga' => $voucher ? '0' : $totalHarga,
                    'bukti_pembayaran' => '',
                    'status_booking' => '0',
                    'status_pembayaran' => $voucher ? 'lunas' : 'belum_bayar',
                    'dibayar_pada' => $voucher ? now() : null,
                    'sistem_pembayaran' => $voucher ? 'voucher' : '',
                    'status_hapus' => '0',
                ]);

                $kodeBookings[] = $kodeBooking;
            }

            if ($voucher) {
                $voucher->update(['tanggal_digunakan' => now()->toDateString()]);
            }

            return $kodeBookings;
        });

        $jumlahSesi = count($kodeBookings);
        $pesan = $jumlahSesi === 1
            ? 'Booking berhasil dibuat. Lanjutkan ke pembayaran untuk mengunci jadwalmu.'
            : "{$jumlahSesi} sesi booking berhasil dibuat. Lanjutkan ke pembayaran untuk mengunci jadwalmu.";

        return redirect()
            ->route('booking.pembayaran', $kodePembayaran)
            ->with('success', $pesan);
    }

    public function roomBooking(Request $request): Response
    {
        $pengguna = $this->currentPengguna($request);

        $rooms = Booking::query()
            ->where('tipe_booking', 'public')
            ->whereNotNull('kode_room')
            ->where('status_hapus', '!=', '1')
            ->where('status_booking', '!=', '400')
            ->where('tgl_main', '>=', Carbon::today()->toDateString())
            ->with('pengguna:id,nama_depan,nama_balakang')
            ->orderBy('tgl_main')
            ->orderBy('jam_mulai')
            ->get()
            ->groupBy('kode_room')
            ->map(function ($group, string $kodeRoom) use ($pengguna): array {
                $pertama = $group->first();
                $anggota = $group->pluck('id_pengguna')->unique()->values();
                $kapasitas = max(1, (int) ($group->max('kapasitas_user') ?: $anggota->count()));
                $pemilik = $group->firstWhere('id_pengguna', $pertama->id_pengguna);

                $sesi = $group
                    ->unique(fn (Booking $booking): string => $booking->jam_mulai.'-'.$booking->jam_selesai)
                    ->sortBy('jam_mulai')
                    ->map(fn (Booking $booking): array => [
                        'jam_mulai' => substr((string) $booking->jam_mulai, 0, 5),
                        'jam_selesai' => substr((string) $booking->jam_selesai, 0, 5),
                    ])
                    ->values();

                $hargaPerUser = (int) $group
                    ->where('id_pengguna', $pertama->id_pengguna)
                    ->sum(fn (Booking $booking): int => (int) $booking->total_harga);

                $namaPemilik = $pemilik?->pengguna
                    ? trim($pemilik->pengguna->nama_depan.' '.$pemilik->pengguna->nama_balakang)
                    : '-';

                return [
                    'kode_room' => $kodeRoom,
                    'id_meja' => $pertama->id_meja,
                    'tgl_main' => $pertama->tgl_main,
                    'pemilik' => $namaPemilik,
                    'sesi' => $sesi,
                    'kapasitas' => $kapasitas,
                    'jumlah_anggota' => $anggota->count(),
                    'sisa_slot' => max(0, $kapasitas - $anggota->count()),
                    'harga_per_user' => $hargaPerUser,
                    'mode_pembayaran' => $this->modePembayaranRoom($pertama),
                    'is_pemilik' => $pengguna
                        ? (string) $pertama->id_pengguna === (string) $pengguna->id
                        : false,
                    'sudah_join' => $pengguna ? $anggota->contains((string) $pengguna->id) : false,
                ];
            })
            ->values();

        return Inertia::render('User/RoomBooking', [
            'pengguna' => $pengguna,
            'rooms' => $rooms,
        ]);
    }

    public function joinRoomBooking(Request $request, string $kodeRoom): RedirectResponse
    {
        $pengguna = $this->currentPengguna($request);

        if (! $pengguna) {
            return redirect()->guest(route('login'));
        }

        $roomBookings = Booking::query()
            ->where('kode_room', $kodeRoom)
            ->where('tipe_booking', 'public')
            ->where('status_hapus', '!=', '1')
            ->where('status_booking', '!=', '400')
            ->where('tgl_main', '>=', Carbon::today()->toDateString())
            ->orderBy('jam_mulai')
            ->get();

        if ($roomBookings->isEmpty()) {
            return back()->with('error', 'Room booking tidak ditemukan atau sudah tidak tersedia.');
        }

        $idPengguna = (string) $pengguna->id;
        $anggota = $roomBookings->pluck('id_pengguna')->unique()->values();

        if ($anggota->contains($idPengguna)) {
            return back()->with('error', 'Anda sudah bergabung di room ini.');
        }

        $kapasitas = max(1, (int) ($roomBookings->max('kapasitas_user') ?: $anggota->count()));

        if ($anggota->count() >= $kapasitas) {
            return back()->with('error', 'Room ini sudah penuh.');
        }

        $pemilikId = (string) $roomBookings->first()->id_pengguna;
        $sesiPemilik = $roomBookings->where('id_pengguna', $pemilikId)->values();
        $tanggal = Carbon::parse($roomBookings->first()->tgl_main);
        $gratis = $this->modePembayaranRoom($roomBookings->first()) === 'one';

        do {
            $kodePembayaran = 'PAY-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
        } while (Booking::query()->where('kode_pembayaran', $kodePembayaran)->exists());

        $kodeBookings = DB::transaction(function () use ($pengguna, $sesiPemilik, $tanggal, $kodePembayaran, $kodeRoom, $gratis): array {
            $kodeBookings = [];

            foreach ($sesiPemilik as $sesi) {
                $jamMulai = substr((string) $sesi->jam_mulai, 0, 5);
                $jamSelesai = substr((string) $sesi->jam_selesai, 0, 5);

                if ($tanggal->isToday() && $jamMulai <= Carbon::now()->format('H:i')) {
                    continue;
                }

                do {
                    $kodeBooking = 'BK-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
                } while (Booking::query()->where('kode_booking', $kodeBooking)->exists());

                Booking::create([
                    'kode_booking' => $kodeBooking,
                    'kode_pembayaran' => $kodePembayaran,
                    'kode_voucher' => null,
                    'tipe_booking' => 'public',
                    'kode_room' => $kodeRoom,
                    'kapasitas_user' => $sesi->kapasitas_user,
                    'mode_pembayaran' => $this->modePembayaranRoom($sesi),
                    'id_pengguna' => (string) $pengguna->id,
                    'id_meja' => $sesi->id_meja,
                    'jam_mulai' => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                    'tgl_main' => $sesi->tgl_main,
                    'tgl_booking' => Carbon::now()->toDateString(),
                    'sistem_pembayaran' => $gratis ? 'room' : '',
                    'harga' => (string) $sesi->harga,
                    'total_harga' => $gratis ? '0' : (string) $sesi->total_harga,
                    'bukti_pembayaran' => '',
                    'status_booking' => '0',
                    'status_pembayaran' => $gratis ? 'lunas' : 'belum_bayar',
                    'dibayar_pada' => $gratis ? now() : null,
                    'status_hapus' => '0',
                ]);

                $kodeBookings[] = $kodeBooking;
            }

            if ($kodeBookings === []) {
                throw ValidationException::withMessages([
                    'room' => 'Sesi room ini sudah lewat untuk hari ini.',
                ]);
            }

            return $kodeBookings;
        });

        if ($gratis) {
            return redirect()
                ->route('riwayat')
                ->with('success', 'Berhasil bergabung ke room. Pembayaran sudah ditanggung oleh pembuka room.');
        }

        return redirect()
            ->route('booking.pembayaran', $kodePembayaran)
            ->with('success', 'Berhasil bergabung ke room. Lanjutkan pembayaran untuk mengunci kursimu.');
    }

    public function pembayaran(Request $request, string $kodePembayaran): Response
    {
        $pengguna = $this->currentPengguna($request);

        if (! $pengguna) {
            abort(404);
        }

        $bookings = Booking::query()
            ->where('kode_pembayaran', $kodePembayaran)
            ->where('id_pengguna', (string) $pengguna->id)
            ->where('status_hapus', '!=', '1')
            ->orderBy('jam_mulai')
            ->get();

        abort_if($bookings->isEmpty(), 404);

        $pertama = $bookings->first();

        return Inertia::render('User/Pembayaran', [
            'pengguna' => $pengguna,
            'pembayaran' => [
                'kode_pembayaran' => $kodePembayaran,
                'status_pembayaran' => $pertama->status_pembayaran,
                'sistem_pembayaran' => $pertama->sistem_pembayaran,
                'bukti_pembayaran_url' => $pertama->bukti_pembayaran_url,
                'dibayar_pada' => optional($pertama->dibayar_pada)->toDateTimeString(),
                'total' => (int) $bookings->sum(fn (Booking $booking) => (int) $booking->total_harga),
                'bookings' => $bookings->map(fn (Booking $booking): array => [
                    'id' => $booking->id,
                    'kode_booking' => $booking->kode_booking,
                    'id_meja' => $booking->id_meja,
                    'tgl_main' => $booking->tgl_main,
                    'jam_mulai' => substr((string) $booking->jam_mulai, 0, 5),
                    'jam_selesai' => substr((string) $booking->jam_selesai, 0, 5),
                    'total_harga' => (int) $booking->total_harga,
                ])->values(),
            ],
            'pengaturanPembayaran' => [
                'bank' => config('payment.bank'),
                'qris_image' => config('payment.qris.image'),
            ],
        ]);
    }

    public function storePembayaran(Request $request, string $kodePembayaran): RedirectResponse
    {
        $pengguna = $this->currentPengguna($request);

        if (! $pengguna) {
            return redirect()->guest(route('login'));
        }

        $bookings = Booking::query()
            ->where('kode_pembayaran', $kodePembayaran)
            ->where('id_pengguna', (string) $pengguna->id)
            ->where('status_hapus', '!=', '1')
            ->get();

        abort_if($bookings->isEmpty(), 404);

        if ($bookings->contains(fn (Booking $booking) => $booking->status_pembayaran === 'lunas')) {
            return back()->with('error', 'Pembayaran ini sudah lunas.');
        }

        $validated = $request->validate([
            'sistem_pembayaran' => ['required', Rule::in(['transfer'])],
            'bukti_pembayaran' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
        ], [
            'sistem_pembayaran.in' => 'Metode pembayaran tidak valid.',
            'bukti_pembayaran.required' => 'Bukti pembayaran wajib diunggah.',
            'bukti_pembayaran.image' => 'Bukti pembayaran harus berupa gambar.',
            'bukti_pembayaran.mimes' => 'Bukti pembayaran harus berformat JPG, JPEG, atau PNG.',
            'bukti_pembayaran.max' => 'Ukuran bukti pembayaran maksimal 2 MB.',
        ]);

        $buktiPembayaran = app(BuktiPembayaranService::class)->simpan($request->file('bukti_pembayaran'));
        $statusPembayaran = 'menunggu_verifikasi';

        // File bukti lama dihapus saat diganti agar tidak menumpuk di storage.
        if ($buktiPembayaran && ($buktiLama = $bookings->first()->bukti_pembayaran)) {
            app(BuktiPembayaranService::class)->hapus($buktiLama);
        }

        Booking::query()
            ->whereIn('id', $bookings->pluck('id'))
            ->update([
                'sistem_pembayaran' => $validated['sistem_pembayaran'],
                'bukti_pembayaran' => $buktiPembayaran ?? $bookings->first()->bukti_pembayaran,
                'status_pembayaran' => $statusPembayaran,
            ]);

        $pesan = 'Bukti pembayaran terkirim. Menunggu verifikasi admin.';

        return redirect()
            ->route('riwayat')
            ->with('success', $pesan)
            ->with('booking_success', true);
    }

    public function destroyPembayaranSesi(Request $request, string $kodePembayaran, Booking $booking): RedirectResponse
    {
        $pengguna = $this->currentPengguna($request);

        if (! $pengguna
            || (string) $booking->id_pengguna !== (string) $pengguna->id
            || (string) $booking->kode_pembayaran !== $kodePembayaran) {
            abort(404);
        }

        if ($booking->status_hapus === '1') {
            abort(404);
        }

        if ($booking->status_booking === '200') {
            return back()->with('error', 'Booking yang sudah disetujui tidak dapat dihapus.');
        }

        if (in_array($booking->status_pembayaran, ['lunas', 'menunggu_verifikasi'], true)) {
            return back()->with('error', 'Sesi ini tidak dapat dihapus karena pembayaran sudah diproses.');
        }

        if (filled($booking->kode_voucher)) {
            return back()->with('error', 'Sesi dengan voucher tidak dapat dihapus satu per satu.');
        }

        $booking->update(['status_hapus' => '1']);

        $sisaSesi = Booking::query()
            ->where('kode_pembayaran', $kodePembayaran)
            ->where('id_pengguna', (string) $pengguna->id)
            ->where('status_hapus', '!=', '1')
            ->count();

        if ($sisaSesi === 0) {
            return redirect()
                ->route('riwayat')
                ->with('success', 'Semua sesi booking dihapus. Silakan buat booking baru.');
        }

        return redirect()
            ->route('booking.pembayaran', $kodePembayaran)
            ->with('success', 'Sesi booking berhasil dihapus. Total bayar sudah diperbarui.');
    }

    public function riwayat(Request $request): Response
    {
        $pengguna = $this->currentPengguna($request);

        // Kolom tgl_main disimpan sebagai string Y-m-d, jadi cukup exact-match.
        $tglMain = $request->query('tgl_main');
        $filterTglMain = is_string($tglMain) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglMain) ? $tglMain : null;

        $bookings = Inertia::defer(function () use ($pengguna, $filterTglMain) {
            if (! $pengguna) {
                return [];
            }

            $bookings = Booking::query()
                ->where('id_pengguna', (string) $pengguna->id)
                ->where('status_hapus', '!=', '1')
                ->when($filterTglMain, fn ($query) => $query->where('tgl_main', $filterTglMain))
                ->latest()
                ->get();

            $namaPembuka = Booking::namaPembukaRoom(
                $bookings->pluck('kode_room')->filter()->unique()->values()->all(),
            );

            return $bookings->each(function (Booking $booking) use ($namaPembuka): void {
                $booking->setAttribute(
                    'pembuka_room',
                    $booking->kode_room ? ($namaPembuka[$booking->kode_room] ?? '-') : null,
                );
            });
        });

        return Inertia::render('User/Riwayat', [
            'pengguna' => $pengguna,
            'bookings' => $bookings,
            'filterTglMain' => $filterTglMain,
        ]);
    }

    public function destroyRiwayat(Request $request, Booking $booking): RedirectResponse
    {
        $pengguna = $this->currentPengguna($request);

        if (! $pengguna || (string) $booking->id_pengguna !== (string) $pengguna->id) {
            abort(404);
        }

        if ($booking->status_hapus === '1') {
            abort(404);
        }

        if ($booking->status_booking === '200') {
            return back()->with('error', 'Booking yang sudah disetujui tidak dapat dihapus.');
        }

        DB::transaction(function () use ($booking): void {
            $booking->update(['status_hapus' => '1']);

            if (! filled($booking->kode_voucher)) {
                return;
            }

            // Voucher yang belum benar-benar terpakai harus bisa dipakai lagi,
            // kecuali masih ada booking aktif lain yang memakai voucher yang sama.
            $masihDipakai = Booking::query()
                ->where('kode_voucher', $booking->kode_voucher)
                ->where('id', '!=', $booking->id)
                ->where('status_hapus', '!=', '1')
                ->where('status_booking', '!=', '400')
                ->exists();

            if (! $masihDipakai) {
                Voucher::query()
                    ->where('kode_voucher', $booking->kode_voucher)
                    ->update(['tanggal_digunakan' => '']);
            }
        });

        return back()->with('success', "Booking {$booking->kode_booking} berhasil dihapus dari riwayat.");
    }

    public function downloadTiket(Request $request, Booking $booking)
    {
        $pengguna = $this->currentPengguna($request);

        if (! $pengguna || (string) $booking->id_pengguna !== (string) $pengguna->id) {
            abort(404);
        }

        if ($booking->status_hapus === '1' || $booking->status_booking !== '200') {
            abort(404);
        }

        $qrText = $booking->kode_pembayaran ?: $booking->kode_booking;
        $qr = (new QRCode)->render($qrText);

        $pdf = Pdf::loadView('pdf.tiket', [
            'booking' => $booking,
            'pengguna' => $pengguna,
            'qr' => $qr,
        ])->setPaper([0, 0, 400, 600], 'portrait');

        return $pdf->download("tiket-{$booking->kode_booking}.pdf");
    }

    public function profil(Request $request): Response
    {
        $pengguna = $this->currentPengguna($request);

        return Inertia::render('User/Profil', [
            'pengguna' => $pengguna,
            'points' => (int) ($pengguna?->points ?? 0),
            'stats' => Inertia::defer(function () use ($pengguna) {
                if (! $pengguna) {
                    return [
                        'total' => 0,
                        'disetujui' => 0,
                        'menunggu' => 0,
                    ];
                }

                $query = Booking::query()
                    ->where('id_pengguna', (string) $pengguna->id)
                    ->where('status_hapus', '!=', '1');

                return [
                    'total' => (clone $query)->count(),
                    'disetujui' => (clone $query)->where('status_booking', '200')->count(),
                    'menunggu' => (clone $query)->whereNotIn('status_booking', ['200', '400'])->count(),
                ];
            }),
        ]);
    }

    public function updateNowa(Request $request): RedirectResponse
    {
        $pengguna = $this->currentPengguna($request);

        if (! $pengguna) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'nowa' => ['required', 'string', 'max:20', Rule::unique('penggunas', 'nowa')->ignore($pengguna->id)],
        ]);

        $pengguna->update(['nowa' => $validated['nowa']]);

        return back()->with('success', 'Nomor WhatsApp berhasil disimpan.');
    }

    public function voucher(Request $request): Response
    {
        $pengguna = $this->currentPengguna($request);

        return Inertia::render('User/Voucher', [
            'pengguna' => $pengguna,
            'vouchers' => Inertia::defer(function () use ($pengguna) {
                $statusVouchers = Statusvoucher::query()->get()->keyBy('id');

                return $pengguna
                    ? Voucher::query()
                        ->where('kode_user', $pengguna->kode_user)
                        ->latest()
                        ->get()
                        ->map(function (Voucher $voucher) use ($statusVouchers) {
                            $statusVoucher = $statusVouchers->get((int) $voucher->id_statusvoucher);

                            return [
                                'id' => $voucher->id,
                                'kode_voucher' => $voucher->kode_voucher,
                                'tanggal_diterima' => $voucher->tanggal_diterima,
                                'tanggal_digunakan' => $voucher->tanggal_digunakan,
                                'status_voucher' => $statusVoucher?->status_voucher,
                                'jumah_booking' => $statusVoucher?->jumah_booking,
                                'jumah_jam' => $statusVoucher?->jumah_jam,
                                'is_used' => filled($voucher->tanggal_digunakan),
                            ];
                        })->values()
                    : collect();
            }),
        ]);
    }

    private function resolveTipeBooking(?string $tipeBooking): ?string
    {
        $settingBookingRoom = $this->settingBookingRoomAktif();

        if (! $settingBookingRoom) {
            return null;
        }

        if (! $tipeBooking) {
            throw ValidationException::withMessages([
                'tipe_booking' => 'Pilih tipe booking terlebih dahulu.',
            ]);
        }

        return $tipeBooking;
    }

    private function settingBookingRoomAktif(): ?Settingbookingroom
    {
        return Settingbookingroom::query()
            ->where('status', 'aktif')
            ->latest('id')
            ->first();
    }

    private function modePembayaranRoom(Booking $booking): string
    {
        $mode = strtolower(trim((string) $booking->mode_pembayaran));

        if (in_array($mode, ['all', 'one'], true)) {
            return $mode;
        }

        // Data lama belum punya mode_pembayaran; harga penuh berarti dibayar sekali (one).
        return (int) $booking->total_harga >= (int) $booking->harga ? 'one' : 'all';
    }

    private function currentPengguna(Request $request): ?Pengguna
    {
        $id = $request->session()->get('pengguna_id');

        return $id ? Pengguna::query()->find($id) : null;
    }

    private function hariIndonesia(Carbon $date): string
    {
        return ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$date->dayOfWeek];
    }
}
