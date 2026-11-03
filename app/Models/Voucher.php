<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $table = 'vouchers';

    protected $fillable = [
        'kode_user',
        'kode_voucher',
        'tanggal_diterima',
        'tanggal_digunakan',
        'id_statusvoucher',
        'status',
    ];
}
