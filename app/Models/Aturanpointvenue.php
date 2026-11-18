<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aturanpointvenue extends Model
{
    protected $fillable = [
        'nama_aturan',
        'slug',
        'deskripsi',
        'min_faan',
        'max_faan',
        'status',
    ];
}
