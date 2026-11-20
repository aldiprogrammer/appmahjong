<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuplayerFood extends Model
{
    protected $table = 'suplayer_food';

    protected $fillable = [
        'vanue_id',
        'kode_suplayer',
        'nama_suplayer',
        'alamat',
        'nowa',
        'email',
        'password',
        'tangggl_daftar',
    ];

    /**
     * @return BelongsTo<Vanue, $this>
     */
    public function vanue(): BelongsTo
    {
        return $this->belongsTo(Vanue::class);
    }
}
