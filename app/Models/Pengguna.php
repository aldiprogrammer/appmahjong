<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengguna extends Model
{
    protected $fillable = [
        'kode_user',
        'nama_depan',
        'nama_balakang',
        'email',
        'nowa',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
    ];
}
