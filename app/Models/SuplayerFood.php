<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuplayerFood extends Model
{
    protected $table = 'suplayer_food';

    protected $fillable = [
        'kode_suplayer',
        'nama_suplayer',
        'alamat',
        'nowa',
        'email',
        'password',
        'tangggl_daftar',
    ];
}
