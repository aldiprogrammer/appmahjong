<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Models\Rank;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RankingController extends Controller
{
    /**
     * Batas jumlah pemain yang ditampilkan di papan peringkat.
     */
    private const BATAS_RANKING = 50;

    public function index(Request $request): Response
    {
        $pengguna = $this->currentPengguna($request);

        $totalPointPerPengguna = Rank::query()
            ->get(['kode_user', 'point'])
            ->groupBy('kode_user')
            ->map(fn ($items, $kodeUser): array => [
                'kode_user' => (string) $kodeUser,
                'point' => (int) $items->sum(fn (Rank $rank): int => (int) $rank->point),
            ])
            ->sortByDesc('point')
            ->values();

        $penggunas = Pengguna::query()
            ->whereIn('kode_user', $totalPointPerPengguna->pluck('kode_user'))
            ->get(['kode_user', 'nama_depan', 'nama_balakang', 'avatar'])
            ->keyBy('kode_user');

        $semuaRanking = $totalPointPerPengguna
            ->filter(fn (array $item): bool => $penggunas->has($item['kode_user']))
            ->values()
            ->map(function (array $item, int $index) use ($penggunas, $pengguna): array {
                $dataPengguna = $penggunas->get($item['kode_user']);
                $nama = trim(($dataPengguna->nama_depan ?? '').' '.($dataPengguna->nama_balakang ?? ''));

                return [
                    'peringkat' => $index + 1,
                    'kode_user' => $item['kode_user'],
                    'nama' => $nama !== '' ? $nama : 'Pemain',
                    'avatar' => $dataPengguna->avatar,
                    'point' => $item['point'],
                    'saya' => $pengguna !== null && $pengguna->kode_user === $item['kode_user'],
                ];
            });

        $peringkatSaya = $pengguna
            ? $semuaRanking->firstWhere('kode_user', $pengguna->kode_user)
            : null;

        return Inertia::render('User/Ranking', [
            'pengguna' => $pengguna,
            'ranking' => $semuaRanking->take(self::BATAS_RANKING)->values(),
            'peringkatSaya' => $peringkatSaya,
            'ringkasan' => [
                'total_pemain' => $semuaRanking->count(),
                'total_point' => $semuaRanking->sum('point'),
                'point_tertinggi' => $semuaRanking->first()['point'] ?? 0,
            ],
        ]);
    }

    private function currentPengguna(Request $request): ?Pengguna
    {
        $id = $request->session()->get('pengguna_id');

        return $id ? Pengguna::query()->find($id) : null;
    }
}
