<?php

namespace App\Services;

use App\Models\Aturanmahjong;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

/**
 * Membaca aturan faan yang dikelola dari admin panel.
 *
 * Kunci faan memakai kolom `inggris` pada tabel faanhands karena tabel
 * tersebut belum memiliki kolom kode. Nilai yang dipakai adalah kolom `faan`,
 * sedangkan `faan_asli` hanya informasi pendamping di admin.
 */
class AturanFaanService
{
    public const KUNCI_CHOW = 'all chow';

    public const KUNCI_PUNG = 'all pungs';

    public const KUNCI_KONG = 'kong';

    public const KUNCI_ALL_KONGS = 'all kongs';

    public const KUNCI_CONCEALED_HAND = 'concealed hand';

    public const KUNCI_MIXED_ONE_SUIT = 'mixed one suit';

    public const KUNCI_PURE_ONE_SUIT = 'pure one suit';

    public const KUNCI_MIXED_TERMINALS = 'mixed terminals';

    public const KUNCI_ALL_HONOURS = 'all honours';

    public const KUNCI_ALL_TERMINALS = 'all terminals';

    public const KUNCI_SEAT_WIND_PUNG = 'seat wind pung';

    public const KUNCI_PREVAILING_WIND_PUNG = 'prevailing wind pung';

    public const KUNCI_RED_DRAGON_PUNG = 'red dragon pung';

    public const KUNCI_GREEN_DRAGON_PUNG = 'green dragon pung';

    public const KUNCI_WHITE_DRAGON_PUNG = 'white dragon pung';

    public const KUNCI_SMALL_THREE_DRAGONS = 'small three dragons';

    public const KUNCI_BIG_THREE_DRAGONS = 'big three dragons';

    public const KUNCI_SMALL_FOUR_WINDS = 'small four winds';

    public const KUNCI_BIG_FOUR_WINDS = 'big four winds';

    public const KUNCI_FOUR_CONCEALED_PUNGS = 'four concealed pungs';

    public const KUNCI_SEVEN_PAIRS = 'seven pairs';

    public const KUNCI_THIRTEEN_ORPHANS = 'thirteen orphans';

    public const KUNCI_NINE_GATES = 'nine gates';

    public const KUNCI_SELF_DRAW = 'self draw';

    public const KUNCI_NO_FLOWERS = 'no flowers';

    public const KUNCI_OWN_FLOWERS = 'own flowers';

    public const KUNCI_OWN_SEASON = 'own season';

    public const KUNCI_LAST_TILE = 'last tile';

    public const KUNCI_ROBBING_THE_KONG = 'robbing the kong';

    public const KUNCI_WIN_ON_KONG_REPLACEMENT = 'win on kong replacement';

    /**
     * Konfigurasi aturan aktif yang sudah dimuat, supaya satu kali analisa
     * hanya membutuhkan satu query.
     *
     * @var array{minimal_faan: int, batas_faan: int, faans: array<string, int>, limit_hands: array<string, bool>, nama_aturan: string|null, terkonfigurasi: bool}|null
     */
    private ?array $konfigurasi = null;

    /**
     * @return array{minimal_faan: int, batas_faan: int, faans: array<string, int>, limit_hands: array<string, bool>, nama_aturan: string|null, terkonfigurasi: bool}
     */
    private function konfigurasi(): array
    {
        if ($this->konfigurasi !== null) {
            return $this->konfigurasi;
        }

        $aturan = Aturanmahjong::query()
            ->with([
                'aturanDasarFaans' => fn (Relation $query): Relation => $query->orderBy('id'),
                'faanHands' => fn (Relation $query): Relation => $query->orderBy('id'),
                'faanHands.kategoriFaan' => fn (Relation $query): Relation => $query->orderBy('id'),
            ])
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        $dasar = $aturan?->aturanDasarFaans->first();
        $minimalFaan = max(0, (int) ($dasar?->minimal_faan ?? 0));
        $batasFaan = max($minimalFaan, (int) ($dasar?->batas_faan ?? 0));
        $faans = [];
        $limitHands = [];

        foreach ($aturan?->faanHands ?? [] as $faanHand) {
            $kunci = $this->kunci((string) $faanHand->inggris);

            if ($kunci !== '') {
                $faans[$kunci] = (int) $faanHand->faan;
                $limitHands[$kunci] = strtolower((string) $faanHand->kategoriFaan?->nama) === 'limit hand';
            }
        }

        return $this->konfigurasi = [
            'minimal_faan' => $minimalFaan,
            'batas_faan' => $batasFaan,
            'faans' => $faans,
            'limit_hands' => $limitHands,
            'nama_aturan' => $aturan?->nama_aturan,
            'terkonfigurasi' => $aturan !== null && $dasar !== null && $faans !== [],
        ];
    }

    public function terkonfigurasi(): bool
    {
        return $this->konfigurasi()['terkonfigurasi'];
    }

    public function namaAturan(): ?string
    {
        return $this->konfigurasi()['nama_aturan'];
    }

    public function minimalFaan(): int
    {
        return $this->konfigurasi()['minimal_faan'];
    }

    public function batasFaan(): int
    {
        return $this->konfigurasi()['batas_faan'];
    }

    public function faan(string $kunci): int
    {
        return $this->konfigurasi()['faans'][$kunci] ?? 0;
    }

    /**
     * Pola yang masuk kategori Limit hand menggantikan faan biasa.
     */
    public function adalahLimitHand(string $kunci): bool
    {
        return $this->konfigurasi()['limit_hands'][$kunci] ?? false;
    }

    /**
     * @return array<string, int>
     */
    public function faanValues(): array
    {
        return $this->konfigurasi()['faans'];
    }

    /**
     * Samakan nama Inggris dari admin dengan kunci yang dipakai mesin hitung.
     */
    private function kunci(string $nama): string
    {
        return trim((string) Str::of($nama)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' '));
    }
}
