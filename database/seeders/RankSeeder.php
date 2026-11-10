<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use App\Models\Rank;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RankSeeder extends Seeder
{
    /**
     * Isi data contoh papan peringkat agar halaman ranking langsung terlihat.
     */
    public function run(): void
    {
        $pemain = [
            ['nama_depan' => 'Andi', 'nama_balakang' => 'Pratama', 'point' => 2450],
            ['nama_depan' => 'Siti', 'nama_balakang' => 'Rahma', 'point' => 2180],
            ['nama_depan' => 'Budi', 'nama_balakang' => 'Santoso', 'point' => 1975],
            ['nama_depan' => 'Dewi', 'nama_balakang' => 'Lestari', 'point' => 1760],
            ['nama_depan' => 'Rizky', 'nama_balakang' => 'Maulana', 'point' => 1580],
            ['nama_depan' => 'Nadia', 'nama_balakang' => 'Putri', 'point' => 1420],
            ['nama_depan' => 'Fajar', 'nama_balakang' => 'Nugroho', 'point' => 1250],
            ['nama_depan' => 'Intan', 'nama_balakang' => 'Permata', 'point' => 1105],
            ['nama_depan' => 'Bayu', 'nama_balakang' => 'Setiawan', 'point' => 980],
            ['nama_depan' => 'Citra', 'nama_balakang' => 'Kirana', 'point' => 860],
            ['nama_depan' => 'Dimas', 'nama_balakang' => 'Saputra', 'point' => 720],
            ['nama_depan' => 'Eka', 'nama_balakang' => 'Wulandari', 'point' => 640],
        ];

        DB::transaction(function () use ($pemain): void {
            $kodeUsers = collect($pemain)
                ->map(fn (array $data, int $index): string => 'PG-RANK'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT))
                ->all();

            Rank::query()->whereIn('kode_user', $kodeUsers)->delete();

            foreach ($pemain as $index => $data) {
                $kodeUser = $kodeUsers[$index];

                $pengguna = Pengguna::query()->updateOrCreate(
                    ['kode_user' => $kodeUser],
                    [
                        'nama_depan' => $data['nama_depan'],
                        'nama_balakang' => $data['nama_balakang'],
                        'email' => sprintf('rank%02d@demo.mahjong.test', $index + 1),
                        'nowa' => sprintf('08130000%04d', $index + 1),
                        'password' => bcrypt('password'),
                        'status' => 'aktif',
                        'points' => $data['point'],
                    ],
                );

                Rank::create([
                    'kode_user' => $pengguna->kode_user,
                    'point' => (string) $data['point'],
                ]);
            }
        });
    }
}
