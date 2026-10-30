<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class jam_main extends Model
{
    protected $fillable = [
        'jam_mulai',
        'jam_selesai',
        'status',
    ];
}
