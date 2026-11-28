<?php

namespace Database\Seeders;

use App\Models\Aturandasarfaan;
use App\Models\Aturanmahjong;
use App\Models\Faanhand;
use App\Models\Kategorifaan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FaanRuleSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Isi aturan faan default yang dipakai halaman point user.
     */
    public function run(): void
    {
        $aturan = Aturanmahjong::firstOrCreate(
            ['kode' => 'AM001'],
            [
                'nama_aturan' => 'HK Klasik Tanpa Pangkat',
                'is_active' => true,
            ],
        );

        if (! $aturan->is_active) {
            $aturan->update(['is_active' => true]);
        }

        Aturandasarfaan::firstOrCreate(
            ['id_aturanmahjong' => (string) $aturan->id],
            [
                'minimal_faan' => '3',
                'batas_faan' => '13',
            ],
        );

        $kategori = collect([
            'Combo Tangan',
            'Naga & Angin',
            'Cara Menang',
            'Bunga & Musim',
            'Limit hand',
            'Aturan Rumah',
        ])->mapWithKeys(fn (string $nama): array => [
            $nama => Kategorifaan::firstOrCreate(['nama' => $nama]),
        ]);

        $faanHands = [
            ['Combo Tangan', 'Semua chow', 'All chow', '1', '1'],
            ['Combo Tangan', 'Semua Pong', 'All Pungs', '1', '3'],
            ['Combo Tangan', 'Semua di tangan', 'Concealed Hand', '1', '1'],
            ['Combo Tangan', 'Satu warna campur', 'Mixed one suit', '3', '3'],
            ['Combo Tangan', 'Satu warna', 'Pure one suit', '7', '7'],
            ['Combo Tangan', 'Semua 1, 9 + Angin/Naga', 'Mixed terminals', '1', '1'],
            ['Combo Tangan', 'Kong', 'Kong', '1', '1'],
            ['Naga & Angin', 'Pong Naga Merah', 'Red Dragon Pung', '1', '1'],
            ['Naga & Angin', 'Pong Naga Hijau', 'Green Dragon Pung', '1', '1'],
            ['Naga & Angin', 'Pong Naga Putih', 'White Dragon Pung', '1', '1'],
            ['Naga & Angin', 'Pong angin kursi', 'Seat Wind pung', '1', '1'],
            ['Naga & Angin', 'Pong angin putaran', 'Prevailing Wind Pung', '1', '1'],
            ['Naga & Angin', 'Tiga Naga kecil', 'Small Three Dragons', '5', '5'],
            ['Naga & Angin', 'Tiga Naga besar', 'Big Three Dragons', '8', '8'],
            ['Naga & Angin', 'Empat Angin kecil', 'Small Four Winds', '6', '6'],
            ['Cara Menang', 'Tarik Sendiri', 'Self-Draw', '1', '1'],
            ['Cara Menang', 'Rampok kong', 'Robbing The Kong', '1', '1'],
            ['Cara Menang', 'Menang dari ganti kong', 'Win on Kong Replacement', '1', '1'],
            ['Cara Menang', 'Kong dobel', 'Wind on Double Kong', '1', '1'],
            ['Cara Menang', 'Menang dari ganti bunga', 'Win on Bonus Replacement', '1', '1'],
            ['Cara Menang', 'Ubin terakhir dinding', 'Last tile', '1', '1'],
            ['Bunga & Musim', 'Tanpa bunga', 'No Flowers', '1', '1'],
            ['Bunga & Musim', 'Bunga sendiri', 'Own Flowers', '1', '1'],
            ['Bunga & Musim', 'Musim sendiri', 'Own Season', '1', '1'],
            ['Bunga & Musim', 'Satu set bunga', 'All Four Flowers', '2', '2'],
            ['Bunga & Musim', 'Satu set musim', 'All Four Season', '2', '2'],
            ['Bunga & Musim', 'Semua 8 Bunga & Musim', 'All 8 Flowers & Seasons', '1', '1'],
            ['Limit hand', 'Empat Angin besar', 'Big Four Winds', '13', '13'],
            ['Limit hand', 'Semua Angin & Naga', 'All Honours', '13', '13'],
            ['Limit hand', 'Semua 1 & 9', 'All Terminals', '13', '13'],
            ['Limit hand', 'Tiga belas yatim', 'Thirteen Orphans', '13', '13'],
            ['Limit hand', 'Empat Pong di tangan', 'Four Concealed Pungs', '13', '13'],
            ['Limit hand', 'Sembilan gerbang', 'Nine Gates', '13', '13'],
            ['Limit hand', 'Semua Kong', 'All Kongs', '13', '13'],
            ['Limit hand', 'Menang dari tangan awal', 'Heavenly Hand', '13', '13'],
            ['Limit hand', 'Menang di giliran pertama', 'Earthly Hand', '13', '13'],
            ['Aturan Rumah', 'Tujuh pasang', 'Seven Pairs', '4', '4'],
            ['Aturan Rumah', 'Semua Hijau', 'All Green', '10', '10'],
            ['Aturan Rumah', 'Naga Giok', 'Jade Dragon', '13', '13'],
            ['Aturan Rumah', 'Naga Rubi', 'Ruby Dragon', '13', '13'],
            ['Aturan Rumah', 'Naga Mutiara', 'Pearl Dragon', '13', '13'],
            ['Aturan Rumah', 'Menang buangan putaran pertama', 'Blessing Of Man', '10', '10'],
        ];

        foreach ($faanHands as [$namaKategori, $namaFaan, $inggris, $faanAsli, $faan]) {
            Faanhand::firstOrCreate(
                [
                    'id_aturanmahjong' => (string) $aturan->id,
                    'inggris' => $inggris,
                ],
                [
                    'id_kategorifaan' => (string) $kategori[$namaKategori]->id,
                    'nama_faan' => $namaFaan,
                    'faan_asli' => $faanAsli,
                    'faan' => $faan,
                ],
            );
        }
    }
}
