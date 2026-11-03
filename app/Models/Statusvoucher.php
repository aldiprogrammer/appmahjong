<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statusvoucher extends Model
{
    protected $table = 'statusvouchers';

    protected $fillable = [
        'status_voucher',
        'jumah_booking',
        'jumah_jam',
        'tanggal_mulai',
        'tanggal_berakhir',
    ];
}
