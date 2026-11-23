<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Verifikasiwhatsapp extends Model
{
    public const STATUS_AKTIF = 'aktif';

    public const STATUS_NONAKTIF = 'nonaktif';

    protected $fillable = [
        'nowa',
        'token',
        'status',
    ];

    /**
     * Token aktif yang dipakai pengiriman OTP. Baris terakhir yang aktif
     * menang bila ada lebih dari satu karena data lama.
     */
    public static function tokenAktif(): ?string
    {
        return static::query()
            ->aktif()
            ->latest('id')
            ->value('token');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeAktif(Builder $query): void
    {
        $query->where('status', self::STATUS_AKTIF);
    }
}
