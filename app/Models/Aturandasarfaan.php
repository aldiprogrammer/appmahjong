<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aturandasarfaan extends Model
{
    protected $fillable = [
        'id_aturanmahjong',
        'minimal_faan',
        'batas_faan',
    ];

    public function aturanMahjong(): BelongsTo
    {
        return $this->belongsTo(Aturanmahjong::class, 'id_aturanmahjong');
    }
}
