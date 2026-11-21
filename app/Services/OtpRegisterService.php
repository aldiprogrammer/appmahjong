<?php

namespace App\Services;

use App\Models\Pengguna;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class OtpRegisterService
{
    private const MASA_BERLAKU_MENIT = 5;

    private const BATAS_KIRIM_ULANG_DETIK = 60;

    private const MAKS_PERCOBAAN = 5;

    public function __construct(private readonly FonnteService $fonnte) {}

    /**
     * Batasi pengiriman ulang agar satu nomor tidak dipakai untuk spam.
     */
    public function bisaKirim(string $nowa): bool
    {
        return ! Cache::has($this->kunciCooldown($nowa));
    }

    /**
     * Buat, simpan, lalu kirim kode OTP. Kode tidak disimpan apa-adanya.
     */
    public function kirimKode(string $nowa): void
    {
        $kode = (string) random_int(100000, 999999);
        $kadaluarsa = now()->addMinutes(self::MASA_BERLAKU_MENIT);

        Cache::put($this->kunciOtp($nowa), [
            'hash' => $this->hashKode($kode),
            'percobaan' => 0,
            'kadaluarsa' => $kadaluarsa->timestamp,
        ], $kadaluarsa);

        // Paket Free Fonnte menolak pesan panjang, jadi OTP sengaja disingkat.
        $pesan = "Kode OTP Anda: {$kode}";

        if (! $this->fonnte->kirimPesan($nowa, $pesan)) {
            Cache::forget($this->kunciOtp($nowa));

            throw new RuntimeException('Gagal mengirim kode OTP melalui WhatsApp.');
        }

        Cache::put($this->kunciCooldown($nowa), true, now()->addSeconds(self::BATAS_KIRIM_ULANG_DETIK));
    }

    /**
     * Cocokkan kode sekali pakai. Kode yang salah menambah percobaan dan
     * kode akan dibatalkan setelah beberapa kali salah.
     */
    public function verifikasiKode(string $nowa, string $kode): bool
    {
        $kunci = $this->kunciOtp($nowa);
        $data = Cache::get($kunci);

        if (! is_array($data) || ! isset($data['hash']) || ! is_string($data['hash'])) {
            return false;
        }

        if (hash_equals($data['hash'], $this->hashKode($kode))) {
            Cache::forget($kunci);

            return true;
        }

        $data['percobaan'] = ($data['percobaan'] ?? 0) + 1;
        $kadaluarsa = (int) ($data['kadaluarsa'] ?? now()->addMinutes(self::MASA_BERLAKU_MENIT)->timestamp);

        if ($data['percobaan'] >= self::MAKS_PERCOBAAN) {
            Cache::forget($kunci);
        } else {
            // Pertahankan waktu kedaluwarsa semula agar percobaan salah
            // tidak memperpanjang masa berlaku kode.
            Cache::put($kunci, $data, now()->setTimestamp($kadaluarsa));
        }

        return false;
    }

    private function hashKode(string $kode): string
    {
        return hash_hmac('sha256', $kode, (string) config('app.key'));
    }

    private function kunciOtp(string $nowa): string
    {
        return 'otp-register:'.$this->sidikJariNomor($nowa);
    }

    private function kunciCooldown(string $nowa): string
    {
        return 'otp-register-cooldown:'.$this->sidikJariNomor($nowa);
    }

    private function sidikJariNomor(string $nowa): string
    {
        return hash('sha256', Pengguna::normalisasiNomor($nowa));
    }
}
