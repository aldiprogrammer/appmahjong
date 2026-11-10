<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Pesanan extends Model
{
    public const STATUS_BARU = 'baru';

    public const STATUS_DIPROSES = 'diproses';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const STATUS_VALID = [
        self::STATUS_BARU,
        self::STATUS_DIPROSES,
        self::STATUS_SELESAI,
        self::STATUS_DIBATALKAN,
    ];

    protected $fillable = [
        'kode_pesanan',
        'id_pengguna',
        'nomor_meja',
        'nama_pemesan',
        'catatan',
        'total',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
        ];
    }

    /**
     * @return HasMany<PesananDetail, $this>
     */
    public function details(): HasMany
    {
        return $this->hasMany(PesananDetail::class, 'id_pesanan');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }

    public static function buatKodePesanan(): string
    {
        do {
            $kode = 'PSN-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        } while (self::query()->where('kode_pesanan', $kode)->exists());

        return $kode;
    }

    public function labelStatus(): string
    {
        return match ($this->status) {
            self::STATUS_BARU => 'Baru',
            self::STATUS_DIPROSES => 'Diproses',
            self::STATUS_SELESAI => 'Selesai',
            self::STATUS_DIBATALKAN => 'Dibatalkan',
            default => ucfirst((string) $this->status),
        };
    }
}
