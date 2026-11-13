<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meja extends Model
{
    protected $fillable = [
        'kode_meja',
        'nama_meja',
        'vanue_id',
    ];

    public function vanue(): BelongsTo
    {
        return $this->belongsTo(Vanue::class);
    }
}
