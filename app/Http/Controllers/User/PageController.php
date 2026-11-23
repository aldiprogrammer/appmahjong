<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\hargajammain;
use App\Models\jam_main;
use App\Models\Kategorimenu;
use App\Models\Meja;
use App\Models\Menu;
use App\Models\NotifikasiBooking;
use App\Models\paket_hargajammain;
use App\Models\Pembayaran;
use App\Models\Pengguna;
use App\Models\Settingbookingroom;
use App\Models\Statusvoucher;
use App\Models\Vanue;
use App\Models\Voucher;
use App\Rules\NomorWhatsappBelumTerdaftar;
use App\Services\BuktiPembayaranService;
use App\Services\NotifikasiBookingService;
use App\Services\OtpRegisterService;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

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
        $kodeMeja = trim((string) $request->query('meja', ''));
        $mejaAwal = null;

        if ($kodeMeja !== '') {
            $meja = Meja::query()
                ->whereRaw('LOWER(kode_meja) = ?', [Str::lower($kodeMeja)])
                ->first(['id', 'kode_meja', 'nama_meja', 'vanue_id']);

            if ($meja) {
                $mejaAwal = [
                    'id' => $meja->id,
                    'kode_meja' => $meja->kode_meja,
                    'nama_meja' => $meja->nama_meja,
                    'vanue_id' => (string) $meja->vanue_id,
                ];
            }
        }

        $kategoriMenus = Kategorimenu::query()
            ->whereHas('menus', fn ($query) => $query->where('status', 'aktif'))
            ->orderBy('kategori')
            ->get(['id', 'kategori', 'vanue_id']);

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
                'vanue_id' => (string) $menu->vanue_id,
                'gambar_url' => $menu->gambar_url,
                'dibuat_di' => $menu->dibuat_di,
                'harga_dingin' => $menu->harga_dingin !== null ? (int) $menu->harga_dingin : null,
                'harga_panas' => $menu->harga_panas !== null ? (int) $menu->harga_panas : null,
            ]);

        $vanues = Vanue::query()
            ->where('status', Vanue::STATUS_AKTIF)
            ->orderBy('nama')
            ->get(['id', 'nama', 'alamat', 'logo', 'banner', 'fasilitas', 'latitude', 'longitude']);

        return Inertia::render('User/Pesan', [
            'pengguna' => $this->currentPengguna($request),
            'kategoriMenus' => $kategoriMenus,
            'menus' => $menus,
            'mejaAwal' => $mejaAwal,
            'vanues' => $vanues,
            // Bila hanya ada satu venue aktif, halaman pesan langsung menampilkan
            // menu venue tersebut tanpa melewati langkah pilih venue.
            'vanueAwalId' => $vanues->count() === 1 ? (string) $vanues->first()->id : null,
            'mejas' => Meja::query()->orderBy('nama_meja')->get(['id', 'kode_meja', 'nama_meja', 'vanue_id']),
        ]);
    }

    public function cariMeja(Request $request): JsonResponse
    {
        $kode = trim((string) $request->query('kode', ''));

        if ($kode === '') {
            return response()->json([
                'found' => false,
                'message' => 'Kode meja tidak boleh kosong.',
            ]);
        }

        $meja = Meja::query()
            ->whereRaw('LOWER(kode_meja) = ?', [Str::lower($kode)])
            ->with('vanue:id,nama,logo')
            ->first(['id', 'kode_meja', 'nama_meja']);

        if (! $meja) {
            return response()->json([
                'found' => false,
                'message' => 'Kode meja tidak ditemukan. Pastikan QR code yang dipindai benar.',
            ]);
        }

        return response()->json([
            'found' => true,
            'meja' => [
                'id' => $meja->id,
                'kode_meja' => $meja->kode_meja,
                'nama_meja' => $meja->nama_meja,
            ],
            'vanue' => $meja->vanue ? [
                'nama' => $meja->vanue->nama,
                'logo_url' => $meja->vanue->logo_url,
            ] : null,
            'pesan_url' => route('pesan', ['meja' => $meja->kode_meja]),
            'point_url' => route('point'),
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

        $vanues = Vanue::query()
            ->where('status', Vanue::STATUS_AKTIF)
            ->orderBy('nama')
            ->get(['id', 'nama', 'alamat', 'logo', 'banner', 'fasilitas', 'latitude', 'longitude']);

        return Inertia::render('User/Booking', [
            'pengguna' => $pengguna,
            'vanues' => $vanues,
            // Bila hanya ada satu venue aktif, halaman langsung membuka pilihan
            // meja venue tersebut tanpa melewati langkah pilih venue.
            'vanueAwalId' => $vanues->count() === 1 ? (string) $vanues->first()->id : null,
            'mejas' => Inertia::defer(fn () => Meja::query()->orderBy('nama_meja')->get(['id', 'kode_meja', 'nama_meja', 'vanue_id'])),
            'jamMains' => Inertia::defer(fn () => jam_main::query()
                ->where('status', 'aktif')
                ->orderBy('jam_mulai')
                ->get(['id', 'jam_mulai', 'jam_selesai', 'status'])),
            'hargas' => Inertia::defer(fn () => hargajammain::query()
                ->orderBy('hari')
                ->orderBy('harga')
                ->get(['id', 'jam_main_id', 'hari', 'harga', 'diskon', 'harga_akhir', 'vanue_id'])),
            'paketHargas' => Inertia::defer(fn () => paket_hargajammain::query()
                ->orderBy('minimal_jam')
                ->get(['id', 'vanue_id', 'hari', 'minimal_jam', 'maksimal_jam', 'jam_mulai', 'jam_selesai', 'diskon_persen', 'harga_paket'])),
            'bookedSlots' => Inertia::defer(fn () => Booking::query()
                ->where('status_hapus', '!=', '1')
                ->where('status_booking', '!=', '400')
                ->where('tgl_main', '>=', Carbon::today()->toDateString())
                ->get(['id_meja', 'tgl_main', 'jam_mulai', 'jam_selesai'])),
            'settingBookingRooms' => Inertia::defer(fn () => Settingbookingroom::query()
                ->where('status', 'aktif')
                ->latest('id')
                ->get(['id', 'vanue_id', 'pembayaran', 'jumlah_user', 'minimal_user', 'maksimal_user'])),
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

        if (blank($pengguna->nowa)) {
            throw ValidationException::withMessages([
                'nowa' => 'Isi nomor WhatsApp terlebih dahulu sebelum melakukan booking.',
            ]);
        }

        $validated['tipe_booking'] = $this->resolveTipeBooking($validated['tipe_booking'] ?? null);

        // Pilihan user hanya disimpan sebagai draf di session. Baru saat bukti
        // pembayaran dikirim draf ini dibuatkan baris booking di database.
        $rincian = $this->rincianSesiBooking($validated, $pengguna);
        $kodePembayaran = $this->kodePembayaranBaru(Carbon::parse($validated['tgl_main']));

        $draft = [
            'id_pengguna' => (string) $pengguna->id,
            'meja_id' => (string) $validated['meja_id'],
            'jam_main_ids' => array_values(array_map('strval', $validated['jam_main_ids'])),
            'tgl_main' => (string) $validated['tgl_main'],
            'kode_voucher' => $validated['kode_voucher'] ?? null,
            'tipe_booking' => $validated['tipe_booking'] ?? null,
            'kode_pembayaran' => $kodePembayaran,
            'kode_room' => $rincian['kode_room'],
            'dibuat_pada' => now()->toDateTimeString(),
        ];

        $this->simpanDraftBooking($kodePembayaran, $draft);

        return redirect()
            ->route('booking.pembayaran', $kodePembayaran)
            ->with('success', 'Jadwal disiapkan. Kirim bukti pembayaran untuk menyimpan bookingmu.');
    }

    /**
     * Hitung rincian sesi dan harga tanpa menulis ke database. Dipakai saat
     * menyiapkan draf dan lagi saat draf difinalisasi agar harga serta
     * ketersediaan jam tetap sesuai data terkini.
     *
     * @param  array{meja_id:mixed, jam_main_ids:array<int,mixed>, tgl_main:string, kode_voucher?:string|null, tipe_booking?:string|null}  $validated
     * @return array{kode_room: string|null, kapasitas_user: int|null, mode_pembayaran: string|null, harga_paket: int|null, sesi: array<int, array{id_meja:string, jam_mulai:string, jam_selesai:string, tgl_main:string, harga:string, total_harga:string, diskon_paket:string, diskon_paket_persen:float|null, harga_paket:string|null, pakai_voucher:bool}>}
     */
    private function rincianSesiBooking(array $validated, Pengguna $pengguna, ?string $kodeRoom = null): array
    {
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

        // Harga jam main mengikuti venue meja yang dipilih, tanpa fallback antar venue.
        $hargaPerJam = hargajammain::query()
            ->whereIn('jam_main_id', array_map('strval', $validated['jam_main_ids']))
            ->where('hari', $hari)
            ->where('vanue_id', $meja->vanue_id)
            ->get()
            ->keyBy(fn (hargajammain $harga) => (string) $harga->jam_main_id);

        // Paket harga berlaku pada hari dan venue yang sama. Bila total durasi
        // melebihi maksimal jam paket, sejumlah sesi dalam rentang jam paket tetap
        // memakai paket dan sisanya memakai harga jam normal. Harga paket tetap
        // mengalahkan diskon persen bila keduanya terisi. Voucher tidak bertumpuk.
        $hargaAkhirPerJam = collect($validated['jam_main_ids'])
            ->mapWithKeys(fn (mixed $jamMainId): array => [
                (string) $jamMainId => (int) round((float) ($hargaPerJam->get((string) $jamMainId)?->harga_akhir ?? 0)),
            ])
            ->all();

        $paketMatch = $voucher === null ? paket_hargajammain::terbaik(
            paket_hargajammain::query()
                ->where('hari', $hari)
                ->where('vanue_id', $meja->vanue_id)
                ->get(),
            $jamMains->values(),
            $hargaAkhirPerJam,
        ) : null;
        $sesiPaketIds = $voucher === null ? $paketMatch['sesi_paket'] ?? null : null;
        $hargaPaket = $voucher === null ? $paketMatch['harga_paket'] ?? null : null;
        $persenPaket = $voucher === null ? $paketMatch['persen_paket'] ?? 0.0 : 0.0;

        $sesi = [];
        $hargaAkhirSesi = [];
        $idSesi = [];

        foreach ($validated['jam_main_ids'] as $jamMainId) {
            $jamMain = $jamMains->get((string) $jamMainId);
            $harga = $hargaPerJam->get((string) $jamMainId);

            if (! $jamMain || ! $harga) {
                throw ValidationException::withMessages([
                    'jam_main_ids' => 'Harga untuk hari dan jam yang dipilih belum tersedia pada venue meja tersebut.',
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

            $idSesi[] = (string) $jamMainId;
            $hargaAkhirSesi[(string) $jamMainId] = (string) $harga->harga_akhir;

            $sesi[] = [
                'id_meja' => $idMeja,
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'tgl_main' => $tanggal->toDateString(),
                'harga' => (string) $harga->harga,
                'total_harga' => (string) $harga->harga_akhir,
                'diskon_paket' => '0',
                'diskon_paket_persen' => null,
                'harga_paket' => null,
                'pakai_voucher' => $voucher !== null,
            ];
        }

        $alokasiHarga = $this->alokasiHargaSesi($hargaAkhirSesi, $hargaPaket, $persenPaket, $sesiPaketIds);

        foreach ($sesi as $index => $item) {
            $alokasi = $alokasiHarga[$idSesi[$index]];

            $sesi[$index]['total_harga'] = $voucher ? '0' : $alokasi['total_harga'];
            $sesi[$index]['diskon_paket'] = $voucher ? '0' : $alokasi['diskon_paket'];
            $sesi[$index]['diskon_paket_persen'] = $voucher ? null : $alokasi['diskon_paket_persen'];
            $sesi[$index]['harga_paket'] = $voucher ? null : $alokasi['harga_paket'];
        }

        $totalSesi = (int) array_sum(array_map(fn (array $item): int => (int) $item['total_harga'], $sesi));

        $kapasitasUser = null;
        $modePembayaran = null;

        if (($validated['tipe_booking'] ?? null) === 'public') {
            $kapasitasUser = max(1, (int) ($settingBookingRoom?->jumlah_user ?? 1));
            $modePembayaran = strtolower((string) ($settingBookingRoom?->pembayaran ?? 'all'));

            if ($kodeRoom === null) {
                do {
                    $kodeRoom = 'ROOM-'.$tanggal->format('Ymd').'-'.strtoupper(Str::random(5));
                } while (Booking::query()->where('kode_room', $kodeRoom)->exists()
                    || collect($this->draftBookings())->contains(fn (array $draft): bool => ($draft['kode_room'] ?? null) === $kodeRoom));
            }
        }

        return [
            'kode_room' => $kodeRoom,
            'kapasitas_user' => $kapasitasUser,
            'mode_pembayaran' => $modePembayaran,
            'harga_paket' => $hargaPaket !== null && ! $voucher ? $totalSesi : null,
            'sesi' => $sesi,
        ];
    }

    /**
     * Bagi harga paket tetap secara proporsional ke sesi yang terkena paket
     * berdasarkan harga_akhir. Sisa pembulatan diberikan ke sesi terakhir.
     * Sesi di luar paket tetap memakai harga jam normal. Paket diskon persen
     * dihitung per sesi yang terkena paket.
     *
     * @param  array<int|string, numeric-string|int|float>  $hargaAkhir  keyed by id jam main
     * @param  array<int, string>|null  $sesiPaketIds
     * @return array<int|string, array{total_harga: string, diskon_paket: string, diskon_paket_persen: float|null, harga_paket: string|null}>
     */
    private function alokasiHargaSesi(array $hargaAkhir, ?float $hargaPaket, float $persenPaket, ?array $sesiPaketIds = null): array
    {
        $idsPaket = $sesiPaketIds === null
            ? array_keys($hargaAkhir)
            : array_values(array_intersect(
                array_map('strval', array_keys($hargaAkhir)),
                array_map('strval', $sesiPaketIds),
            ));
        $idsPaket = array_map('strval', $idsPaket);
        $hasil = [];

        foreach ($hargaAkhir as $id => $harga) {
            if (! in_array((string) $id, $idsPaket, true)) {
                $normal = (int) round((float) $harga);

                $hasil[$id] = [
                    'total_harga' => (string) $normal,
                    'diskon_paket' => '0',
                    'diskon_paket_persen' => null,
                    'harga_paket' => null,
                ];
            }
        }

        $hargaPaketSesi = [];

        foreach ($idsPaket as $id) {
            $hargaPaketSesi[$id] = $hargaAkhir[$id];
        }

        if ($hargaPaket !== null) {
            $target = max(0, (int) round($hargaPaket));
            $totalNormal = (int) array_sum(array_map(fn ($harga): int => (int) round((float) $harga), $hargaPaketSesi));
            $tersisa = $target;
            $jumlahPaket = count($hargaPaketSesi);
            $posisi = 0;

            foreach ($hargaPaketSesi as $id => $harga) {
                $normal = (int) round((float) $harga);

                if ($posisi === $jumlahPaket - 1) {
                    $total = $tersisa;
                } elseif ($totalNormal > 0) {
                    $total = min((int) floor($target * $normal / $totalNormal), $tersisa);
                    $tersisa -= $total;
                } else {
                    $total = min((int) floor($target / max(1, $jumlahPaket)), $tersisa);
                    $tersisa -= $total;
                }

                $total = max(0, $total);
                $tersisa = max(0, $tersisa);
                $posisi++;

                $hasil[$id] = [
                    'total_harga' => (string) $total,
                    'diskon_paket' => (string) max(0, $normal - $total),
                    'diskon_paket_persen' => null,
                    'harga_paket' => (string) $total,
                ];
            }

            return $hasil;
        }

        foreach ($hargaPaketSesi as $id => $harga) {
            $normal = (int) round((float) $harga);
            $diskon = $persenPaket > 0 ? (int) round($normal * $persenPaket / 100) : 0;
            $total = max(0, $normal - $diskon);

            $hasil[$id] = [
                'total_harga' => (string) $total,
                'diskon_paket' => (string) $diskon,
                'diskon_paket_persen' => $persenPaket > 0 ? $persenPaket : null,
                'harga_paket' => null,
            ];
        }

        return $hasil;
    }

    private function kodePembayaranBaru(Carbon $tanggal): string
    {
        do {
            $kodePembayaran = 'PAY-'.$tanggal->format('Ymd').'-'.strtoupper(Str::random(5));
        } while (Booking::query()->where('kode_pembayaran', $kodePembayaran)->exists()
            || array_key_exists($kodePembayaran, $this->draftBookings()));

        return $kodePembayaran;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function draftBookings(): array
    {
        // Draf kedaluwarsa dibuang supaya session tidak menumpuk.
        $drafts = session()->get('booking_drafts', []);

        if (! is_array($drafts)) {
            return [];
        }

        $drafts = array_filter($drafts, function ($draft): bool {
            if (! is_array($draft) || blank($draft['tgl_main'] ?? null)) {
                return false;
            }

            return Carbon::parse($draft['tgl_main'])->toDateString() >= Carbon::today()->toDateString();
        });

        return $drafts;
    }

    private function simpanDraftBooking(string $kodePembayaran, array $draft): void
    {
        $drafts = $this->draftBookings();
        $drafts[$kodePembayaran] = $draft;

        session()->put('booking_drafts', $drafts);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function draftBooking(string $kodePembayaran, ?Pengguna $pengguna): ?array
    {
        $draft = $this->draftBookings()[$kodePembayaran] ?? null;

        if (! $pengguna || ! is_array($draft)) {
            return null;
        }

        return (string) ($draft['id_pengguna'] ?? '') === (string) $pengguna->id ? $draft : null;
    }

    private function hapusDraftBooking(string $kodePembayaran): void
    {
        $drafts = $this->draftBookings();
        unset($drafts[$kodePembayaran]);

        session()->put('booking_drafts', $drafts);
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array{meja_id:string, jam_main_ids:array<int,string>, tgl_main:string, kode_voucher:string|null, tipe_booking:string|null}
     */
    private function validatedDariDraft(array $draft): array
    {
        $validated = [
            'meja_id' => (string) $draft['meja_id'],
            'jam_main_ids' => array_values(array_map('strval', $draft['jam_main_ids'] ?? [])),
            'tgl_main' => (string) $draft['tgl_main'],
            'kode_voucher' => $draft['kode_voucher'] ?? null,
            'tipe_booking' => $draft['tipe_booking'] ?? null,
        ];

        $validated['tipe_booking'] = $this->resolveTipeBooking($validated['tipe_booking']);

        return $validated;
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

        if (blank($pengguna->nowa)) {
            return back()->with('error', 'Lengkapi nomor WhatsApp terlebih dahulu sebelum bergabung ke room.');
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
            // Satu kode booking dipakai bersama oleh seluruh sesi dalam transaksi ini.
            do {
                $kodeBooking = 'BK-'.$tanggal->format('Ymd').'-'.strtoupper(Str::random(4));
            } while (Booking::query()->where('kode_booking', $kodeBooking)->exists());

            $kodeBookings = [];

            foreach ($sesiPemilik as $sesi) {
                $jamMulai = substr((string) $sesi->jam_mulai, 0, 5);
                $jamSelesai = substr((string) $sesi->jam_selesai, 0, 5);

                if ($tanggal->isToday() && $jamMulai <= Carbon::now()->format('H:i')) {
                    continue;
                }

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
                    'diskon_paket' => $gratis ? '0' : (string) ($sesi->diskon_paket ?? '0'),
                    'diskon_paket_persen' => $gratis ? null : $sesi->diskon_paket_persen,
                    'harga_paket' => $gratis ? null : $sesi->harga_paket,
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

        $this->catatNotifikasiBookingMasuk(
            $this->mejaDariKode((string) $roomBookings->first()->id_meja),
            count($kodeBookings),
            $kodeBookings[0] ?? null,
            NotifikasiBooking::TIPE_ROOM,
        );

        if ($gratis) {
            return redirect()
                ->route('riwayat')
                ->with('success', 'Berhasil bergabung ke room. Pembayaran sudah ditanggung oleh pembuka room.');
        }

        return redirect()
            ->route('booking.pembayaran', $kodePembayaran)
            ->with('success', 'Berhasil bergabung ke room. Lanjutkan pembayaran untuk mengunci kursimu.');
    }

    /**
     * Catat notifikasi booking masuk ke panel admin untuk venue meja terkait.
     */
    private function catatNotifikasiBookingMasuk(
        ?Meja $meja,
        int $jumlahSesi,
        ?string $kodeBooking,
        string $tipe = NotifikasiBooking::TIPE_BOOKING,
    ): void {
        if ($meja === null || $meja->vanue_id === null || $kodeBooking === null || $jumlahSesi < 1) {
            return;
        }

        $sesi = Booking::query()
            ->where('kode_booking', $kodeBooking)
            ->first(['tgl_main', 'jam_mulai', 'jam_selesai']);

        $namaMeja = $meja->nama_meja ?: ($meja->kode_meja ?: (string) $meja->id);
        $tanggal = $sesi?->tgl_main
            ? Carbon::parse($sesi->tgl_main)->translatedFormat('d M Y')
            : '-';
        $jam = $sesi
            ? substr((string) $sesi->jam_mulai, 0, 5).' - '.substr((string) $sesi->jam_selesai, 0, 5)
            : '-';

        $judul = $tipe === NotifikasiBooking::TIPE_ROOM
            ? 'Anggota room baru bergabung'
            : 'Booking baru masuk';

        $pesan = $jumlahSesi === 1
            ? "{$judul} untuk meja {$namaMeja} pada {$tanggal} pukul {$jam}. Kode booking: {$kodeBooking}."
            : "{$jumlahSesi} sesi booking baru untuk meja {$namaMeja} pada {$tanggal} pukul {$jam}. Kode booking: {$kodeBooking}.";

        app(NotifikasiBookingService::class)->catatBookingMasuk(
            (int) $meja->vanue_id,
            $judul,
            $pesan,
            $kodeBooking,
            $tipe,
        );
    }

    /**
     * Cari meja berdasarkan kode meja (fallback ke id meja).
     */
    private function mejaDariKode(string $idMeja): ?Meja
    {
        return Meja::query()
            ->where('kode_meja', $idMeja)
            ->orWhere('id', ctype_digit($idMeja) ? (int) $idMeja : 0)
            ->first();
    }

    /**
     * Metode pembayaran untuk venue meja booking, diambil dari tabel pembayarans.
     * Bila venue belum punya data, gunakan konfigurasi payment sebagai cadangan.
     *
     * @return array<int, array{id: int|string, metode: string, bank: string|null, atas_nama: string|null, norek: string|null, qris_image_url: string|null}>
     */
    private function metodePembayaranVenue(?int $vanueId): array
    {
        $daftar = collect();

        if ($vanueId !== null) {
            $daftar = Pembayaran::query()
                ->where('id_venue', (string) $vanueId)
                ->get()
                ->map(fn (Pembayaran $pembayaran): array => [
                    'id' => $pembayaran->id,
                    'metode' => $pembayaran->metode_pembayaran,
                    'bank' => $pembayaran->bank,
                    'atas_nama' => $pembayaran->atas_nama,
                    'norek' => $pembayaran->norek,
                    'qris_image_url' => $pembayaran->qris_image
                        ? asset('storage/'.$pembayaran->qris_image)
                        : null,
                ]);
        }

        if ($daftar->isEmpty()) {
            $bank = config('payment.bank');

            $daftar = collect([[
                'id' => 'config',
                'metode' => 'transfer',
                'bank' => $bank['name'] ?? null,
                'atas_nama' => $bank['account_holder'] ?? null,
                'norek' => $bank['account_number'] ?? null,
                'qris_image_url' => config('payment.qris.image'),
            ]]);
        }

        // Transfer ditampilkan lebih dulu, lalu QRIS.
        return $daftar
            ->sortBy(fn (array $item): int => $item['metode'] === 'transfer' ? 0 : 1)
            ->values()
            ->all();
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

        $draft = $bookings->isEmpty() ? $this->draftBooking($kodePembayaran, $pengguna) : null;

        abort_if($bookings->isEmpty() && $draft === null, 404);

        if ($draft !== null) {
            $validated = $this->validatedDariDraft($draft);
            $rincian = $this->rincianSesiBooking($validated, $pengguna, $draft['kode_room'] ?? null);
            $sesi = collect($rincian['sesi']);
            $subtotal = (int) $sesi->sum(fn (array $item): int => (int) $item['harga']);
            $totalBayar = (int) $sesi->sum(fn (array $item): int => (int) $item['total_harga']);
            $menggunakanVoucher = filled($validated['kode_voucher']);
            $mejaBooking = Meja::query()->find((int) $validated['meja_id']);

            $dataPembayaran = [
                'status_pembayaran' => 'belum_bayar',
                'sistem_pembayaran' => '',
                'bukti_pembayaran_url' => null,
                'dibayar_pada' => null,
                'subtotal' => $subtotal,
                'diskon' => max(0, $subtotal - $totalBayar),
                'label_diskon' => $menggunakanVoucher ? 'Diskon voucher' : 'Diskon paket',
                'harga_paket' => $rincian['harga_paket'],
                'total' => $totalBayar,
                'bisa_hapus_sesi' => ! $menggunakanVoucher,
                'menggunakan_voucher' => $menggunakanVoucher,
                'bookings' => $sesi->map(fn (array $item, int $index): array => [
                    'id' => 'draft-'.$index,
                    'kode_booking' => '-',
                    'id_meja' => $item['id_meja'],
                    'tgl_main' => $item['tgl_main'],
                    'jam_mulai' => $item['jam_mulai'],
                    'jam_selesai' => $item['jam_selesai'],
                    'harga' => (int) $item['harga'],
                    'total_harga' => (int) $item['total_harga'],
                    'diskon_paket' => (int) $item['diskon_paket'],
                    'diskon_paket_persen' => $item['diskon_paket_persen'] !== null
                        ? (float) $item['diskon_paket_persen']
                        : null,
                    'harga_paket' => $item['harga_paket'] !== null ? (int) $item['harga_paket'] : null,
                    'pakai_voucher' => $item['pakai_voucher'],
                ])->values()->all(),
            ];
        } else {
            $pertama = $bookings->first();
            $totalBayar = (int) $bookings->sum(fn (Booking $booking) => (int) $booking->total_harga);
            $subtotal = (int) $bookings->sum(fn (Booking $booking) => (int) $booking->harga);
            $hargaPaket = $bookings->contains(fn (Booking $booking): bool => $booking->harga_paket !== null)
                ? $totalBayar
                : null;

            // Label diskon menyesuaikan sumbernya: voucher atau paket jam main.
            $labelDiskon = $pertama->sistem_pembayaran === 'voucher'
                ? 'Diskon voucher'
                : 'Diskon paket';

            $mejaBooking = $this->mejaDariKode((string) $pertama->id_meja);

            $dataPembayaran = [
                'status_pembayaran' => $pertama->status_pembayaran,
                'sistem_pembayaran' => $pertama->sistem_pembayaran,
                'bukti_pembayaran_url' => $pertama->bukti_pembayaran_url,
                'dibayar_pada' => optional($pertama->dibayar_pada)->toDateTimeString(),
                'subtotal' => $subtotal,
                'diskon' => max(0, $subtotal - $totalBayar),
                'label_diskon' => $labelDiskon,
                'harga_paket' => $hargaPaket,
                'total' => $totalBayar,
                'bisa_hapus_sesi' => true,
                'menggunakan_voucher' => false,
                'bookings' => $bookings->map(fn (Booking $booking): array => [
                    'id' => $booking->id,
                    'kode_booking' => $booking->kode_booking,
                    'id_meja' => $booking->id_meja,
                    'tgl_main' => $booking->tgl_main,
                    'jam_mulai' => substr((string) $booking->jam_mulai, 0, 5),
                    'jam_selesai' => substr((string) $booking->jam_selesai, 0, 5),
                    'harga' => (int) $booking->harga,
                    'total_harga' => (int) $booking->total_harga,
                    'diskon_paket' => (int) $booking->diskon_paket,
                    'diskon_paket_persen' => $booking->diskon_paket_persen !== null
                        ? (float) $booking->diskon_paket_persen
                        : null,
                    'harga_paket' => $booking->harga_paket !== null ? (int) $booking->harga_paket : null,
                    'pakai_voucher' => filled($booking->kode_voucher),
                ])->values()->all(),
            ];
        }

        return Inertia::render('User/Pembayaran', [
            'pengguna' => $pengguna,
            'pembayaran' => array_merge([
                'kode_pembayaran' => $kodePembayaran,
            ], $dataPembayaran),
            'pengaturanPembayaran' => [
                'metode' => $this->metodePembayaranVenue($mejaBooking?->vanue_id),
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

        $draft = $bookings->isEmpty() ? $this->draftBooking($kodePembayaran, $pengguna) : null;

        abort_if($bookings->isEmpty() && $draft === null, 404);

        if ($bookings->contains(fn (Booking $booking) => $booking->status_pembayaran === 'lunas')) {
            return back()->with('error', 'Pembayaran ini sudah lunas.');
        }

        $menggunakanVoucher = $draft !== null
            ? filled($draft['kode_voucher'] ?? null)
            : false;

        $validated = $request->validate([
            'sistem_pembayaran' => ['required', Rule::in($menggunakanVoucher ? ['voucher'] : ['transfer', 'qris'])],
            'bukti_pembayaran' => $menggunakanVoucher
                ? ['prohibited']
                : [
                    'required',
                    'image',
                    'mimes:jpg,jpeg,png',
                    'max:10240',
                ],
        ], [
            'sistem_pembayaran.in' => 'Metode pembayaran tidak valid.',
            'bukti_pembayaran.required' => 'Bukti pembayaran wajib diunggah.',
            'bukti_pembayaran.image' => 'Bukti pembayaran harus berupa gambar.',
            'bukti_pembayaran.mimes' => 'Bukti pembayaran harus berformat JPG, JPEG, atau PNG.',
            'bukti_pembayaran.max' => 'Ukuran bukti pembayaran maksimal 10 MB.',
        ]);

        $buktiPembayaran = '';

        if (! $menggunakanVoucher) {
            try {
                $buktiPembayaran = app(BuktiPembayaranService::class)->simpan($request->file('bukti_pembayaran'));
            } catch (Throwable $exception) {
                report($exception);

                return back()->withInput()->with('error', 'Gagal mengunggah bukti pembayaran ke Cloudinary. Silakan coba lagi.');
            }
        }

        if ($draft !== null) {
            return $this->finalisasiDraftBooking(
                $pengguna,
                $kodePembayaran,
                $draft,
                $validated['sistem_pembayaran'],
                $menggunakanVoucher,
                $buktiPembayaran,
            );
        }

        $statusPembayaran = 'menunggu_verifikasi';

        // Aset Cloudinary lama (atau file lokal lama) dihapus saat diganti.
        if ($buktiPembayaran && ($buktiLama = $bookings->first()->bukti_pembayaran)) {
            app(BuktiPembayaranService::class)->hapus($buktiLama);
        }

        Booking::query()
            ->whereIn('id', $bookings->pluck('id'))
            ->update([
                'sistem_pembayaran' => $validated['sistem_pembayaran'],
                'bukti_pembayaran' => $buktiPembayaran,
                'status_pembayaran' => $statusPembayaran,
            ]);

        $pesan = 'Bukti pembayaran terkirim. Menunggu verifikasi admin.';

        return redirect()
            ->route('riwayat')
            ->with('success', $pesan)
            ->with('booking_success', true);
    }

    /**
     * Buat baris booking dari draf hanya setelah bukti pembayaran dikirim.
     */
    private function finalisasiDraftBooking(
        Pengguna $pengguna,
        string $kodePembayaran,
        array $draft,
        string $sistemPembayaran,
        bool $menggunakanVoucher,
        string $buktiPembayaran,
    ): RedirectResponse {
        $kodeBookings = DB::transaction(function () use ($pengguna, $kodePembayaran, $draft, $sistemPembayaran, $menggunakanVoucher, $buktiPembayaran): array {
            $validated = $this->validatedDariDraft($draft);
            $rincian = $this->rincianSesiBooking($validated, $pengguna, $draft['kode_room'] ?? null);
            $kodeBookings = [];

            // Satu kode booking dipakai bersama oleh seluruh sesi dalam transaksi ini.
            do {
                $kodeBooking = 'BK-'.Carbon::parse($validated['tgl_main'])->format('Ymd').'-'.strtoupper(Str::random(4));
            } while (Booking::query()->where('kode_booking', $kodeBooking)->exists());

            foreach ($rincian['sesi'] as $sesi) {
                Booking::create([
                    'kode_booking' => $kodeBooking,
                    'kode_pembayaran' => $kodePembayaran,
                    'kode_voucher' => $menggunakanVoucher ? $validated['kode_voucher'] : null,
                    'tipe_booking' => $validated['tipe_booking'],
                    'kode_room' => $rincian['kode_room'],
                    'kapasitas_user' => $rincian['kapasitas_user'],
                    'mode_pembayaran' => $rincian['mode_pembayaran'],
                    'id_pengguna' => (string) $pengguna->id,
                    'id_meja' => $sesi['id_meja'],
                    'jam_mulai' => $sesi['jam_mulai'],
                    'jam_selesai' => $sesi['jam_selesai'],
                    'tgl_main' => $sesi['tgl_main'],
                    'tgl_booking' => Carbon::now()->toDateString(),
                    'sistem_pembayaran' => $menggunakanVoucher ? 'voucher' : $sistemPembayaran,
                    'harga' => $sesi['harga'],
                    'total_harga' => $sesi['total_harga'],
                    'diskon_paket' => $sesi['diskon_paket'],
                    'diskon_paket_persen' => $sesi['diskon_paket_persen'],
                    'harga_paket' => $sesi['harga_paket'],
                    'bukti_pembayaran' => $buktiPembayaran,
                    'status_booking' => '0',
                    'status_pembayaran' => $menggunakanVoucher ? 'lunas' : 'menunggu_verifikasi',
                    'dibayar_pada' => $menggunakanVoucher ? now() : null,
                    'status_hapus' => '0',
                ]);

                $kodeBookings[] = $kodeBooking;
            }

            if ($menggunakanVoucher) {
                $voucher = Voucher::query()
                    ->where('kode_voucher', $validated['kode_voucher'])
                    ->where('kode_user', $pengguna->kode_user)
                    ->first();

                $voucher?->update(['tanggal_digunakan' => now()->toDateString()]);
            }

            return $kodeBookings;
        });

        $this->hapusDraftBooking($kodePembayaran);

        $this->catatNotifikasiBookingMasuk(
            $this->mejaDariKode((string) $draft['meja_id']),
            count($kodeBookings),
            $kodeBookings[0] ?? null,
        );

        $pesan = $menggunakanVoucher
            ? 'Booking voucher berhasil dibuat.'
            : 'Booking berhasil dibuat dan bukti pembayaran terkirim. Menunggu verifikasi admin.';

        return redirect()
            ->route('riwayat')
            ->with('success', $pesan)
            ->with('booking_success', true);
    }

    /**
     * Hapus satu sesi, baik baris booking final maupun draf yang belum disimpan.
     *
     * Id draf berbentuk `draft-{index}`, sehingga parameter tidak memakai
     * route model binding.
     */
    public function destroyPembayaranSesi(Request $request, string $kodePembayaran, string $booking): RedirectResponse
    {
        $pengguna = $this->currentPengguna($request);

        if (! $pengguna) {
            abort(404);
        }

        if (Str::startsWith($booking, 'draft-')) {
            return $this->hapusSesiDraft($pengguna, $kodePembayaran, $booking);
        }

        $booking = Booking::query()
            ->whereKey($booking)
            ->where('kode_pembayaran', $kodePembayaran)
            ->where('id_pengguna', (string) $pengguna->id)
            ->where('status_hapus', '!=', '1')
            ->firstOrFail();

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

        $sisaBookings = Booking::query()
            ->where('kode_pembayaran', $kodePembayaran)
            ->where('id_pengguna', (string) $pengguna->id)
            ->where('status_hapus', '!=', '1')
            ->orderBy('jam_mulai')
            ->get();

        if ($sisaBookings->isEmpty()) {
            return redirect()
                ->route('riwayat')
                ->with('success', 'Semua sesi booking dihapus. Silakan buat booking baru.');
        }

        // Diskon paket hanya berlaku selama sesi tersisa masih memenuhi syarat
        // paket. Bila tidak lagi memenuhi, harga sesi kembali normal.
        $this->hitungUlangHargaSesi($sisaBookings);

        return redirect()
            ->route('booking.pembayaran', $kodePembayaran)
            ->with('success', 'Sesi booking berhasil dihapus. Total bayar sudah diperbarui.');
    }

    private function hapusSesiDraft(Pengguna $pengguna, string $kodePembayaran, string $sesi): RedirectResponse
    {
        $index = Str::after($sesi, 'draft-');

        if (! ctype_digit($index)) {
            abort(404);
        }

        $draft = $this->draftBooking($kodePembayaran, $pengguna);

        abort_if($draft === null, 404);

        if (filled($draft['kode_voucher'] ?? null)) {
            return back()->with('error', 'Sesi dengan voucher tidak dapat dihapus satu per satu.');
        }

        $jamMainIds = array_values(array_map('strval', $draft['jam_main_ids'] ?? []));

        if (! array_key_exists((int) $index, $jamMainIds)) {
            abort(404);
        }

        unset($jamMainIds[(int) $index]);
        $draft['jam_main_ids'] = array_values($jamMainIds);

        if ($draft['jam_main_ids'] === []) {
            $this->hapusDraftBooking($kodePembayaran);

            return redirect()
                ->route('riwayat')
                ->with('success', 'Semua sesi dihapus. Silakan buat booking baru.');
        }

        $this->simpanDraftBooking($kodePembayaran, $draft);

        return redirect()
            ->route('booking.pembayaran', $kodePembayaran)
            ->with('success', 'Sesi booking berhasil dihapus. Total bayar sudah diperbarui.');
    }

    /**
     * Hitung ulang total harga sesi yang masih tersisa pada satu pembayaran.
     * Paket harga (harga tetap maupun diskon persen) dipertahankan hanya bila
     * seluruh sesi tersisa masih memenuhi syarat paket. Bila tidak, tiap sesi
     * dikembalikan ke harga normal (`harga_akhir`).
     *
     * @param  Collection<int, Booking>  $bookings
     */
    private function hitungUlangHargaSesi(Collection $bookings): void
    {
        $semuaJamMain = jam_main::query()->get();

        $cariJamMain = function (Booking $booking) use ($semuaJamMain): ?jam_main {
            return $semuaJamMain->first(fn (jam_main $jamMain): bool => substr((string) $jamMain->jam_mulai, 0, 5) === substr((string) $booking->jam_mulai, 0, 5)
                && substr((string) $jamMain->jam_selesai, 0, 5) === substr((string) $booking->jam_selesai, 0, 5));
        };

        $sesiPerVenue = $bookings->groupBy(
            fn (Booking $booking): string => (string) $this->mejaDariKode((string) $booking->id_meja)?->vanue_id,
        );

        foreach ($sesiPerVenue as $vanueId => $sesiVenue) {
            $vanueId = $vanueId === '' ? null : $vanueId;
            $pertama = $sesiVenue->first();
            $hari = $this->hariIndonesia(Carbon::parse($pertama->tgl_main));

            $jamMains = $sesiVenue
                ->map($cariJamMain)
                ->filter()
                ->unique(fn (jam_main $jamMain): string => (string) $jamMain->id)
                ->values();

            $sesiVenue = $sesiVenue->values();
            $adaVoucher = $sesiVenue->contains(fn (Booking $booking): bool => filled($booking->kode_voucher));

            $hargaPerJam = hargajammain::query()
                ->where('vanue_id', $vanueId)
                ->where('hari', $hari)
                ->whereIn('jam_main_id', $jamMains->pluck('id')->all())
                ->get()
                ->keyBy(fn (hargajammain $harga): string => (string) $harga->jam_main_id);

            $hargaNormalPerSesi = function (Booking $sesi) use ($cariJamMain, $hargaPerJam): int {
                $jamMain = $cariJamMain($sesi);
                $harga = $jamMain ? $hargaPerJam->get((string) $jamMain->id) : null;

                return $harga ? (int) round((float) $harga->harga_akhir) : 0;
            };
            $hargaAkhirPerJam = [];
            $bookingPerJam = [];

            foreach ($sesiVenue as $sesi) {
                $jamMain = $cariJamMain($sesi);

                if (! $jamMain) {
                    continue;
                }

                $hargaAkhirPerJam[(string) $jamMain->id] = $hargaNormalPerSesi($sesi);
                $bookingPerJam[(string) $jamMain->id] = $sesi;
            }

            $paketMatch = $adaVoucher ? null : paket_hargajammain::terbaik(
                paket_hargajammain::query()
                    ->where('hari', $hari)
                    ->where('vanue_id', $vanueId)
                    ->get(),
                $jamMains,
                $hargaAkhirPerJam,
            );
            $sesiPaketIds = $paketMatch['sesi_paket'] ?? null;
            $hargaPaket = $paketMatch['harga_paket'] ?? null;
            $persenPaket = $paketMatch['persen_paket'] ?? 0.0;

            $hargaAkhirSesi = [];

            foreach ($hargaAkhirPerJam as $idJam => $harga) {
                if ($harga === 0 || filled($bookingPerJam[$idJam]->kode_voucher)) {
                    continue;
                }

                $hargaAkhirSesi[$idJam] = (string) $harga;
            }

            $alokasiHarga = $this->alokasiHargaSesi($hargaAkhirSesi, $hargaPaket, $persenPaket, $sesiPaketIds);

            foreach ($alokasiHarga as $idJam => $hargaSesi) {
                $bookingPerJam[$idJam]->update($hargaSesi);
            }
        }
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

    public function kirimOtpNowa(Request $request, OtpRegisterService $otp): RedirectResponse
    {
        $pengguna = $this->currentPengguna($request);

        if (! $pengguna) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'nowa' => ['required', 'string', 'max:20', 'regex:/^(\+?62|0|8)[0-9]{8,13}$/', new NomorWhatsappBelumTerdaftar($pengguna->id)],
        ], [
            'nowa.regex' => 'Format nomor WhatsApp tidak valid. Contoh: 08xxxxxxxxxx.',
        ]);

        if (! $otp->bisaKirim($validated['nowa'])) {
            throw ValidationException::withMessages([
                'nowa' => 'Tunggu 60 detik sebelum meminta kode OTP lagi.',
            ]);
        }

        try {
            $otp->kirimKode($validated['nowa']);
        } catch (Throwable $exception) {
            Log::warning('Gagal mengirim OTP WhatsApp profil: '.$exception->getMessage());

            throw ValidationException::withMessages([
                'nowa' => 'Kode OTP gagal dikirim. Periksa nomor WhatsApp lalu coba lagi.',
            ]);
        }

        return back()->with('otp_terkirim', true);
    }

    public function updateNowa(Request $request, OtpRegisterService $otp): RedirectResponse
    {
        $pengguna = $this->currentPengguna($request);

        if (! $pengguna) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'nowa' => ['required', 'string', 'max:20', 'regex:/^(\+?62|0|8)[0-9]{8,13}$/', new NomorWhatsappBelumTerdaftar($pengguna->id)],
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'nowa.regex' => 'Format nomor WhatsApp tidak valid. Contoh: 08xxxxxxxxxx.',
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.size' => 'Kode OTP harus terdiri dari 6 angka.',
            'otp.regex' => 'Kode OTP harus terdiri dari 6 angka.',
        ]);

        if (! $otp->verifikasiKode($validated['nowa'], $validated['otp'])) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP salah atau sudah kedaluwarsa. Minta kode baru jika perlu.',
            ]);
        }

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
