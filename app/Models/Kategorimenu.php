<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategorimenu extends Model
{
    protected $fillable = [
        'kategori',
        'vanue_id',
    ];

    /**
     * @return BelongsTo<Vanue, $this>
     */
    public function vanue(): BelongsTo
    {
        return $this->belongsTo(Vanue::class);
    }

    /**
     * @return HasMany<Menu, $this>
     */
    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class, 'id_kategori');
    }
}
