<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Meja;
use App\Models\Pengguna;
use App\Models\Statusvoucher;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
                'pengguna:id,nama_depan,nama_balakang',
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
        ]);
    }

    public function approve(Booking $booking): RedirectResponse
    {
        $booking->update(['status_booking' => '200']);

        if (filled($booking->kode_voucher)) {
            Voucher::query()
                ->where('kode_voucher', $booking->kode_voucher)
                ->update(['tanggal_digunakan' => now()->toDateString()]);
        }

        $this->issueVouchers($booking);

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
