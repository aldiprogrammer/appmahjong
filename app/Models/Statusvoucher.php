<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statusvoucher extends Model
{
    protected $table = 'statusvouchers';

    protected $fillable = [
        'vanue_id',
        'status_voucher',
        'jumah_booking',
        'jumah_jam',
        'tanggal_mulai',
        'tanggal_berakhir',
    ];

    protected function casts(): array
    {
        return [
            'vanue_id' => 'integer',
        ];
    }

    public function vanue()
    {
        return $this->belongsTo(Vanue::class);
    }
}
