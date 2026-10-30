<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\hargajammain;
use App\Models\jam_main;
use App\Models\Meja;
use App\Models\Pengguna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
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

    public function booking(Request $request): Response
    {
        return Inertia::render('User/Booking', [
            'pengguna' => $this->currentPengguna($request),
            'mejas' => Meja::query()->orderBy('nama_meja')->get(['id', 'kode_meja', 'nama_meja']),
            'jamMains' => jam_main::query()
                ->where('status', 'aktif')
                ->orderBy('jam_mulai')
                ->get(['id', 'jam_mulai', 'jam_selesai', 'status']),
            'hargas' => hargajammain::query()
                ->orderBy('hari')
                ->orderBy('harga')
                ->get(['id', 'jam_main_id', 'hari', 'harga', 'diskon', 'harga_akhir']),
            'bookedSlots' => Booking::query()
                ->where('status_hapus', '!=', '1')
                ->where('status_booking', '!=', '400')
                ->where('tgl_main', '>=', Carbon::today()->toDateString())
                ->get(['id_meja', 'tgl_main', 'jam_mulai', 'jam_selesai']),
        ]);
    }

    public function storeBooking(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meja_id' => ['required', Rule::exists('mejas', 'id')],
            'jam_main_ids' => ['required', 'array', 'min:1'],
            'jam_main_ids.*' => ['required', 'distinct', Rule::exists('jam_mains', 'id')->where('status', 'aktif')],
            'tgl_main' => ['required', 'date', 'after_or_equal:today'],
            'sistem_pembayaran' => ['required', Rule::in(['transfer', 'qris', 'tunai'])],
        ]);

        $pengguna = $this->currentPengguna($request);

        if (! $pengguna) {
            return redirect()->guest(route('login'));
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

        $kodeBookings = [];

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

            Booking::create([
                'kode_booking' => $kodeBooking,
                'id_pengguna' => (string) $pengguna->id,
                'id_meja' => $idMeja,
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'tgl_main' => $tanggal->toDateString(),
                'tgl_booking' => Carbon::now()->toDateString(),
                'sistem_pembayaran' => $validated['sistem_pembayaran'],
                'harga' => (string) $harga->harga,
                'total_harga' => (string) $harga->harga_akhir,
                'bukti_pembayaran' => '',
                'status_booking' => '0',
                'status_hapus' => '0',
            ]);

            $kodeBookings[] = $kodeBooking;
        }

        $jumlahSesi = count($kodeBookings);
        $pesan = $jumlahSesi === 1
            ? "Booking {$kodeBookings[0]} berhasil dibuat dan menunggu persetujuan admin."
            : "{$jumlahSesi} sesi booking berhasil dibuat dan menunggu persetujuan admin.";

        return redirect()
            ->route('riwayat')
            ->with('success', $pesan);
    }

    public function riwayat(Request $request): Response
    {
        $pengguna = $this->currentPengguna($request);

        $bookings = $pengguna
            ? Booking::query()
                ->where('id_pengguna', (string) $pengguna->id)
                ->where('status_hapus', '!=', '1')
                ->latest()
                ->get()
            : collect();

        return Inertia::render('User/Riwayat', [
            'pengguna' => $pengguna,
            'bookings' => $bookings,
        ]);
    }

    public function profil(Request $request): Response
    {
        $pengguna = $this->currentPengguna($request);

        $stats = [
            'total' => 0,
            'disetujui' => 0,
            'menunggu' => 0,
        ];

        if ($pengguna) {
            $query = Booking::query()
                ->where('id_pengguna', (string) $pengguna->id)
                ->where('status_hapus', '!=', '1');

            $stats = [
                'total' => (clone $query)->count(),
                'disetujui' => (clone $query)->where('status_booking', '200')->count(),
                'menunggu' => (clone $query)->whereNotIn('status_booking', ['200', '400'])->count(),
            ];
        }

        return Inertia::render('User/Profil', [
            'pengguna' => $pengguna,
            'stats' => $stats,
        ]);
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
