<?php

namespace App\Services;

use Illuminate\Support\Str;

class MahjongCombinationService
{
    public const MODE_CHICKEN = 'chicken';

    public const MODE_HK = 'hk';

    public function __construct(private readonly AturanFaanService $aturanFaan) {}

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
        'f1', 'f2', 'f3', 'f4', 'f5', 'f6', 'f7', 'f8',
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
        'f1' => 'Musim Semi (Timur)', 'f2' => 'Musim Panas (Selatan)',
        'f3' => 'Musim Gugur (Barat)', 'f4' => 'Musim Dingin (Utara)',
        'f5' => 'Bunga Plum (Timur)', 'f6' => 'Bunga Anggrek (Selatan)',
        'f7' => 'Bunga Krisan (Barat)', 'f8' => 'Bunga Bambu (Utara)',
    ];

    /**
     * @var array<string, int>
     */
    private const RANK = [
        'w1' => 1, 'w2' => 2, 'w3' => 3, 'w4' => 4, 'w5' => 5, 'w6' => 6, 'w7' => 7, 'w8' => 8, 'w9' => 9,
        't1' => 10, 't2' => 11, 't3' => 12, 't4' => 13, 't5' => 14, 't6' => 15, 't7' => 16, 't8' => 17, 't9' => 18,
        'b1' => 19, 'b2' => 20, 'b3' => 21, 'b4' => 22, 'b5' => 23, 'b6' => 24, 'b7' => 25, 'b8' => 26, 'b9' => 27,
        'z1' => 28, 'z2' => 29, 'z3' => 30, 'z4' => 31, 'z5' => 32, 'z6' => 33, 'z7' => 34,
        'f1' => 35, 'f2' => 36, 'f3' => 37, 'f4' => 38,
        'f5' => 39, 'f6' => 40, 'f7' => 41, 'f8' => 42,
    ];

    /**
     * Bunga yang cocok untuk setiap angin kursi.
     *
     * @var array<string, array<int, string>>
     */
    private const BUNGA_KURSI = [
        'z1' => ['f1', 'f5'],
        'z2' => ['f2', 'f6'],
        'z3' => ['f3', 'f7'],
        'z4' => ['f4', 'f8'],
    ];

    /**
     * Analisis susunan kartu: validasi winning hand, deteksi kombinasi, dan hitung poin.
     *
     * Mode "chicken" menjumlahkan point per meld memakai nilai faan hand dari
     * admin panel. Bunga yang sesuai posisi duduk memakai faan Own Flowers atau
     * Own Season; pair tidak dihitung. Mode "hk" menjadikan total faan sebagai
     * point dengan batas minimal dan maksimal dari aturan aktif.
     *
     * Opsi manual HK: angin kursi, self-draw (自摸), kartu tertutup (門前清),
     * tanpa bunga (無花), last tile, robbing kong, dan kong replacement.
     * All Chows, Own Flower, dan Dragon Pung dideteksi otomatis dari kartu.
     * Opsi lain diabaikan pada mode chicken.
     *
     * @param  array<int, string>  $tiles
     * @param  array{seat_wind?: string|null, round_wind?: string|null, self_draw?: bool, concealed?: bool, winning_tile?: string|null, no_flowers?: bool, last_tile?: bool, robbing_kong?: bool, kong_replacement?: bool}  $opsi
     * @return array{
     *     tiles: array<int, string>,
     *     flowers: array<int, string>,
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
        $semua = $this->normalisasi($tiles);
        $flowers = array_values(array_filter($semua, fn (string $tile): bool => $tile[0] === 'f'));
        $hand = array_values(array_filter($semua, fn (string $tile): bool => $tile[0] !== 'f'));

        if (! $this->aturanFaan->terkonfigurasi()) {
            $hasil = $this->hasil(
                $semua,
                false,
                [],
                [],
                'Aturan point belum dikonfigurasi. Aktifkan satu aturan mahjong dan lengkapi aturan dasar faan serta faan hand di admin.',
                $mode,
            );
            $hasil['flowers'] = $flowers;

            return $hasil;
        }

        if ($hand === []) {
            $hasil = $this->hasil([], false, [], [], 'Belum ada kartu yang terdeteksi.', $mode);
            $hasil['tiles'] = $semua;
            $hasil['flowers'] = $flowers;

            return $hasil;
        }

        $hasil = $mode === self::MODE_HK
            ? $this->analisaHk($hand, $this->normalisasiOpsiHk($opsi), $flowers)
            : $this->analisaChicken($hand, $flowers, $this->normalisasiOpsiHk($opsi)['seat_wind']);

        $hasil['tiles'] = $semua;
        $hasil['flowers'] = $flowers;

        return $hasil;
    }

    /**
     * Tangan yang selalu tertutup menurut Tilerank: Seven Pairs dan
     * Thirteen Orphans. Pemain tidak perlu ditanya Concealed/Exposed untuk
     * bentuk ini karena semua ubinnya memang masih di tangan.
     *
     * @param  array<int, string>  $tiles
     */
    public function selaluTertutup(array $tiles): bool
    {
        $semua = $this->normalisasi($tiles);
        $hand = array_values(array_filter($semua, fn (string $tile): bool => $tile[0] !== 'f'));

        if ($hand === []) {
            return false;
        }

        $counts = array_count_values($hand);

        return $this->tujuhPasang($counts, count($hand)) || $this->tigaBelasYatim($counts);
    }

    /**
     * @param  array<string, mixed>  $opsi
     * @return array{seat_wind: string|null, round_wind: string|null, self_draw: bool, concealed: bool, winning_tile: string|null, no_flowers: bool, last_tile: bool, robbing_kong: bool, kong_replacement: bool}
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
            'winning_tile' => $this->normalisasiKode((string) ($opsi['winning_tile'] ?? '')),
            'no_flowers' => (bool) ($opsi['no_flowers'] ?? false),
            'last_tile' => (bool) ($opsi['last_tile'] ?? false),
            'robbing_kong' => (bool) ($opsi['robbing_kong'] ?? false),
            'kong_replacement' => (bool) ($opsi['kong_replacement'] ?? false),
        ];
    }

    /**
     * @param  array<int, string>  $tiles
     * @param  array<int, string>  $flowers
     * @return array<string, mixed>
     */
    private function analisaChicken(array $tiles, array $flowers = [], ?string $seatWind = null): array
    {
        $counts = array_count_values($tiles);

        // Bunga dan musim yang sesuai posisi duduk memakai faan dari admin.
        $bagianBunga = $this->kategoriBungaSendiri($seatWind, $flowers);
        $bungaSendiri = [...$bagianBunga['bunga'], ...$bagianBunga['musim']];
        $bonusBunga = ($bagianBunga['bunga'] !== []
            ? $this->aturanFaan->faan(AturanFaanService::KUNCI_OWN_FLOWERS)
            : 0)
            + ($bagianBunga['musim'] !== []
                ? $this->aturanFaan->faan(AturanFaanService::KUNCI_OWN_SEASON)
                : 0);
        $kombinasiBunga = [
            'type' => 'special',
            'label' => 'Set bunga kursi '.self::LABEL_KARTU[$seatWind ?? 'z1'],
            'tiles' => $bungaSendiri,
        ];

        if ($this->tujuhPasang($counts, count($tiles))) {
            $combinations = [];

            foreach ($this->urutanKartu($counts) as $tile) {
                $combinations[] = $this->kombinasi('pair', [$tile, $tile]);
            }

            $pesan = 'Winning hand: tujuh pasang (7 pairs).';

            if ($bonusBunga > 0) {
                $combinations[] = $kombinasiBunga;
                $pesan .= ' Set bunga kursi +'.$bonusBunga.' point.';
            }

            return $this->hasil($tiles, true, $combinations, [], $pesan, self::MODE_CHICKEN, null, [], $bonusBunga);
        }

        $dekomposisi = $this->uraikan($counts);

        if ($dekomposisi !== null) {
            $combinations = [];

            foreach ($dekomposisi['melds'] as $meld) {
                $combinations[] = $this->kombinasi($meld['type'], $meld['tiles']);
            }

            $combinations[] = $this->kombinasi('pair', [$dekomposisi['pair'], $dekomposisi['pair']]);

            $pesan = 'Chicken hand terdeteksi (4 meld + 1 pair).';

            if ($bonusBunga > 0) {
                $combinations[] = $kombinasiBunga;
                $pesan .= ' Set bunga kursi +'.$bonusBunga.' point.';
            }

            return $this->hasil($tiles, true, $combinations, [], $pesan, self::MODE_CHICKEN, null, [], $bonusBunga);
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
     * Penilaian HK klasik memakai batas faan dari aturan aktif di admin panel.
     * Faan yang dihitung menjadi point.
     *
     * @param  array<int, string>  $tiles
     * @param  array{seat_wind: string|null, round_wind: string|null, self_draw: bool, concealed: bool, winning_tile: string|null, no_flowers: bool, last_tile: bool, robbing_kong: bool, kong_replacement: bool}  $opsi
     * @param  array<int, string>  $flowers
     * @return array<string, mixed>
     */
    private function analisaHk(array $tiles, array $opsi, array $flowers = []): array
    {
        $counts = array_count_values($tiles);
        $jumlah = count($tiles);
        $kondisiTambahan = $this->faanKondisiTambahan($opsi, $flowers);

        // Kong memakai empat kartu fisik tetapi tetap satu meld, sehingga total
        // kartu fisik hand menang bisa 14 sampai 18 (14 + jumlah kong).
        if ($jumlah < 14 || $jumlah > 18) {
            [$combinations, $unmatched] = $this->deteksiKombinasi($counts);

            return $this->hasil(
                $tiles,
                false,
                $combinations,
                $unmatched,
                'HK klasik membutuhkan 14 kartu (4 meld + 1 pair), ditambah satu kartu untuk setiap kong.',
                self::MODE_HK,
            );
        }

        if ($this->tigaBelasYatim($counts)) {
            $detailUtama = [
                'label' => '十三么 (Thirteen Orphans)',
                'faan' => $this->aturanFaan->faan(AturanFaanService::KUNCI_THIRTEEN_ORPHANS),
            ];
            $details = $this->aturanFaan->adalahLimitHand(AturanFaanService::KUNCI_THIRTEEN_ORPHANS)
                ? [$detailUtama]
                : array_merge([$detailUtama], $kondisiTambahan);
            $faan = array_sum(array_column($details, 'faan'));
            $kombinasi = [$this->kombinasiKhusus('十三么 (Thirteen Orphans)', $tiles)];

            if ($this->belumMemenuhiMinimal($faan)) {
                return $this->hasil(
                    $tiles,
                    false,
                    $kombinasi,
                    [],
                    $this->pesanMinimalFaan($faan),
                    self::MODE_HK,
                    $faan,
                    $details,
                );
            }

            $faan = min($this->aturanFaan->batasFaan(), $faan);

            return $this->hasil(
                $tiles,
                true,
                $kombinasi,
                [],
                'HK klasik: 十三么 ('.$faan.' faan).',
                self::MODE_HK,
                $faan,
                $details,
            );
        }

        if ($this->sembilanGerbang($counts)) {
            $detailUtama = [
                'label' => '九子連環 (Nine Gates)',
                'faan' => $this->aturanFaan->faan(AturanFaanService::KUNCI_NINE_GATES),
            ];
            $details = $this->aturanFaan->adalahLimitHand(AturanFaanService::KUNCI_NINE_GATES)
                ? [$detailUtama]
                : array_merge([$detailUtama], $kondisiTambahan);
            $faan = array_sum(array_column($details, 'faan'));
            $kombinasi = [$this->kombinasiKhusus('九子連環 (Nine Gates)', $tiles)];

            if ($this->belumMemenuhiMinimal($faan)) {
                return $this->hasil(
                    $tiles,
                    false,
                    $kombinasi,
                    [],
                    $this->pesanMinimalFaan($faan),
                    self::MODE_HK,
                    $faan,
                    $details,
                );
            }

            $faan = min($this->aturanFaan->batasFaan(), $faan);

            return $this->hasil(
                $tiles,
                true,
                $kombinasi,
                [],
                'HK klasik: 九子連環 ('.$faan.' faan).',
                self::MODE_HK,
                $faan,
                $details,
            );
        }

        if ($this->tujuhPasang($counts, $jumlah)) {
            $combinations = [];

            foreach ($this->urutanKartu($counts) as $tile) {
                $combinations[] = $this->kombinasi('pair', [$tile, $tile]);
            }

            $details = array_merge(
                [['label' => '七對 (Seven Pairs)', 'faan' => $this->aturanFaan->faan(AturanFaanService::KUNCI_SEVEN_PAIRS)]],
                $kondisiTambahan,
            );
            $faan = array_sum(array_column($details, 'faan'));

            if ($this->belumMemenuhiMinimal($faan)) {
                return $this->hasil(
                    $tiles,
                    false,
                    $combinations,
                    [],
                    $this->pesanMinimalFaan($faan),
                    self::MODE_HK,
                    $faan,
                    $details,
                );
            }

            $faan = min($this->aturanFaan->batasFaan(), $faan);

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
            $skor = $this->skorHk($counts, $kandidat, $opsi, $flowers);

            if ($terbaik === null || $skor['faan'] > $terbaik['faan']) {
                $terbaik = $skor + ['dekomposisi' => $kandidat];
            }
        }

        $faan = (int) $terbaik['faan'];
        $details = $terbaik['details'];

        $combinations = [];

        foreach ($terbaik['dekomposisi']['melds'] as $meld) {
            $combinations[] = $this->kombinasi($meld['type'], $meld['tiles']);
        }

        $pair = $terbaik['dekomposisi']['pair'];
        $combinations[] = $this->kombinasi('pair', [$pair, $pair]);

        if ($this->belumMemenuhiMinimal($faan)) {
            return $this->hasil(
                $tiles,
                false,
                $combinations,
                [],
                $this->pesanMinimalFaan($faan),
                self::MODE_HK,
                $faan,
                $details,
            );
        }

        $faan = min($this->aturanFaan->batasFaan(), $faan);

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
     * @param  array{seat_wind: string|null, round_wind: string|null, self_draw: bool, concealed: bool, winning_tile: string|null, no_flowers: bool, last_tile: bool, robbing_kong: bool, kong_replacement: bool}  $opsi
     * @param  array<int, string>  $flowers
     * @return array{faan: int, details: array<int, array{label: string, faan: int}>}
     */
    private function skorHk(array $counts, array $dekomposisi, array $opsi, array $flowers = []): array
    {
        $details = [];
        $detailsLimit = [];
        $tambah = function (string $label, string $kunciFaan) use (&$details, &$detailsLimit): void {
            $detail = ['label' => $label, 'faan' => $this->aturanFaan->faan($kunciFaan)];

            if ($this->aturanFaan->adalahLimitHand($kunciFaan)) {
                $detailsLimit[] = $detail;

                return;
            }

            $details[] = $detail;
        };

        $melds = $dekomposisi['melds'];
        $pair = $dekomposisi['pair'];
        $chows = array_values(array_filter($melds, fn (array $meld): bool => $meld['type'] === 'chow'));
        $pungs = array_values(array_filter($melds, fn (array $meld): bool => $meld['type'] !== 'chow'));
        $kongs = array_values(array_filter($melds, fn (array $meld): bool => $meld['type'] === 'kong'));
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
            $tambah('字一色 (All Honors)', AturanFaanService::KUNCI_ALL_HONOURS);
        } elseif ($semuaTerminal) {
            $tambah('清么九 (All Terminals)', AturanFaanService::KUNCI_ALL_TERMINALS);
        } elseif ($satuSuit !== null) {
            $tambah('清一色 (Full Flush)', AturanFaanService::KUNCI_PURE_ONE_SUIT);
        } elseif ($satuSuitHonor !== null) {
            $tambah('混一色 (Half Flush)', AturanFaanService::KUNCI_MIXED_ONE_SUIT);
        }

        if (count($pungAngin) === 4) {
            $tambah('大四喜 (Great Four Winds)', AturanFaanService::KUNCI_BIG_FOUR_WINDS);
        } elseif (count($pungAngin) === 3 && in_array($pair, $angin, true)) {
            $tambah('小四喜 (Small Four Winds)', AturanFaanService::KUNCI_SMALL_FOUR_WINDS);
        }

        // Pola Tiga Naga sudah termasuk faan Pong Naga yang menyusunnya.
        $polaNagaSudahDihitung = false;

        if (count($pungNaga) === 3) {
            $tambah('大三元 (Great Three Dragons)', AturanFaanService::KUNCI_BIG_THREE_DRAGONS);
            $polaNagaSudahDihitung = true;
        } elseif (count($pungNaga) === 2 && in_array($pair, $naga, true)) {
            $tambah('小三元 (Small Three Dragons)', AturanFaanService::KUNCI_SMALL_THREE_DRAGONS);
            $polaNagaSudahDihitung = true;
        }

        foreach ($kongs as $kong) {
            $tambah('槓 (Kong) '.self::LABEL_KARTU[$kong['tiles'][0]], AturanFaanService::KUNCI_KONG);
        }

        if (count($kongs) === 4) {
            $tambah('四槓 (All Kongs)', AturanFaanService::KUNCI_ALL_KONGS);
        }

        if ($chows === []) {
            // Sama seperti Tilerank, kong dihitung sebagai pung untuk pola
            // 四暗刻. Hanya pung biasa yang kehilangan status tersembunyi bila
            // ubin kemenangan menutupnya; kong tetap memenuhi pola ini.
            $pungDariBuangan = false;

            if ($opsi['winning_tile'] !== null) {
                foreach ($pungs as $pung) {
                    if ($pung['type'] === 'pung' && in_array($opsi['winning_tile'], $pung['tiles'], true)) {
                        $pungDariBuangan = true;

                        break;
                    }
                }
            }

            if ($opsi['concealed'] && ! $pungDariBuangan && count($pungs) === 4) {
                $tambah('四暗刻 (Four Concealed Pungs)', AturanFaanService::KUNCI_FOUR_CONCEALED_PUNGS);
            } else {
                $tambah('對對胡 (All Pungs)', AturanFaanService::KUNCI_PUNG);
            }
        } elseif (count($chows) === 4) {
            $tambah('平胡 (All Chows)', AturanFaanService::KUNCI_CHOW);
        }

        foreach ($this->faanKondisiTambahan($opsi, $flowers) as $detail) {
            $details[] = $detail;
        }

        if (! $semuaHonor && ! $semuaTerminal && $this->setiapGrupAdaTerminal($dekomposisi)) {
            $tambah('混么九 (Mixed Terminals)', AturanFaanService::KUNCI_MIXED_TERMINALS);
        }

        if (! $polaNagaSudahDihitung) {
            foreach ($pungNaga as $kode) {
                $kunciNaga = match ($kode) {
                    'z5' => AturanFaanService::KUNCI_RED_DRAGON_PUNG,
                    'z6' => AturanFaanService::KUNCI_GREEN_DRAGON_PUNG,
                    default => AturanFaanService::KUNCI_WHITE_DRAGON_PUNG,
                };

                $tambah(self::LABEL_KARTU[$kode], $kunciNaga);
            }
        }

        if ($opsi['seat_wind'] !== null && in_array($opsi['seat_wind'], $pungAngin, true)) {
            $tambah(self::LABEL_KARTU[$opsi['seat_wind']].' (angin kursi)', AturanFaanService::KUNCI_SEAT_WIND_PUNG);
        }

        if ($opsi['round_wind'] !== null && in_array($opsi['round_wind'], $pungAngin, true)) {
            $tambah(self::LABEL_KARTU[$opsi['round_wind']].' (angin putaran)', AturanFaanService::KUNCI_PREVAILING_WIND_PUNG);
        }

        // Limit hand menggantikan semua faan biasa, seperti perilaku HK klasik
        // di Tilerank: daftar faan hanya memuat pola limit dan totalnya batas faan.
        if ($detailsLimit !== []) {
            return [
                'faan' => $this->aturanFaan->batasFaan(),
                'details' => $detailsLimit,
            ];
        }

        return [
            'faan' => array_sum(array_column($details, 'faan')),
            'details' => $details,
        ];
    }

    /**
     * Kondisi manual dan bunga yang berlaku untuk semua bentuk winning hand.
     *
     * @param  array{seat_wind: string|null, round_wind: string|null, self_draw: bool, concealed: bool, winning_tile: string|null, no_flowers: bool, last_tile: bool, robbing_kong: bool, kong_replacement: bool}  $opsi
     * @param  array<int, string>  $flowers
     * @return array<int, array{label: string, faan: int}>
     */
    private function faanKondisiTambahan(array $opsi, array $flowers): array
    {
        $details = [];

        if ($opsi['concealed']) {
            $details[] = [
                'label' => '門前清 (Concealed Hand)',
                'faan' => $this->aturanFaan->faan(AturanFaanService::KUNCI_CONCEALED_HAND),
            ];
        }

        if ($opsi['self_draw']) {
            $details[] = [
                'label' => '自摸 (Self-draw)',
                'faan' => $this->aturanFaan->faan(AturanFaanService::KUNCI_SELF_DRAW),
            ];
        }

        if ($opsi['no_flowers'] && $flowers === []) {
            $details[] = [
                'label' => '無花 (No Flowers)',
                'faan' => $this->aturanFaan->faan(AturanFaanService::KUNCI_NO_FLOWERS),
            ];
        }

        $bagianBunga = $this->kategoriBungaSendiri($opsi['seat_wind'], $flowers);

        if ($bagianBunga['bunga'] !== []) {
            $details[] = [
                'label' => '正花 (Own Flowers)',
                'faan' => $this->aturanFaan->faan(AturanFaanService::KUNCI_OWN_FLOWERS),
            ];
        }

        if ($bagianBunga['musim'] !== []) {
            $details[] = [
                'label' => '季節 (Own Season)',
                'faan' => $this->aturanFaan->faan(AturanFaanService::KUNCI_OWN_SEASON),
            ];
        }

        if ($opsi['last_tile']) {
            $details[] = [
                'label' => '海底撈月 (Last Tile)',
                'faan' => $this->aturanFaan->faan(AturanFaanService::KUNCI_LAST_TILE),
            ];
        }

        if ($opsi['robbing_kong']) {
            $details[] = [
                'label' => '搶槓 (Robbing Kong)',
                'faan' => $this->aturanFaan->faan(AturanFaanService::KUNCI_ROBBING_THE_KONG),
            ];
        }

        if ($opsi['kong_replacement']) {
            $details[] = [
                'label' => '槓上開花 (Kong Replacement)',
                'faan' => $this->aturanFaan->faan(AturanFaanService::KUNCI_WIN_ON_KONG_REPLACEMENT),
            ];
        }

        return $details;
    }

    /**
     * @param  array<int, string>  $flowers
     * @return array<int, string>
     */
    private function bungaSendiri(?string $seatWind, array $flowers): array
    {
        if ($seatWind === null) {
            return [];
        }

        return array_values(array_intersect(self::BUNGA_KURSI[$seatWind] ?? [], $flowers));
    }

    /**
     * @param  array<int, string>  $flowers
     * @return array{bunga: array<int, string>, musim: array<int, string>}
     */
    private function kategoriBungaSendiri(?string $seatWind, array $flowers): array
    {
        $sendiri = $this->bungaSendiri($seatWind, $flowers);

        return [
            'bunga' => array_values(array_filter(
                $sendiri,
                fn (string $tile): bool => in_array($tile, ['f5', 'f6', 'f7', 'f8'], true),
            )),
            'musim' => array_values(array_filter(
                $sendiri,
                fn (string $tile): bool => in_array($tile, ['f1', 'f2', 'f3', 'f4'], true),
            )),
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
            'spring' => 'f1', 'summer' => 'f2', 'autumn' => 'f3', 'winter' => 'f4',
            'plum' => 'f5', 'orchid' => 'f6', 'chrysanthemum' => 'f7', 'bambooflower' => 'f8',
            'musimsemi' => 'f1', 'musimpanas' => 'f2', 'musimgugur' => 'f3', 'musimdingin' => 'f4',
            'bungaplum' => 'f5', 'bungaanggrek' => 'f6', 'bungakrisan' => 'f7', 'bungabambu' => 'f8',
            'flower1' => 'f5', 'flower2' => 'f6', 'flower3' => 'f7', 'flower4' => 'f8',
            'season1' => 'f1', 'season2' => 'f2', 'season3' => 'f3', 'season4' => 'f4',
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

    private function belumMemenuhiMinimal(int $faan): bool
    {
        return $faan < $this->aturanFaan->minimalFaan();
    }

    private function pesanMinimalFaan(int $faan): string
    {
        return 'Belum bisa menang: total '.$faan.' faan, minimal '.$this->aturanFaan->minimalFaan().' faan.';
    }

    /**
     * @param  array<int, string>  $tiles
     * @param  array<int, array{type: string, label: string, tiles: array<int, string>}>  $combinations
     * @param  array<int, string>  $unmatched
     * @param  array<int, array{label: string, faan: int}>  $faanDetails
     * @return array{
     *     tiles: array<int, string>,
     *     flowers: array<int, string>,
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
        int $bonusChicken = 0,
    ): array {
        $points = 0;

        if ($winning) {
            $points = $mode === self::MODE_HK
                ? min($this->aturanFaan->batasFaan(), (int) $faan)
                : $this->poinChicken($combinations) + $bonusChicken;
        }

        return [
            'tiles' => $tiles,
            'flowers' => [],
            'is_winning_hand' => $winning,
            'points' => $points,
            'combinations' => $combinations,
            'unmatched' => $unmatched,
            'message' => $message,
            'scoring_mode' => $mode,
            'faan' => $mode === self::MODE_HK ? $faan : null,
            'faan_details' => $faanDetails,
        ];
    }

    /**
     * Point chicken hand dijumlahkan per meld. Pair dan kombinasi bunga
     * ditambahkan terpisah lewat bonus. Bunga kursi tidak termasuk di sini.
     *
     * @param  array<int, array{type: string, label: string, tiles: array<int, string>}>  $combinations
     */
    private function poinChicken(array $combinations): int
    {
        return array_sum(array_map(
            fn (array $combination): int => match ($combination['type']) {
                'chow' => $this->aturanFaan->faan(AturanFaanService::KUNCI_CHOW),
                'pung' => $this->aturanFaan->faan(AturanFaanService::KUNCI_PUNG),
                'kong' => $this->aturanFaan->faan(AturanFaanService::KUNCI_KONG),
                default => 0,
            },
            $combinations,
        ));
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
