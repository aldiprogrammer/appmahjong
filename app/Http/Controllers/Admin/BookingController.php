<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\hargajammain;
use App\Models\jam_main;
use App\Models\Meja;
use App\Models\Pengguna;
use App\Models\Statusvoucher;
use App\Models\Voucher;
use App\Services\NotifikasiPenggunaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    use VanueScope;

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));
        $vanueIdFilter = $this->vanueFilter($request);

        // Venue yang membatasi daftar: venue milik admin venue, atau pilihan
        // filter venue bagi super admin.
        $mejaVanueId = $vanueTerbatas ?? $vanueIdFilter;

        $bookings = Booking::query()
            ->with([
                'pengguna:id,nama_depan,nama_balakang,nowa',
                'meja:id,kode_meja,nama_meja,vanue_id',
                'meja.vanue:id,nama',
            ])
            ->where('status_hapus', '!=', '1')
            ->when($mejaVanueId !== null, function ($query) use ($mejaVanueId) {
                $idMejas = Meja::query()
                    ->where('vanue_id', $mejaVanueId)
                    ->get(['kode_meja', 'id'])
                    ->flatMap(fn (Meja $meja) => [$meja->kode_meja, (string) $meja->id])
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $query->whereIn('id_meja', $idMejas);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('kode_booking', 'like', "%{$search}%")
                        ->orWhere('kode_pembayaran', 'like', "%{$search}%")
                        ->orWhere('id_pengguna', 'like', "%{$search}%")
                        ->orWhere('nama_pengguna', 'like', "%{$search}%")
                        ->orWhere('no_wa', 'like', "%{$search}%")
                        ->orWhere('id_meja', 'like', "%{$search}%")
                        ->orWhere('tgl_main', 'like', "%{$search}%")
                        ->orWhere('tgl_booking', 'like', "%{$search}%")
                        ->orWhere('sistem_pembayaran', 'like', "%{$search}%")
                        ->orWhere('tipe_booking', 'like', "%{$search}%")
                        ->orWhere('kode_room', 'like', "%{$search}%")
                        ->orWhereHas('pengguna', function ($query) use ($search) {
                            $query->where('nama_depan', 'like', "%{$search}%")
                                ->orWhere('nama_balakang', 'like', "%{$search}%");
                        });
                });
            })
            ->when(in_array($status, ['200', '400'], true), function ($query) use ($status) {
                $query->where('status_booking', $status);
            })
            ->when($status === 'menunggu', function ($query) {
                $query->whereNotIn('status_booking', ['200', '400']);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $namaPembuka = Booking::namaPembukaRoom(
            $bookings->getCollection()->pluck('kode_room')->filter()->unique()->values()->all(),
        );

        $bookings->getCollection()->transform(function (Booking $booking) use ($namaPembuka): Booking {
            $booking->setAttribute(
                'pembuka_room',
                $booking->kode_room ? ($namaPembuka[$booking->kode_room] ?? '-') : null,
            );

            return $booking;
        });

        return Inertia::render('Admin/Booking/Index', [
            'bookings' => $bookings,
            'vanues' => $this->daftarVanueTersedia($request),
            'bolehPilihVanue' => $vanueTerbatas === null,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'vanue_id' => $vanueIdFilter,
            ],
            'mejas' => Inertia::defer(fn () => Meja::query()
                ->when($vanueTerbatas !== null, fn ($query) => $query->where('vanue_id', $vanueTerbatas))
                ->with('vanue:id,nama')
                ->orderBy('nama_meja')
                ->get(['id', 'kode_meja', 'nama_meja', 'vanue_id'])),
            'jamMains' => Inertia::defer(fn () => jam_main::query()
                ->where('status', 'aktif')
                ->orderBy('jam_mulai')
                ->get(['id', 'jam_mulai', 'jam_selesai'])),
            'hargas' => Inertia::defer(fn () => hargajammain::query()
                ->when($vanueTerbatas !== null, fn ($query) => $query->where('vanue_id', $vanueTerbatas))
                ->get(['id', 'jam_main_id', 'hari', 'harga', 'diskon', 'harga_akhir', 'vanue_id'])),
            'bookedSlots' => Inertia::defer(fn () => Booking::query()
                ->where('status_hapus', '!=', '1')
                ->where('status_booking', '!=', '400')
                ->where('tgl_main', '>=', Carbon::today()->toDateString())
                ->get(['id_meja', 'tgl_main', 'jam_mulai', 'jam_selesai'])),
        ]);
    }

    /**
     * Input booking manual oleh admin: identitas tamu disimpan langsung di
     * booking (tanpa membuat akun pengguna), harga otomatis dari Harga Jam
     * Main, diskon diisi manual (persen atau nominal), booking langsung
     * disetujui, dan status pembayaran mengikuti sistem pembayaran yang dipilih.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tgl_main' => ['required', 'date', 'after_or_equal:today'],
            'meja_id' => ['required', 'integer', Rule::exists('mejas', 'id')],
            'jam_main_ids' => ['required', 'array', 'min:1'],
            'jam_main_ids.*' => ['required', 'distinct', Rule::exists('jam_mains', 'id')->where('status', 'aktif')],
            'nama_pengguna' => ['required', 'string', 'max:100'],
            'no_wa' => ['nullable', 'string', 'max:20'],
            'sistem_pembayaran' => ['required', Rule::in(['transfer', 'qris', 'tunai'])],
            'diskon_tipe' => ['required', Rule::in(['persen', 'nominal'])],
            'diskon_nilai' => [
                'required',
                'numeric',
                'min:0',
                Rule::when($request->input('diskon_tipe') === 'persen', ['max:100']),
            ],
        ]);

        $meja = Meja::query()->findOrFail($validated['meja_id']);
        $this->batasiAksesVanue($request, (int) $meja->vanue_id);

        $tanggal = Carbon::parse($validated['tgl_main']);
        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$tanggal->dayOfWeek];

        $jamMains = jam_main::query()
            ->whereIn('id', $validated['jam_main_ids'])
            ->where('status', 'aktif')
            ->get()
            ->keyBy(fn (jam_main $jamMain): string => (string) $jamMain->id);

        $hargaPerJam = hargajammain::query()
            ->whereIn('jam_main_id', array_map('strval', $validated['jam_main_ids']))
            ->where('hari', $hari)
            ->where('vanue_id', $meja->vanue_id)
            ->get()
            ->keyBy(fn (hargajammain $harga): string => (string) $harga->jam_main_id);

        $idMeja = $meja->kode_meja ?: (string) $meja->id;

        do {
            $kodePembayaran = 'PAY-'.$tanggal->format('Ymd').'-'.strtoupper(Str::random(5));
        } while (Booking::query()->where('kode_pembayaran', $kodePembayaran)->exists());

        $lunas = $validated['sistem_pembayaran'] === 'tunai';
        $noWa = filled($validated['no_wa'] ?? null) ? trim($validated['no_wa']) : null;

        DB::transaction(function () use ($validated, $jamMains, $hargaPerJam, $idMeja, $tanggal, $kodePembayaran, $lunas, $noWa): void {
            $sesi = [];
            $totalDasar = 0;

            // Kumpulkan seluruh sesi dulu agar total dasar diketahui sebelum
            // diskon manual dibagi ke tiap sesi.
            foreach ($validated['jam_main_ids'] as $jamMainId) {
                $jamMain = $jamMains->get((string) $jamMainId);
                $harga = $hargaPerJam->get((string) $jamMainId);

                if (! $jamMain || ! $harga) {
                    throw ValidationException::withMessages([
                        'jam_main_ids' => 'Harga untuk hari dan jam yang dipilih belum tersedia pada venue meja tersebut.',
                    ]);
                }

                $jamMulai = substr((string) $jamMain->jam_mulai, 0, 5);
                $jamSelesai = substr((string) $jamMain->jam_selesai, 0, 5);

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

                $hargaAkhir = max(0, (int) $harga->harga_akhir);
                $totalDasar += $hargaAkhir;

                $sesi[] = [
                    'jam_mulai' => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                    'harga' => (string) $harga->harga,
                    'harga_akhir' => $hargaAkhir,
                ];
            }

            $diskonTotal = $validated['diskon_tipe'] === 'persen'
                ? (int) round($totalDasar * (float) $validated['diskon_nilai'] / 100)
                : (int) $validated['diskon_nilai'];

            if ($diskonTotal > $totalDasar) {
                throw ValidationException::withMessages([
                    'diskon_nilai' => 'Diskon tidak boleh melebihi total harga.',
                ]);
            }

            // Bagi diskon ke tiap sesi sebanding harga sesinya; sisa pembulatan
            // diberikan ke sesi terakhir agar jumlahnya persis.
            $terpakai = 0;
            $jumlahSesi = count($sesi);

            foreach ($sesi as $urutan => $sesiSaatIni) {
                $diskonSesi = $urutan === $jumlahSesi - 1
                    ? $diskonTotal - $terpakai
                    : (int) round($sesiSaatIni['harga_akhir'] * $diskonTotal / max(1, $totalDasar));
                $diskonSesi = min($diskonSesi, $diskonTotal - $terpakai);
                $terpakai += $diskonSesi;

                do {
                    $kodeBooking = 'BK-'.$tanggal->format('Ymd').'-'.strtoupper(Str::random(4));
                } while (Booking::query()->where('kode_booking', $kodeBooking)->exists());

                Booking::create([
                    'kode_booking' => $kodeBooking,
                    'kode_pembayaran' => $kodePembayaran,
                    'kode_voucher' => null,
                    'tipe_booking' => 'private',
                    'kode_room' => null,
                    'kapasitas_user' => null,
                    'mode_pembayaran' => 'one',
                    'id_pengguna' => null,
                    'nama_pengguna' => $validated['nama_pengguna'],
                    'no_wa' => $noWa,
                    'id_meja' => $idMeja,
                    'jam_mulai' => $sesiSaatIni['jam_mulai'],
                    'jam_selesai' => $sesiSaatIni['jam_selesai'],
                    'tgl_main' => $tanggal->toDateString(),
                    'tgl_booking' => Carbon::now()->toDateString(),
                    'sistem_pembayaran' => $validated['sistem_pembayaran'],
                    'harga' => $sesiSaatIni['harga'],
                    'total_harga' => (string) max(0, $sesiSaatIni['harga_akhir'] - $diskonSesi),
                    'diskon_paket' => (string) $diskonSesi,
                    'diskon_paket_persen' => $validated['diskon_tipe'] === 'persen'
                        ? (float) $validated['diskon_nilai']
                        : null,
                    'bukti_pembayaran' => '',
                    'status_booking' => '200',
                    'status_pembayaran' => $lunas ? 'lunas' : 'belum_bayar',
                    'dibayar_pada' => $lunas ? now() : null,
                    'status_hapus' => '0',
                ]);
            }
        });

        return back()->with('success', "Booking untuk {$validated['nama_pengguna']} berhasil dibuat dan langsung disetujui.");
    }

    public function approve(Booking $booking): RedirectResponse
    {
        // Menyetujui booking berarti pembayarannya ikut dianggap terverifikasi.
        $booking->update([
            'status_booking' => '200',
            'status_pembayaran' => 'lunas',
            'dibayar_pada' => $booking->dibayar_pada ?? now(),
        ]);

        if (filled($booking->kode_voucher)) {
            Voucher::query()
                ->where('kode_voucher', $booking->kode_voucher)
                ->update(['tanggal_digunakan' => now()->toDateString()]);
        }

        $this->issueVouchers($booking);

        // Booking manual tanpa akun pengguna (id_pengguna null) tidak
        // mendapat notifikasi in-app.
        if (blank($booking->id_pengguna)) {
            return back()->with('success', "Booking {$booking->kode_booking} berhasil disetujui.");
        }

        app(NotifikasiPenggunaService::class)->catat(
            $booking->id_pengguna,
            'Booking disetujui',
            "Booking {$booking->kode_booking} telah disetujui. Sampai jumpa di meja!",
            $booking->kode_booking,
        );

        return back()->with('success', "Booking {$booking->kode_booking} berhasil disetujui.");
    }

    private function issueVouchers(Booking $booking): void
    {
        $pengguna = Pengguna::find($booking->id_pengguna);

        if (! $pengguna) {
            return;
        }

        $statusVouchers = Statusvoucher::query()
            ->where('status_voucher', 'aktif')
            ->whereNotNull('tanggal_mulai')
            ->whereNotNull('tanggal_berakhir')
            ->get();

        foreach ($statusVouchers as $statusVoucher) {
            $requiredBookings = (int) $statusVoucher->jumah_booking;

            if ($requiredBookings <= 0) {
                continue;
            }

            $approvedCount = Booking::query()
                ->where('id_pengguna', $booking->id_pengguna)
                ->where('status_booking', '200')
                ->where('status_hapus', '!=', '1')
                ->whereBetween('tgl_booking', [$statusVoucher->tanggal_mulai, $statusVoucher->tanggal_berakhir])
                ->count();

            $possibleVouchers = (int) floor($approvedCount / $requiredBookings);

            if ($possibleVouchers <= 0) {
                continue;
            }

            $issuedCount = Voucher::query()
                ->where('kode_user', $pengguna->kode_user)
                ->where('id_statusvoucher', (string) $statusVoucher->id)
                ->count();

            $newVouchers = $possibleVouchers - $issuedCount;

            if ($newVouchers <= 0) {
                continue;
            }

            for ($i = 0; $i < $newVouchers; $i++) {
                Voucher::create([
                    'kode_user' => $pengguna->kode_user,
                    'kode_voucher' => 'VCH-'.Str::upper(Str::random(10)),
                    'tanggal_diterima' => now()->toDateString(),
                    'tanggal_digunakan' => '',
                    'id_statusvoucher' => (string) $statusVoucher->id,
                ]);
            }
        }
    }

    public function reject(Booking $booking): RedirectResponse
    {
        $booking->update(['status_booking' => '400']);

        if (filled($booking->kode_voucher)) {
            Voucher::query()
                ->where('kode_voucher', $booking->kode_voucher)
                ->update(['tanggal_digunakan' => '']);
        }

        if (blank($booking->id_pengguna)) {
            return back()->with('success', "Booking {$booking->kode_booking} berhasil ditolak.");
        }

        app(NotifikasiPenggunaService::class)->catat(
            $booking->id_pengguna,
            'Booking ditolak',
            "Booking {$booking->kode_booking} ditolak. Silakan hubungi admin bila perlu bantuan.",
            $booking->kode_booking,
        );

        return back()->with('success', "Booking {$booking->kode_booking} berhasil ditolak.");
    }

    public function verifikasiPembayaran(Booking $booking): RedirectResponse
    {
        Booking::query()
            ->where('kode_pembayaran', $booking->kode_pembayaran ?: $booking->kode_booking)
            ->where('id_pengguna', $booking->id_pengguna)
            ->where('status_hapus', '!=', '1')
            ->update([
                'status_pembayaran' => 'lunas',
                'dibayar_pada' => now(),
            ]);

        return back()->with('success', "Pembayaran {$booking->kode_pembayaran} berhasil diverifikasi.");
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        DB::transaction(function () use ($booking): void {
            $booking->update([
                'status_booking' => '400',
                'status_hapus' => '1',
            ]);

            if (filled($booking->kode_voucher)) {
                Voucher::query()
                    ->where('kode_voucher', $booking->kode_voucher)
                    ->update(['tanggal_digunakan' => '']);
            }
        });

        return back()->with('success', "Booking {$booking->kode_booking} berhasil dihapus dari daftar.");
    }
}
