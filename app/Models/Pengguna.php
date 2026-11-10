<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Pengguna extends Model
{
    protected $fillable = [
        'kode_user',
        'nama_depan',
        'nama_balakang',
        'email',
        'nowa',
        'password',
        'google_id',
        'avatar',
        'status',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
        ];
    }

    protected $hidden = [
        'password',
    ];

    /**
     * Buat kode pengguna unik dengan format PG-XXXXXX.
     */
    public static function buatKodeUserUnik(): string
    {
        do {
            $kodeUser = 'PG-'.strtoupper(Str::random(6));
        } while (self::query()->where('kode_user', $kodeUser)->exists());

        return $kodeUser;
    }
}
