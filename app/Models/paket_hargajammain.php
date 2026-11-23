<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class paket_hargajammain extends Model
{
    protected $fillable = [
        'vanue_id',
        'hari',
        'minimal_jam',
        'maksimal_jam',
        'jam_mulai',
        'jam_selesai',
        'diskon_persen',
        'harga_paket',
    ];

    protected function casts(): array
    {
        return [
            'minimal_jam' => 'integer',
            'maksimal_jam' => 'integer',
            'diskon_persen' => 'float',
            'harga_paket' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Vanue, $this>
     */
    public function vanue(): BelongsTo
    {
        return $this->belongsTo(Vanue::class);
    }

    /**
     * Cari paket terbaik beserta sesi yang dikenai paket. Bila total durasi
     * melebihi maksimal jam paket, sesi sejumlah maksimal jam yang berada di
     * dalam rentang jam paket tetap memakai paket, sedangkan sesi sisanya
     * memakai harga jam normal. Pemenangnya adalah paket dengan total efektif
     * terendah (paket + sesi normal), dengan minimal jam terkecil sebagai
     * pemenang saat seri.
     *
     * @param  Collection<int, self>  $paketList
     * @param  Collection<int, jam_main>  $jamMains
     * @param  array<int|string, int|float>  $hargaPerJam  harga_akhir per id jam main
     * @return array{paket: self, sesi_paket: array<int, string>, harga_paket: float|null, persen_paket: float}|null
     */
    public static function terbaik(Collection $paketList, Collection $jamMains, array $hargaPerJam = []): ?array
    {
        if ($jamMains->isEmpty()) {
            return null;
        }

        $semuaSesi = $jamMains->values();
        $terbaik = null;
        $efektifTerbaik = PHP_FLOAT_MAX;

        foreach ($paketList as $paket) {
            $kandidat = self::cocok($paket, $semuaSesi, $hargaPerJam);

            if ($kandidat === null) {
                continue;
            }

            if ($kandidat['total_efektif'] < $efektifTerbaik
                || ($kandidat['total_efektif'] === $efektifTerbaik
                    && $paket->minimal_jam < ($terbaik['paket']->minimal_jam ?? PHP_INT_MAX))) {
                $terbaik = $kandidat;
                $efektifTerbaik = $kandidat['total_efektif'];
            }
        }

        if ($terbaik === null) {
            return null;
        }

        return [
            'paket' => $terbaik['paket'],
            'sesi_paket' => $terbaik['sesi_paket'],
            'harga_paket' => $terbaik['harga_paket'],
            'persen_paket' => $terbaik['persen_paket'],
        ];
    }

    /**
     * @param  Collection<int, jam_main>  $semuaSesi
     * @param  array<int|string, int|float>  $hargaPerJam
     * @return array{paket: self, sesi_paket: array<int, string>, harga_paket: float|null, persen_paket: float, total_efektif: float}|null
     */
    private static function cocok(self $paket, Collection $semuaSesi, array $hargaPerJam): ?array
    {
        $totalMenit = $semuaSesi->sum(fn (jam_main $jamMain): int => self::durasiMenit($jamMain));

        if ($totalMenit < $paket->minimal_jam * 60) {
            return null;
        }

        $batasPaket = $paket->maksimal_jam !== null ? $paket->maksimal_jam * 60 : null;

        if ($batasPaket === null || $totalMenit <= $batasPaket) {
            $sesiPaket = $semuaSesi;
        } else {
            $sesiDalamRentang = $semuaSesi
                ->filter(fn (jam_main $jamMain): bool => self::sesiDalamRentang(
                    collect([$jamMain]),
                    (string) $paket->jam_mulai,
                    (string) $paket->jam_selesai,
                ))
                ->sortBy(fn (jam_main $jamMain): int => self::menitDariJam((string) $jamMain->jam_mulai))
                ->values();

            $sesiPaket = collect();
            $akumulasi = 0;

            foreach ($sesiDalamRentang as $jamMain) {
                $durasi = self::durasiMenit($jamMain);

                if ($akumulasi + $durasi > $batasPaket) {
                    break;
                }

                $sesiPaket->push($jamMain);
                $akumulasi += $durasi;
            }

            if ($akumulasi !== $batasPaket) {
                return null;
            }
        }

        if ($sesiPaket->isEmpty()
            || ! self::sesiDalamRentang($sesiPaket, (string) $paket->jam_mulai, (string) $paket->jam_selesai)) {
            return null;
        }

        $sesiPaketIds = $sesiPaket->map(fn (jam_main $jamMain): string => (string) $jamMain->id)->values()->all();
        $hargaNormalPaket = 0;
        $hargaNormalSisa = 0;

        foreach ($semuaSesi as $jamMain) {
            $harga = (int) round((float) ($hargaPerJam[(string) $jamMain->id] ?? 0));

            if (in_array((string) $jamMain->id, $sesiPaketIds, true)) {
                $hargaNormalPaket += $harga;
            } else {
                $hargaNormalSisa += $harga;
            }
        }

        if ($paket->harga_paket !== null) {
            $hargaPaket = (float) $paket->harga_paket;
            $persenPaket = 0.0;
        } else {
            $hargaPaket = null;
            $persenPaket = (float) $paket->diskon_persen;
        }

        $totalEfektif = ($hargaPaket !== null
            ? $hargaPaket
            : $hargaNormalPaket * (1 - $persenPaket / 100)) + $hargaNormalSisa;

        return [
            'paket' => $paket,
            'sesi_paket' => $sesiPaketIds,
            'harga_paket' => $hargaPaket,
            'persen_paket' => $persenPaket,
            'total_efektif' => $totalEfektif,
        ];
    }

    /**
     * @param  Collection<int, jam_main>  $jamMains
     */
    private static function sesiDalamRentang(Collection $jamMains, string $jamMulai, string $jamSelesai): bool
    {
        $rentangMulai = self::menitDariJam($jamMulai);
        $rentangSelesai = self::menitDariJam($jamSelesai);

        // Jam selesai tidak melewati jam mulai berarti rentang melewati tengah malam.
        if ($rentangSelesai <= $rentangMulai) {
            $rentangSelesai += 24 * 60;
        }

        // Rentang seharian penuh (00:00 - 00:00) selalu mencakup semua sesi.
        if ($rentangSelesai - $rentangMulai >= 24 * 60) {
            return true;
        }

        foreach ($jamMains as $jamMain) {
            $sesiMulai = self::menitDariJam((string) $jamMain->jam_mulai);
            $sesiSelesai = self::menitDariJam((string) $jamMain->jam_selesai);

            if ($sesiSelesai <= $sesiMulai) {
                $sesiSelesai += 24 * 60;
            }

            // Sesi dites juga dengan geseran satu hari agar rentang yang melewati
            // tengah malam (mis. 22:00 - 02:00) menangkap sesi dini hari.
            $cocok = false;

            foreach ([0, 24 * 60] as $geser) {
                if ($sesiMulai + $geser >= $rentangMulai && $sesiSelesai + $geser <= $rentangSelesai) {
                    $cocok = true;

                    break;
                }
            }

            if (! $cocok) {
                return false;
            }
        }

        return true;
    }

    /**
     * Durasi satu slot jam main dalam menit, menangani slot yang melewati tengah malam.
     */
    private static function durasiMenit(jam_main $jamMain): int
    {
        $mulai = self::menitDariJam((string) $jamMain->jam_mulai);
        $selesai = self::menitDariJam((string) $jamMain->jam_selesai);
        $durasi = $selesai - $mulai;

        return $durasi > 0 ? $durasi : $durasi + 24 * 60;
    }

    private static function menitDariJam(string $jam): int
    {
        $bagian = array_map('intval', explode(':', substr($jam, 0, 5)));

        return ($bagian[0] ?? 0) * 60 + ($bagian[1] ?? 0);
    }
}
