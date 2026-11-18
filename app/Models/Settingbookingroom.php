<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settingbookingroom extends Model
{
    protected $table = 'settingbookingrooms';

    protected $fillable = [
        'vanue_id',
        'pembayaran',
        'jumlah_user',
        'minimal_user',
        'maksimal_user',
        'status',
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
