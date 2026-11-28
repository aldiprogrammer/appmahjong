<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faanhand extends Model
{
    protected $fillable = [
        'id_aturanmahjong',
        'id_kategorifaan',
        'nama_faan',
        'inggris',
        'faan_asli',
        'faan',
    ];

    public function aturanMahjong(): BelongsTo
    {
        return $this->belongsTo(Aturanmahjong::class, 'id_aturanmahjong');
    }

    public function kategoriFaan(): BelongsTo
    {
        return $this->belongsTo(Kategorifaan::class, 'id_kategorifaan');
    }
}
