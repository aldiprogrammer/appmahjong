<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mahjongfaanrules extends Model
{
    protected $fillable = [
        'id_aturanpoint',
        'kode',
        'nama',
        'faan_value',
    ];

    public function aturanPoint(): BelongsTo
    {
        return $this->belongsTo(Aturanpointvenue::class, 'id_aturanpoint');
    }
}
