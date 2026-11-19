<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\NotifikasiPengguna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotifikasiPenggunaController extends Controller
{
    /**
     * Daftar notifikasi milik pengguna yang sedang masuk.
     */
    public function index(Request $request): JsonResponse
    {
        $idPengguna = $this->idPengguna($request);

        abort_if($idPengguna === null, 401);

        $query = NotifikasiPengguna::query()
            ->where('id_pengguna', $idPengguna)
            ->latest('id');

        $notifikasi = (clone $query)
            ->limit(15)
            ->get(['id', 'tipe', 'judul', 'pesan', 'kode_booking', 'dibaca', 'created_at'])
            ->map(fn (NotifikasiPengguna $item): array => [
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
     * Tandai satu notifikasi milik pengguna sebagai sudah dibaca.
     */
    public function dibaca(Request $request, NotifikasiPengguna $notifikasi): JsonResponse
    {
        $idPengguna = $this->idPengguna($request);

        abort_if($idPengguna === null, 401);
        abort_if((string) $notifikasi->id_pengguna !== $idPengguna, 403);

        $notifikasi->update(['dibaca' => true]);

        return response()->json(['ok' => true]);
    }

    /**
     * Tandai seluruh notifikasi pengguna sebagai sudah dibaca.
     */
    public function dibacaSemua(Request $request): JsonResponse
    {
        $idPengguna = $this->idPengguna($request);

        abort_if($idPengguna === null, 401);

        NotifikasiPengguna::query()
            ->where('id_pengguna', $idPengguna)
            ->where('dibaca', false)
            ->update(['dibaca' => true]);

        return response()->json(['ok' => true]);
    }

    private function idPengguna(Request $request): ?string
    {
        $id = $request->session()->get('pengguna_id');

        return filled($id) ? (string) $id : null;
    }
}
