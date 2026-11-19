<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikasiPengguna extends Model
{
    public const TIPE_BOOKING = 'booking';

    public const TIPE_PEMBAYARAN = 'pembayaran';

    protected $fillable = [
        'id_pengguna',
        'tipe',
        'judul',
        'pesan',
        'kode_booking',
        'dibaca',
    ];

    protected function casts(): array
    {
        return [
            'dibaca' => 'boolean',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }
}
