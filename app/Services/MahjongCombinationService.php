<?php

namespace App\Services;

use Illuminate\Support\Str;

class MahjongCombinationService
{
    public const POINT_PER_HAND = 10;

    /**
     * Urutan kartu yang dipakai untuk normalisasi dan pencarian meld.
     *
     * @var array<int, string>
     */
    private const URUTAN = [
        'w1', 'w2', 'w3', 'w4', 'w5', 'w6', 'w7', 'w8', 'w9',
        't1', 't2', 't3', 't4', 't5', 't6', 't7', 't8', 't9',
        'b1', 'b2', 'b3', 'b4', 'b5', 'b6', 'b7', 'b8', 'b9',
        'z1', 'z2', 'z3', 'z4', 'z5', 'z6', 'z7',
    ];

    /**
     * @var array<string, string>
     */
    private const LABEL_KARTU = [
        'w1' => '1 Wan', 'w2' => '2 Wan', 'w3' => '3 Wan', 'w4' => '4 Wan', 'w5' => '5 Wan',
        'w6' => '6 Wan', 'w7' => '7 Wan', 'w8' => '8 Wan', 'w9' => '9 Wan',
        't1' => '1 Tong', 't2' => '2 Tong', 't3' => '3 Tong', 't4' => '4 Tong', 't5' => '5 Tong',
        't6' => '6 Tong', 't7' => '7 Tong', 't8' => '8 Tong', 't9' => '9 Tong',
        'b1' => '1 Bambu', 'b2' => '2 Bambu', 'b3' => '3 Bambu', 'b4' => '4 Bambu', 'b5' => '5 Bambu',
        'b6' => '6 Bambu', 'b7' => '7 Bambu', 'b8' => '8 Bambu', 'b9' => '9 Bambu',
        'z1' => 'Angin Timur', 'z2' => 'Angin Selatan', 'z3' => 'Angin Barat', 'z4' => 'Angin Utara',
        'z5' => 'Naga Merah', 'z6' => 'Naga Hijau', 'z7' => 'Naga Putih',
    ];

    /**
     * @var array<string, int>
     */
    private const RANK = [
        'w1' => 1, 'w2' => 2, 'w3' => 3, 'w4' => 4, 'w5' => 5, 'w6' => 6, 'w7' => 7, 'w8' => 8, 'w9' => 9,
        't1' => 10, 't2' => 11, 't3' => 12, 't4' => 13, 't5' => 14, 't6' => 15, 't7' => 16, 't8' => 17, 't9' => 18,
        'b1' => 19, 'b2' => 20, 'b3' => 21, 'b4' => 22, 'b5' => 23, 'b6' => 24, 'b7' => 25, 'b8' => 26, 'b9' => 27,
        'z1' => 28, 'z2' => 29, 'z3' => 30, 'z4' => 31, 'z5' => 32, 'z6' => 33, 'z7' => 34,
    ];

    /**
     * Analisis susunan kartu: validasi winning hand, deteksi kombinasi, dan hitung poin.
     *
     * @param  array<int, string>  $tiles
     * @return array{
     *     tiles: array<int, string>,
     *     is_winning_hand: bool,
     *     points: int,
     *     combinations: array<int, array{type: string, label: string, tiles: array<int, string>}>,
     *     unmatched: array<int, string>,
     *     message: string
     * }
     */
    public function analisa(array $tiles): array
    {
        $tiles = $this->normalisasi($tiles);
        $counts = array_count_values($tiles);

        if ($tiles === []) {
            return $this->hasil($tiles, false, [], $tiles, 'Belum ada kartu yang terdeteksi.');
        }

        if ($this->tujuhPasang($counts, count($tiles))) {
            $combinations = [];

            foreach ($this->urutanKartu($counts) as $tile) {
                $combinations[] = $this->kombinasi('pair', [$tile, $tile]);
            }

            return $this->hasil($tiles, true, $combinations, [], 'Winning hand: tujuh pasang (7 pairs).');
        }

        $dekomposisi = $this->uraikan($counts);

        if ($dekomposisi !== null) {
            $combinations = [];

            foreach ($dekomposisi['melds'] as $meld) {
                $combinations[] = $this->kombinasi($meld['type'], $meld['tiles']);
            }

            $combinations[] = $this->kombinasi('pair', [$dekomposisi['pair'], $dekomposisi['pair']]);

            return $this->hasil($tiles, true, $combinations, [], 'Chicken hand terdeteksi (4 meld + 1 pair).');
        }

        [$combinations, $unmatched] = $this->deteksiKombinasi($counts);

        return $this->hasil(
            $tiles,
            false,
            $combinations,
            $unmatched,
            'Susunan ini belum membentuk winning hand (butuh 4 meld + 1 pair).',
        );
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    public function daftarKartu(): array
    {
        return array_map(
            fn (string $tile): array => ['code' => $tile, 'label' => self::LABEL_KARTU[$tile]],
            self::URUTAN,
        );
    }

    /**
     * @param  array<int, mixed>  $tiles
     * @return array<int, string>
     */
    public function normalisasi(array $tiles): array
    {
        $hasil = [];

        foreach ($tiles as $tile) {
            $kode = $this->normalisasiKode((string) $tile);

            if ($kode !== null) {
                $hasil[] = $kode;
            }
        }

        usort($hasil, fn (string $a, string $b): int => self::RANK[$a] <=> self::RANK[$b]);

        return $hasil;
    }

    /**
     * Ubah label deteksi AI menjadi kode kartu internal.
     */
    public function normalisasiKode(string $tile): ?string
    {
        $tile = Str::lower(trim($tile));

        if (isset(self::RANK[$tile])) {
            return $tile;
        }

        $tile = str_replace(['_', '-', ' '], '', $tile);

        if (isset(self::RANK[$tile])) {
            return $tile;
        }

        $alias = [
            'east' => 'z1', 'south' => 'z2', 'west' => 'z3', 'north' => 'z4',
            'red' => 'z5', 'green' => 'z6', 'white' => 'z7',
            'eastwind' => 'z1', 'southwind' => 'z2', 'westwind' => 'z3', 'northwind' => 'z4',
            'reddragon' => 'z5', 'greendragon' => 'z6', 'whitedragon' => 'z7',
            'timur' => 'z1', 'selatan' => 'z2', 'barat' => 'z3', 'utara' => 'z4',
            'merah' => 'z5', 'hijau' => 'z6', 'putih' => 'z7',
            'angintimur' => 'z1', 'anginselatan' => 'z2', 'anginbarat' => 'z3', 'anginutara' => 'z4',
            'nagamerah' => 'z5', 'nagahijau' => 'z6', 'nagaputih' => 'z7',
        ];

        if (isset($alias[$tile])) {
            return $alias[$tile];
        }

        // Format label umum dari dataset: 1m/1p/1s, man/pin/sou, 1wan, 1tong, 1bambu.
        if (preg_match('/^([1-9])(?:m|man|wan|char|character|c)$/', $tile, $matches)) {
            return 'w'.$matches[1];
        }

        if (preg_match('/^([1-9])(?:p|pin|dot|dots|circle|tong|t)$/', $tile, $matches)) {
            return 't'.$matches[1];
        }

        if (preg_match('/^([1-9])(?:s|sou|bam|bambu|bamboo|b)$/', $tile, $matches)) {
            return 'b'.$matches[1];
        }

        if (preg_match('/^(?:wan|w|man)([1-9])$/', $tile, $matches)) {
            return 'w'.$matches[1];
        }

        if (preg_match('/^(?:tong|t|pin|dot)([1-9])$/', $tile, $matches)) {
            return 't'.$matches[1];
        }

        if (preg_match('/^(?:bambu|bamboo|b|sou|s)([1-9])$/', $tile, $matches)) {
            return 'b'.$matches[1];
        }

        return null;
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function tujuhPasang(array $counts, int $jumlahKartu): bool
    {
        if ($jumlahKartu !== 14 || count($counts) !== 7) {
            return false;
        }

        foreach ($counts as $count) {
            if ($count !== 2) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, int>  $counts
     * @return array{pair: string, melds: array<int, array{type: string, tiles: array<int, string>}>}|null
     */
    private function uraikan(array $counts): ?array
    {
        foreach ($counts as $tile => $count) {
            if ($count < 2) {
                continue;
            }

            $sisa = $counts;
            $this->kurangi($sisa, $tile, 2);
            $melds = [];

            if ($this->cariMeld($sisa, $melds) && count($melds) === 4) {
                return ['pair' => $tile, 'melds' => $melds];
            }
        }

        return null;
    }

    /**
     * @param  array<string, int>  $counts
     * @param  array<int, array{type: string, tiles: array<int, string>}>  $melds
     */
    private function cariMeld(array $counts, array &$melds): bool
    {
        if ($counts === []) {
            return true;
        }

        $tile = $this->tileTerendah($counts);

        if (($counts[$tile] ?? 0) >= 4) {
            $sisa = $counts;
            $this->kurangi($sisa, $tile, 4);
            $melds[] = ['type' => 'kong', 'tiles' => [$tile, $tile, $tile, $tile]];

            if ($this->cariMeld($sisa, $melds)) {
                return true;
            }

            array_pop($melds);
        }

        if (($counts[$tile] ?? 0) >= 3) {
            $sisa = $counts;
            $this->kurangi($sisa, $tile, 3);
            $melds[] = ['type' => 'pung', 'tiles' => [$tile, $tile, $tile]];

            if ($this->cariMeld($sisa, $melds)) {
                return true;
            }

            array_pop($melds);
        }

        $berikut = $this->tileBerikut($tile, 1);
        $berikutDua = $this->tileBerikut($tile, 2);

        if ($berikut !== null && $berikutDua !== null
            && ($counts[$berikut] ?? 0) > 0
            && ($counts[$berikutDua] ?? 0) > 0) {
            $sisa = $counts;
            $this->kurangi($sisa, $tile, 1);
            $this->kurangi($sisa, $berikut, 1);
            $this->kurangi($sisa, $berikutDua, 1);
            $melds[] = ['type' => 'chow', 'tiles' => [$tile, $berikut, $berikutDua]];

            if ($this->cariMeld($sisa, $melds)) {
                return true;
            }

            array_pop($melds);
        }

        return false;
    }

    /**
     * Deteksi kombinasi secara greedy untuk susunan yang belum winning.
     *
     * @param  array<string, int>  $counts
     * @return array{0: array<int, array{type: string, label: string, tiles: array<int, string>}>, 1: array<int, string>}
     */
    private function deteksiKombinasi(array $counts): array
    {
        $sisa = $counts;
        $combinations = [];

        foreach ($this->urutanKartu($sisa) as $tile) {
            while (($sisa[$tile] ?? 0) >= 4) {
                $this->kurangi($sisa, $tile, 4);
                $combinations[] = $this->kombinasi('kong', [$tile, $tile, $tile, $tile]);
            }
        }

        foreach ($this->urutanKartu($sisa) as $tile) {
            while (($sisa[$tile] ?? 0) >= 3) {
                $this->kurangi($sisa, $tile, 3);
                $combinations[] = $this->kombinasi('pung', [$tile, $tile, $tile]);
            }
        }

        foreach ($this->urutanKartu($sisa) as $tile) {
            while (($sisa[$tile] ?? 0) > 0) {
                $berikut = $this->tileBerikut($tile, 1);
                $berikutDua = $this->tileBerikut($tile, 2);

                if ($berikut === null || $berikutDua === null
                    || ($sisa[$berikut] ?? 0) <= 0
                    || ($sisa[$berikutDua] ?? 0) <= 0) {
                    break;
                }

                $this->kurangi($sisa, $tile, 1);
                $this->kurangi($sisa, $berikut, 1);
                $this->kurangi($sisa, $berikutDua, 1);
                $combinations[] = $this->kombinasi('chow', [$tile, $berikut, $berikutDua]);
            }
        }

        foreach ($this->urutanKartu($sisa) as $tile) {
            while (($sisa[$tile] ?? 0) >= 2) {
                $this->kurangi($sisa, $tile, 2);
                $combinations[] = $this->kombinasi('pair', [$tile, $tile]);
            }
        }

        $unmatched = [];

        foreach ($this->urutanKartu($sisa) as $tile) {
            for ($i = 0; $i < ($sisa[$tile] ?? 0); $i++) {
                $unmatched[] = $tile;
            }
        }

        return [$combinations, $unmatched];
    }

    /**
     * @param  array<int, string>  $tiles
     * @param  array<int, array{type: string, label: string, tiles: array<int, string>}>  $combinations
     * @param  array<int, string>  $unmatched
     * @return array{
     *     tiles: array<int, string>,
     *     is_winning_hand: bool,
     *     points: int,
     *     combinations: array<int, array{type: string, label: string, tiles: array<int, string>}>,
     *     unmatched: array<int, string>,
     *     message: string
     * }
     */
    private function hasil(array $tiles, bool $winning, array $combinations, array $unmatched, string $message): array
    {
        return [
            'tiles' => $tiles,
            'is_winning_hand' => $winning,
            'points' => $winning ? self::POINT_PER_HAND : 0,
            'combinations' => $combinations,
            'unmatched' => $unmatched,
            'message' => $message,
        ];
    }

    /**
     * @param  array<int, string>  $tiles
     * @return array{type: string, label: string, tiles: array<int, string>}
     */
    private function kombinasi(string $tipe, array $tiles): array
    {
        $labelTipe = match ($tipe) {
            'chow' => 'Chow',
            'pung' => 'Pung',
            'kong' => 'Kong',
            default => 'Pair',
        };

        $labelKartu = array_map(fn (string $tile): string => self::LABEL_KARTU[$tile], $tiles);

        $label = $tipe === 'chow'
            ? $labelTipe.' '.implode('-', array_map(
                fn (string $tile): string => substr($tile, 1),
                $tiles,
            )).' '.$this->namaSuit($tiles[0])
            : $labelTipe.' '.self::LABEL_KARTU[$tiles[0]];

        return ['type' => $tipe, 'label' => $label, 'tiles' => $tiles];
    }

    private function namaSuit(string $tile): string
    {
        return match ($tile[0]) {
            'w' => 'Wan',
            't' => 'Tong',
            'b' => 'Bambu',
            default => '',
        };
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function kurangi(array &$counts, string $tile, int $jumlah): void
    {
        $counts[$tile] = ($counts[$tile] ?? 0) - $jumlah;

        if ($counts[$tile] <= 0) {
            unset($counts[$tile]);
        }
    }

    private function tileBerikut(string $tile, int $langkah): ?string
    {
        if (! preg_match('/^([wtb])([1-9])$/', $tile, $matches)) {
            return null;
        }

        $angka = (int) $matches[2] + $langkah;

        return $angka <= 9 ? $matches[1].$angka : null;
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function tileTerendah(array $counts): string
    {
        $tiles = array_keys($counts);
        usort($tiles, fn (string $a, string $b): int => self::RANK[$a] <=> self::RANK[$b]);

        return $tiles[0];
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<int, string>
     */
    private function urutanKartu(array $counts): array
    {
        $tiles = array_keys($counts);
        usort($tiles, fn (string $a, string $b): int => self::RANK[$a] <=> self::RANK[$b]);

        return $tiles;
    }
}
