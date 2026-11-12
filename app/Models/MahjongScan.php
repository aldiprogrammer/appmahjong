<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MahjongScan extends Model
{
    protected $fillable = [
        'id_pengguna',
        'image_path',
        'tiles',
        'combinations',
        'unmatched',
        'message',
        'is_winning_hand',
        'points',
        'scoring_mode',
        'faan',
        'faan_details',
        'seat_wind',
        'round_wind',
        'is_self_draw',
        'is_concealed',
        'is_no_flowers',
        'is_last_tile',
        'is_robbing_kong',
        'is_kong_replacement',
        'status',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'tiles' => 'array',
            'combinations' => 'array',
            'unmatched' => 'array',
            'is_winning_hand' => 'boolean',
            'points' => 'integer',
            'faan' => 'integer',
            'faan_details' => 'array',
            'is_self_draw' => 'boolean',
            'is_concealed' => 'boolean',
            'is_no_flowers' => 'boolean',
            'is_last_tile' => 'boolean',
            'is_robbing_kong' => 'boolean',
            'is_kong_replacement' => 'boolean',
            'confirmed_at' => 'datetime',
        ];
    }

    public static function imageUrl(?string $path): ?string
    {
        return $path ? asset('storage/'.$path) : null;
    }
}
