<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class);
    }

    public function mejas(): HasMany
    {
        return $this->hasMany(Meja::class);
    }

    public function kategorimenus(): HasMany
    {
        return $this->hasMany(Kategorimenu::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    /**
     * @return HasMany<hargajammain, $this>
     */
    public function hargaJamMains(): HasMany
    {
        return $this->hasMany(hargajammain::class);
    }
}
