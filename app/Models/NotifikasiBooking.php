<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikasiBooking extends Model
{
    public const TIPE_BOOKING = 'booking';

    public const TIPE_ROOM = 'room';

    protected $fillable = [
        'vanue_id',
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

    public function vanue(): BelongsTo
    {
        return $this->belongsTo(Vanue::class);
    }
}
