<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class hargajammain extends Model
{
    protected $fillable = [
        'jam_main_id',
        'hari',
        'harga',
        'diskon',
        'harga_akhir',
    ];

    public function jamMain(): BelongsTo
    {
        return $this->belongsTo(jam_main::class, 'jam_main_id');
    }
}
