<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class paket_hargajammain extends Model
{
    protected $fillable = [
        'vanue_id',
        'hari',
        'minimal_jam',
        'jam_mulai',
        'jam_selesai',
        'diskon_persen',
    ];

    protected function casts(): array
    {
        return [
            'minimal_jam' => 'integer',
            'diskon_persen' => 'float',
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
     * Paket terbaik untuk booking: seluruh sesi jam main harus berada di dalam
     * rentang jam paket, total durasi memenuhi syarat minimal jam, lalu diskon
     * persen terbesar lebih dulu dan syarat minimal jam terkecil sebagai pemenang
     * saat seri. Mengembalikan null bila tidak ada paket yang cocok.
     *
     * @param  Collection<int, self>  $paketList
     * @param  Collection<int, jam_main>  $jamMains
     */
    public static function terbaik(Collection $paketList, Collection $jamMains): ?self
    {
        if ($jamMains->isEmpty()) {
            return null;
        }

        $totalMenit = $jamMains->sum(fn (jam_main $jamMain): int => self::durasiMenit($jamMain));

        return $paketList
            ->filter(fn (self $paket): bool => $totalMenit >= $paket->minimal_jam * 60)
            ->filter(fn (self $paket): bool => self::sesiDalamRentang($jamMains, (string) $paket->jam_mulai, (string) $paket->jam_selesai))
            ->sort(fn (self $a, self $b): int => [$b->diskon_persen, $b->minimal_jam] <=> [$a->diskon_persen, $a->minimal_jam])
            ->first();
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
