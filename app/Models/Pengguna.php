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
     * Samakan semua format nomor WhatsApp ke format internasional 62...
     */
    public static function normalisasiNomor(string $nowa): string
    {
        $nomor = preg_replace('/\D+/', '', $nowa) ?? '';

        if (str_starts_with($nomor, '0')) {
            return '62'.substr($nomor, 1);
        }

        if (str_starts_with($nomor, '8')) {
            return '62'.$nomor;
        }

        return $nomor;
    }

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
