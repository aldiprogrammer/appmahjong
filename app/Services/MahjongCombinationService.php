<?php

namespace App\Services;

use Illuminate\Support\Str;

class MahjongCombinationService
{
    public const POINT_PER_HAND = 10;

    public const MODE_CHICKEN = 'chicken';

    public const MODE_HK = 'hk';

    public const HK_MIN_FAAN = 3;

    public const HK_MAX_FAAN = 13;

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
     * Mode "chicken" memberi 10 point untuk setiap chicken hand. Mode "hk"
     * memakai penilaian HK klasik 3-13 faan dan menjadikan faan sebagai point.
     *
     * Opsi manual HK: angin kursi, angin meja, self-draw (自摸), dan kartu
     * tertutup (門前清). Opsi lain diabaikan pada mode chicken.
     *
     * @param  array<int, string>  $tiles
     * @param  array{seat_wind?: string|null, round_wind?: string|null, self_draw?: bool, concealed?: bool}  $opsi
     * @return array{
     *     tiles: array<int, string>,
     *     is_winning_hand: bool,
     *     points: int,
     *     combinations: array<int, array{type: string, label: string, tiles: array<int, string>}>,
     *     unmatched: array<int, string>,
     *     message: string,
     *     scoring_mode: string,
     *     faan: int|null,
     *     faan_details: array<int, array{label: string, faan: int}>
     * }
     */
    public function analisa(array $tiles, string $mode = self::MODE_CHICKEN, array $opsi = []): array
    {
        $mode = $mode === self::MODE_HK ? self::MODE_HK : self::MODE_CHICKEN;
        $tiles = $this->normalisasi($tiles);

        if ($tiles === []) {
            return $this->hasil($tiles, false, [], $tiles, 'Belum ada kartu yang terdeteksi.', $mode);
        }

        return $mode === self::MODE_HK
            ? $this->analisaHk($tiles, $this->normalisasiOpsiHk($opsi))
            : $this->analisaChicken($tiles);
    }

    /**
     * @param  array<string, mixed>  $opsi
     * @return array{seat_wind: string|null, round_wind: string|null, self_draw: bool, concealed: bool}
     */
    private function normalisasiOpsiHk(array $opsi): array
    {
        $angin = ['z1', 'z2', 'z3', 'z4'];
        $seat = $opsi['seat_wind'] ?? null;
        $round = $opsi['round_wind'] ?? null;

        return [
            'seat_wind' => in_array($seat, $angin, true) ? $seat : null,
            'round_wind' => in_array($round, $angin, true) ? $round : null,
            'self_draw' => (bool) ($opsi['self_draw'] ?? false),
            'concealed' => (bool) ($opsi['concealed'] ?? false),
        ];
    }

    /**
     * @param  array<int, string>  $tiles
     * @return array<string, mixed>
     */
    private function analisaChicken(array $tiles): array
    {
        $counts = array_count_values($tiles);

        if ($this->tujuhPasang($counts, count($tiles))) {
            $combinations = [];

            foreach ($this->urutanKartu($counts) as $tile) {
                $combinations[] = $this->kombinasi('pair', [$tile, $tile]);
            }

            return $this->hasil($tiles, true, $combinations, [], 'Winning hand: tujuh pasang (7 pairs).', self::MODE_CHICKEN);
        }

        $dekomposisi = $this->uraikan($counts);

        if ($dekomposisi !== null) {
            $combinations = [];

            foreach ($dekomposisi['melds'] as $meld) {
                $combinations[] = $this->kombinasi($meld['type'], $meld['tiles']);
            }

            $combinations[] = $this->kombinasi('pair', [$dekomposisi['pair'], $dekomposisi['pair']]);

            return $this->hasil($tiles, true, $combinations, [], 'Chicken hand terdeteksi (4 meld + 1 pair).', self::MODE_CHICKEN);
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
     * Penilaian HK klasik 3-13 faan. Faan yang dihitung menjadi point.
     *
     * @param  array<int, string>  $tiles
     * @param  array{seat_wind: string|null, round_wind: string|null, self_draw: bool, concealed: bool}  $opsi
     * @return array<string, mixed>
     */
    private function analisaHk(array $tiles, array $opsi): array
    {
        $counts = array_count_values($tiles);
        $jumlah = count($tiles);

        if ($jumlah !== 14) {
            [$combinations, $unmatched] = $this->deteksiKombinasi($counts);

            return $this->hasil(
                $tiles,
                false,
                $combinations,
                $unmatched,
                'HK klasik membutuhkan 14 kartu (4 meld + 1 pair).',
                self::MODE_HK,
            );
        }

        if ($this->tigaBelasYatim($counts)) {
            return $this->hasil(
                $tiles,
                true,
                [$this->kombinasiKhusus('十三么 (Thirteen Orphans)', $tiles)],
                [],
                'HK klasik: 十三么 (13 faan).',
                self::MODE_HK,
                13,
                [['label' => '十三么 (Thirteen Orphans)', 'faan' => 13]],
            );
        }

        if ($this->sembilanGerbang($counts)) {
            return $this->hasil(
                $tiles,
                true,
                [$this->kombinasiKhusus('九子連環 (Nine Gates)', $tiles)],
                [],
                'HK klasik: 九子連環 (13 faan).',
                self::MODE_HK,
                13,
                [['label' => '九子連環 (Nine Gates)', 'faan' => 13]],
            );
        }

        if ($this->tujuhPasang($counts, $jumlah)) {
            $combinations = [];

            foreach ($this->urutanKartu($counts) as $tile) {
                $combinations[] = $this->kombinasi('pair', [$tile, $tile]);
            }

            $details = [['label' => '七對 (Seven Pairs)', 'faan' => 10]];

            if ($opsi['self_draw']) {
                $details[] = ['label' => '自摸 (Self-draw)', 'faan' => 1];
            }

            $faan = min(self::HK_MAX_FAAN, array_sum(array_column($details, 'faan')));

            return $this->hasil(
                $tiles,
                true,
                $combinations,
                [],
                'HK klasik: tujuh pasang ('.$faan.' faan).',
                self::MODE_HK,
                $faan,
                $details,
            );
        }

        $dekomposisi = $this->uraikanSemua($counts);

        if ($dekomposisi === []) {
            [$combinations, $unmatched] = $this->deteksiKombinasi($counts);

            return $this->hasil(
                $tiles,
                false,
                $combinations,
                $unmatched,
                'Susunan ini belum membentuk winning hand HK (4 meld + 1 pair, 7 pasang, atau 十三么).',
                self::MODE_HK,
            );
        }

        $terbaik = null;

        foreach ($dekomposisi as $kandidat) {
            $skor = $this->skorHk($counts, $kandidat, $opsi);

            if ($terbaik === null || $skor['faan'] > $terbaik['faan']) {
                $terbaik = $skor + ['dekomposisi' => $kandidat];
            }
        }

        $faan = (int) $terbaik['faan'];
        $details = $terbaik['details'];

        if ($faan < self::HK_MIN_FAAN) {
            $details[] = ['label' => 'Minimum HK klasik', 'faan' => self::HK_MIN_FAAN - $faan];
            $faan = self::HK_MIN_FAAN;
        }

        if ($faan > self::HK_MAX_FAAN) {
            $faan = self::HK_MAX_FAAN;
        }

        $combinations = [];

        foreach ($terbaik['dekomposisi']['melds'] as $meld) {
            $combinations[] = $this->kombinasi($meld['type'], $meld['tiles']);
        }

        $pair = $terbaik['dekomposisi']['pair'];
        $combinations[] = $this->kombinasi('pair', [$pair, $pair]);

        return $this->hasil(
            $tiles,
            true,
            $combinations,
            [],
            'HK klasik: '.$faan.' faan.',
            self::MODE_HK,
            $faan,
            $details,
        );
    }

    /**
     * Hitung faan dari satu dekomposisi 4 meld + 1 pair.
     *
     * @param  array<string, int>  $counts
     * @param  array{pair: string, melds: array<int, array{type: string, tiles: array<int, string>}>}  $dekomposisi
     * @param  array{seat_wind: string|null, round_wind: string|null, self_draw: bool, concealed: bool}  $opsi
     * @return array{faan: int, details: array<int, array{label: string, faan: int}>}
     */
    private function skorHk(array $counts, array $dekomposisi, array $opsi): array
    {
        $details = [];
        $tambah = function (string $label, int $faan) use (&$details): void {
            $details[] = ['label' => $label, 'faan' => $faan];
        };

        $melds = $dekomposisi['melds'];
        $pair = $dekomposisi['pair'];
        $chows = array_values(array_filter($melds, fn (array $meld): bool => $meld['type'] === 'chow'));
        $pungs = array_values(array_filter($melds, fn (array $meld): bool => $meld['type'] !== 'chow'));
        $kongs = array_filter($melds, fn (array $meld): bool => $meld['type'] === 'kong');
        $kodePung = array_map(fn (array $meld): string => $meld['tiles'][0], $pungs);

        $angin = ['z1', 'z2', 'z3', 'z4'];
        $naga = ['z5', 'z6', 'z7'];
        $pungAngin = array_values(array_intersect($angin, $kodePung));
        $pungNaga = array_values(array_intersect($naga, $kodePung));

        $semuaHonor = $this->semuaHonor($counts);
        $semuaTerminal = $this->semuaTerminal($counts);
        $satuSuit = $this->satuSuit($counts);
        $satuSuitHonor = $this->satuSuitDenganHonor($counts);

        if ($semuaHonor) {
            $tambah('字一色 (All Honors)', 13);
        } elseif ($semuaTerminal) {
            $tambah('清么九 (All Terminals)', 13);
        } elseif ($satuSuit !== null) {
            $tambah('清一色 (Full Flush)', 7);
        } elseif ($satuSuitHonor !== null) {
            $tambah('混一色 (Half Flush)', 3);
        }

        if (count($pungAngin) === 4) {
            $tambah('大四喜 (Great Four Winds)', 13);
        } elseif (count($pungAngin) === 3 && in_array($pair, $angin, true)) {
            $tambah('小四喜 (Small Four Winds)', 10);
        }

        if (count($pungNaga) === 3) {
            $tambah('大三元 (Great Three Dragons)', 10);
        } elseif (count($pungNaga) === 2 && in_array($pair, $naga, true)) {
            $tambah('小三元 (Small Three Dragons)', 6);
        }

        if (count($kongs) >= 4) {
            $tambah('四槓 (Four Kongs)', 13);
        } elseif (count($kongs) === 3) {
            $tambah('三槓 (Three Kongs)', 5);
        }

        if ($chows === []) {
            if ($opsi['concealed']) {
                $tambah('四暗刻 (Four Concealed Pungs)', 13);
            } else {
                $tambah('對對胡 (All Pungs)', 3);
            }
        } elseif (count($chows) === 4) {
            $tambah('平胡 (All Chows)', 1);
        }

        if ($opsi['concealed'] && $chows !== []) {
            $tambah('門前清 (Concealed Hand)', 1);
        }

        if ($opsi['self_draw']) {
            $tambah('自摸 (Self-draw)', 1);
        }

        if (! $semuaHonor && ! $semuaTerminal && $this->setiapGrupAdaTerminal($dekomposisi)) {
            $tambah('混么九 (Mixed Terminals)', 3);
        }

        foreach ($pungNaga as $kode) {
            $tambah(self::LABEL_KARTU[$kode], 1);
        }

        foreach ($pungAngin as $kode) {
            $faanAngin = 0;

            if ($kode === $opsi['seat_wind']) {
                $faanAngin++;
            }

            if ($kode === $opsi['round_wind']) {
                $faanAngin++;
            }

            if ($faanAngin > 0) {
                $tambah(self::LABEL_KARTU[$kode].' (angin)', $faanAngin);
            }
        }

        return [
            'faan' => array_sum(array_column($details, 'faan')),
            'details' => $details,
        ];
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function tigaBelasYatim(array $counts): bool
    {
        $wajib = ['w1', 'w9', 't1', 't9', 'b1', 'b9', 'z1', 'z2', 'z3', 'z4', 'z5', 'z6', 'z7'];

        if (count($counts) !== 13 || array_sum($counts) !== 14) {
            return false;
        }

        foreach ($wajib as $tile) {
            if (($counts[$tile] ?? 0) < 1) {
                return false;
            }
        }

        return max($counts) === 2;
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function sembilanGerbang(array $counts): bool
    {
        $suit = null;

        foreach (array_keys($counts) as $tile) {
            if ($tile[0] === 'z') {
                return false;
            }

            $suit ??= $tile[0];

            if ($tile[0] !== $suit) {
                return false;
            }
        }

        if ($suit === null || array_sum($counts) !== 14) {
            return false;
        }

        for ($angka = 1; $angka <= 9; $angka++) {
            if (($counts[$suit.$angka] ?? 0) < 1) {
                return false;
            }
        }

        return ($counts[$suit.'1'] ?? 0) >= 3 && ($counts[$suit.'9'] ?? 0) >= 3;
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function semuaHonor(array $counts): bool
    {
        foreach (array_keys($counts) as $tile) {
            if ($tile[0] !== 'z') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function semuaTerminal(array $counts): bool
    {
        foreach (array_keys($counts) as $tile) {
            if ($tile[0] === 'z' || ! in_array($tile[1], ['1', '9'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Satu suit angka tanpa honor.
     *
     * @param  array<string, int>  $counts
     */
    private function satuSuit(array $counts): ?string
    {
        $suit = null;

        foreach (array_keys($counts) as $tile) {
            if ($tile[0] === 'z') {
                return null;
            }

            $suit ??= $tile[0];

            if ($tile[0] !== $suit) {
                return null;
            }
        }

        return $suit;
    }

    /**
     * Satu suit angka dicampur honor.
     *
     * @param  array<string, int>  $counts
     */
    private function satuSuitDenganHonor(array $counts): ?string
    {
        $suit = null;
        $adaHonor = false;

        foreach (array_keys($counts) as $tile) {
            if ($tile[0] === 'z') {
                $adaHonor = true;

                continue;
            }

            $suit ??= $tile[0];

            if ($tile[0] !== $suit) {
                return null;
            }
        }

        return $suit !== null && $adaHonor ? $suit : null;
    }

    /**
     * Setiap meld dan pair mengandung terminal (1/9) atau honor.
     *
     * @param  array{pair: string, melds: array<int, array{type: string, tiles: array<int, string>}>}  $dekomposisi
     */
    private function setiapGrupAdaTerminal(array $dekomposisi): bool
    {
        foreach ($dekomposisi['melds'] as $meld) {
            if (! $this->adaTerminalAtauHonor($meld['tiles'])) {
                return false;
            }
        }

        return $this->adaTerminalAtauHonor([$dekomposisi['pair']]);
    }

    /**
     * @param  array<int, string>  $tiles
     */
    private function adaTerminalAtauHonor(array $tiles): bool
    {
        foreach ($tiles as $tile) {
            if ($tile[0] === 'z' || in_array($tile[1], ['1', '9'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<int, array{pair: string, melds: array<int, array{type: string, tiles: array<int, string>}>}>
     */
    private function uraikanSemua(array $counts): array
    {
        $hasil = [];

        foreach ($counts as $tile => $count) {
            if ($count < 2) {
                continue;
            }

            $sisa = $counts;
            $this->kurangi($sisa, $tile, 2);
            $melds = [];
            $this->cariMeldSemua($sisa, $melds, $hasil, $tile);
        }

        return $hasil;
    }

    /**
     * @param  array<string, int>  $counts
     * @param  array<int, array{type: string, tiles: array<int, string>}>  $melds
     * @param  array<int, array{pair: string, melds: array<int, array{type: string, tiles: array<int, string>}>}>  $hasil
     */
    private function cariMeldSemua(array $counts, array &$melds, array &$hasil, string $pair): void
    {
        if ($counts === []) {
            if (count($melds) === 4) {
                $hasil[] = ['pair' => $pair, 'melds' => $melds];
            }

            return;
        }

        if (count($melds) >= 4) {
            return;
        }

        $tile = $this->tileTerendah($counts);

        if (($counts[$tile] ?? 0) >= 4) {
            $sisa = $counts;
            $this->kurangi($sisa, $tile, 4);
            $melds[] = ['type' => 'kong', 'tiles' => [$tile, $tile, $tile, $tile]];
            $this->cariMeldSemua($sisa, $melds, $hasil, $pair);
            array_pop($melds);
        }

        if (($counts[$tile] ?? 0) >= 3) {
            $sisa = $counts;
            $this->kurangi($sisa, $tile, 3);
            $melds[] = ['type' => 'pung', 'tiles' => [$tile, $tile, $tile]];
            $this->cariMeldSemua($sisa, $melds, $hasil, $pair);
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
            $this->cariMeldSemua($sisa, $melds, $hasil, $pair);
            array_pop($melds);
        }
    }

    /**
     * @param  array<int, string>  $tiles
     * @return array{type: string, label: string, tiles: array<int, string>}
     */
    private function kombinasiKhusus(string $label, array $tiles): array
    {
        return ['type' => 'special', 'label' => $label, 'tiles' => $tiles];
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
     * @param  array<int, array{label: string, faan: int}>  $faanDetails
     * @return array{
     *     tiles: array<int, string>,
     *     is_winning_hand: bool,
     *     points: int,
     *     combinations: array<int, array{type: string, label: string, tiles: array<int, string>}>,
     *     unmatched: array<int, string>,
     *     message: string,
     *     scoring_mode: string,
     *     faan: int|null,
     *     faan_details: array<int, array{label: string, faan: int}>
     * }
     */
    private function hasil(
        array $tiles,
        bool $winning,
        array $combinations,
        array $unmatched,
        string $message,
        string $mode = self::MODE_CHICKEN,
        ?int $faan = null,
        array $faanDetails = [],
    ): array {
        $points = 0;

        if ($winning) {
            $points = $mode === self::MODE_HK
                ? max(self::HK_MIN_FAAN, min(self::HK_MAX_FAAN, (int) $faan))
                : self::POINT_PER_HAND;
        }

        return [
            'tiles' => $tiles,
            'is_winning_hand' => $winning,
            'points' => $points,
            'combinations' => $combinations,
            'unmatched' => $unmatched,
            'message' => $message,
            'scoring_mode' => $mode,
            'faan' => $mode === self::MODE_HK && $winning ? $points : null,
            'faan_details' => $faanDetails,
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
