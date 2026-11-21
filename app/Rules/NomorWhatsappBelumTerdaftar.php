<?php

namespace App\Rules;

use App\Models\Pengguna;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NomorWhatsappBelumTerdaftar implements ValidationRule
{
    public function __construct(private readonly ?int $penggunaId = null) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $nomor = Pengguna::normalisasiNomor((string) $value);
        $formatLokal = str_starts_with($nomor, '62') ? '0'.substr($nomor, 2) : $nomor;

        $sudahTerdaftar = Pengguna::query()
            ->whereIn('nowa', array_values(array_unique([$nomor, $formatLokal])))
            ->when($this->penggunaId !== null, fn ($query) => $query->whereKeyNot($this->penggunaId))
            ->exists();

        if ($sudahTerdaftar) {
            $fail('Nomor WhatsApp sudah terdaftar. Silakan masuk atau gunakan nomor lain.');
        }
    }
}
