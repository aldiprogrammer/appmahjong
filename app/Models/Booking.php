<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'kode_booking',
        'id_pengguna',
        'id_meja',
        'jam_mulai',
        'jam_selesai',
        'tgl_main',
        'tgl_booking',
        'sistem_pembayaran',
        'harga',
        'total_harga',
        'bukti_pembayaran',
        'status_booking',
        'status_hapus',
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }
}
