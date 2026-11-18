<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Printer extends Model
{
    protected $table = 'printers';

    protected $fillable = [
        'vanue_id',
        'nama',
        'device_id',
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
