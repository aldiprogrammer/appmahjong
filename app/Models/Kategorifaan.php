<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategorifaan extends Model
{
    protected $fillable = [
        'nama',
    ];

    public function faanHands(): HasMany
    {
        return $this->hasMany(Faanhand::class, 'id_kategorifaan');
    }
}
