<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $bookings = Booking::query()
            ->with('pengguna:id,nama_depan,nama_balakang')
            ->where('status_hapus', '!=', '1')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('kode_booking', 'like', "%{$search}%")
                        ->orWhere('kode_pembayaran', 'like', "%{$search}%")
                        ->orWhere('id_pengguna', 'like', "%{$search}%")
                        ->orWhere('id_meja', 'like', "%{$search}%")
                        ->orWhere('tgl_main', 'like', "%{$search}%")
                        ->orWhere('tgl_booking', 'like', "%{$search}%")
                        ->orWhere('sistem_pembayaran', 'like', "%{$search}%")
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

        return Inertia::render('Admin/Booking/Index', [
            'bookings' => $bookings,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function approve(Booking $booking): RedirectResponse
    {
        $booking->update(['status_booking' => '200']);

        return back()->with('success', "Booking {$booking->kode_booking} berhasil disetujui.");
    }

    public function reject(Booking $booking): RedirectResponse
    {
        $booking->update(['status_booking' => '400']);

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
        $booking->update(['status_hapus' => '1']);

        return back()->with('success', "Booking {$booking->kode_booking} berhasil dihapus dari daftar.");
    }
}
