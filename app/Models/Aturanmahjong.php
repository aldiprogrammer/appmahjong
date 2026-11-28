<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aturanmahjong extends Model
{
    protected $fillable = [
        'kode',
        'nama_aturan',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function aturanDasarFaans(): HasMany
    {
        return $this->hasMany(Aturandasarfaan::class, 'id_aturanmahjong');
    }

    public function faanHands(): HasMany
    {
        return $this->hasMany(Faanhand::class, 'id_aturanmahjong');
    }
}
