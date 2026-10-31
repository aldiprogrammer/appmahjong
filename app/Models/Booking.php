<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $appends = [
        'bukti_pembayaran_url',
    ];

    protected $fillable = [
        'kode_booking',
        'kode_pembayaran',
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
        'status_pembayaran',
        'dibayar_pada',
        'status_hapus',
    ];

    protected function casts(): array
    {
        return [
            'dibayar_pada' => 'datetime',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }

    protected function buktiPembayaranUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->bukti_pembayaran
                ? asset('storage/'.$this->bukti_pembayaran)
                : null,
        );
    }
}
