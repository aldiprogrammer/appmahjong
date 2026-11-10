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
            'confirmed_at' => 'datetime',
        ];
    }

    public static function imageUrl(?string $path): ?string
    {
        return $path ? asset('storage/'.$path) : null;
    }
}
