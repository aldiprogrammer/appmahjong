<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Vanue extends Model
{
    protected $appends = [
        'logo_url',
    ];

    protected $fillable = [
        'nama',
        'alamat',
        'logo',
    ];

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->logo ? asset('storage/'.$this->logo) : null);
    }
}
