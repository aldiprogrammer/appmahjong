<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    protected $fillable = [
        'id_venue',
        'metode_pembayaran',
        'bank',
        'atas_nama',
        'norek',
        'qris_image',
    ];

    protected function casts(): array
    {
        return [
            'qris_image' => 'string',
        ];
    }

    public function vanue(): BelongsTo
    {
        return $this->belongsTo(Vanue::class, 'id_venue');
    }
}
