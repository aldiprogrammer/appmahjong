<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settingbookingroom extends Model
{
    protected $table = 'settingbookingrooms';

    protected $fillable = [
        'pembayaran',
        'jumlah_user',
        'minimal_user',
        'maksimal_user',
        'status',
    ];
}
