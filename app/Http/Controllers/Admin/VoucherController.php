<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Statusvoucher;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $statusVouchers = Statusvoucher::query()->get()->keyBy('id');

        $vouchers = Voucher::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('kode_voucher', 'like', "%{$search}%")
                        ->orWhere('kode_user', 'like', "%{$search}%")
                        ->orWhere('id_statusvoucher', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('tanggal_diterima', 'like', "%{$search}%")
                        ->orWhere('tanggal_digunakan', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(function (Voucher $voucher) use ($statusVouchers) {
                $statusVoucher = $statusVouchers->get((int) $voucher->id_statusvoucher);

                return [
                    'id' => $voucher->id,
                    'kode_user' => $voucher->kode_user,
                    'kode_voucher' => $voucher->kode_voucher,
                    'tanggal_diterima' => $voucher->tanggal_diterima,
                    'tanggal_digunakan' => $voucher->tanggal_digunakan,
                    'id_statusvoucher' => $voucher->id_statusvoucher,
                    'status' => $voucher->status ?: 'aktif',
                    'status_voucher' => $statusVoucher?->status_voucher,
                    'jumah_jam' => $statusVoucher?->jumah_jam,
                    'is_used' => filled($voucher->tanggal_digunakan),
                    'created_at' => $voucher->created_at,
                ];
            });

        return Inertia::render('Admin/Voucher/Index', [
            'vouchers' => $vouchers,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(Voucher $voucher): Response
    {
        $statusVoucher = Statusvoucher::query()->find((int) $voucher->id_statusvoucher);

        $bookings = Booking::query()
            ->where('kode_voucher', $voucher->kode_voucher)
            ->with('pengguna:id,nama_depan,nama_balakang')
            ->latest()
            ->get();

        return Inertia::render('Admin/Voucher/Show', [
            'voucher' => [
                'id' => $voucher->id,
                'kode_user' => $voucher->kode_user,
                'kode_voucher' => $voucher->kode_voucher,
                'status' => $voucher->status ?: 'aktif',
                'status_voucher' => $statusVoucher?->status_voucher,
                'jumah_jam' => $statusVoucher?->jumah_jam,
                'tanggal_diterima' => $voucher->tanggal_diterima,
                'tanggal_digunakan' => $voucher->tanggal_digunakan,
                'is_used' => filled($voucher->tanggal_digunakan),
            ],
            'bookings' => $bookings->map(fn (Booking $booking): array => [
                'id' => $booking->id,
                'kode_booking' => $booking->kode_booking,
                'kode_pembayaran' => $booking->kode_pembayaran,
                'pengguna' => $booking->pengguna ? trim($booking->pengguna->nama_depan.' '.$booking->pengguna->nama_balakang) : '-',
                'id_meja' => $booking->id_meja,
                'tgl_main' => $booking->tgl_main,
                'jam_mulai' => substr((string) $booking->jam_mulai, 0, 5),
                'jam_selesai' => substr((string) $booking->jam_selesai, 0, 5),
                'status_booking' => $booking->status_booking,
                'status_pembayaran' => $booking->status_pembayaran,
                'total_harga' => $booking->total_harga,
            ])->values(),
        ]);
    }

    public function updateStatus(Voucher $voucher): RedirectResponse
    {
        $voucher->update([
            'status' => $voucher->status === 'aktif' ? 'nonaktif' : 'aktif',
        ]);

        return back()->with('success', 'Status voucher berhasil diperbarui.');
    }

    public function destroy(Voucher $voucher): RedirectResponse
    {
        Booking::query()
            ->where('kode_voucher', $voucher->kode_voucher)
            ->update(['kode_voucher' => null]);

        $voucher->delete();

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Voucher berhasil dihapus.');
    }
}
