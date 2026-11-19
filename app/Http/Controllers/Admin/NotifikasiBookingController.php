<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
use App\Http\Controllers\Controller;
use App\Models\NotifikasiBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotifikasiBookingController extends Controller
{
    use VanueScope;

    /**
     * Daftar notifikasi booking masuk untuk admin yang sedang aktif.
     * Admin venue hanya menerima notifikasi venue miliknya, super admin
     * menerima notifikasi dari seluruh venue.
     */
    public function index(Request $request): JsonResponse
    {
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        $query = NotifikasiBooking::query()
            ->when($vanueTerbatas !== null, fn ($query) => $query->where('vanue_id', $vanueTerbatas))
            ->latest('id');

        $notifikasi = (clone $query)
            ->limit(15)
            ->get(['id', 'vanue_id', 'tipe', 'judul', 'pesan', 'kode_booking', 'dibaca', 'created_at'])
            ->map(fn (NotifikasiBooking $item): array => [
                'id' => $item->id,
                'tipe' => $item->tipe,
                'judul' => $item->judul,
                'pesan' => $item->pesan,
                'kode_booking' => $item->kode_booking,
                'dibaca' => $item->dibaca,
                'waktu' => $item->created_at?->diffForHumans(),
            ])
            ->values();

        return response()->json([
            'belum_dibaca' => (clone $query)->where('dibaca', false)->count(),
            'notifikasi' => $notifikasi,
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca.
     */
    public function dibaca(Request $request, NotifikasiBooking $notifikasi): JsonResponse
    {
        if ($notifikasi->vanue_id !== null) {
            $this->batasiAksesVanue($request, (int) $notifikasi->vanue_id);
        }

        $notifikasi->update(['dibaca' => true]);

        return response()->json(['ok' => true]);
    }

    /**
     * Tandai seluruh notifikasi milik admin sebagai sudah dibaca.
     */
    public function dibacaSemua(Request $request): JsonResponse
    {
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        NotifikasiBooking::query()
            ->when($vanueTerbatas !== null, fn ($query) => $query->where('vanue_id', $vanueTerbatas))
            ->where('dibaca', false)
            ->update(['dibaca' => true]);

        return response()->json(['ok' => true]);
    }
}
