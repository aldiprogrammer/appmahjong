<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Menu extends Model
{
    protected $appends = [
        'gambar_url',
    ];

    protected $fillable = [
        'id_kategori',
        'vanue_id',
        'nama',
        'gambar',
        'dibuat_di',
        'harga',
        'harga_dingin',
        'harga_panas',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'string',
        ];
    }

    /**
     * @return BelongsTo<Kategorimenu, $this>
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategorimenu::class, 'id_kategori');
    }

    /**
     * @return BelongsTo<Vanue, $this>
     */
    public function vanue(): BelongsTo
    {
        return $this->belongsTo(Vanue::class);
    }

    protected function gambarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->gambar ? asset('storage/'.$this->gambar) : null);
    }
}
