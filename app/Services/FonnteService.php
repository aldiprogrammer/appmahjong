<?php

namespace App\Services;

use App\Models\Verifikasiwhatsapp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FonnteService
{
    /**
     * Kirim pesan WhatsApp melalui Fonnte.
     *
     * Mengembalikan false bila token belum diisi, gateway menolak, atau
     * koneksi gagal, supaya caller bisa memberi pesan yang ramah pengguna.
     */
    public function kirimPesan(string $target, string $pesan): bool
    {
        // Token aktif dikelola admin lewat menu Pengaturan > Verifikasi WhatsApp.
        // Nilai di .env tetap dipakai bila belum ada token aktif di database.
        $token = Verifikasiwhatsapp::tokenAktif() ?: config('services.fonnte.token');

        if (blank($token)) {
            Log::warning('Fonnte token belum dikonfigurasi; pesan tidak dikirim.');

            return false;
        }

        try {
            $response = Http::baseUrl(config('services.fonnte.base_url'))
                ->connectTimeout(3)
                ->timeout((int) config('services.fonnte.timeout', 10))
                ->withHeaders([
                    'Authorization' => $token,
                    'Accept' => 'application/json',
                ])
                ->asForm()
                ->post('/send', [
                    'target' => $target,
                    'message' => $pesan,
                ]);
        } catch (Throwable $exception) {
            Log::warning('Gagal menghubungi Fonnte: '.$exception->getMessage());

            return false;
        }

        $body = $response->json();
        $berhasil = $response->successful()
            && filter_var($body['status'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! $berhasil) {
            Log::warning('Fonnte menolak pengiriman pesan.', [
                'status_http' => $response->status(),
                'alasan' => $body['reason'] ?? $body['detail'] ?? null,
                'target' => $this->sembunyikanNomor($target),
            ]);
        }

        return $berhasil;
    }

    private function sembunyikanNomor(string $nomor): string
    {
        if (Str::length($nomor) <= 7) {
            return $nomor;
        }

        return Str::substr($nomor, 0, 3).'****'.Str::substr($nomor, -3);
    }
}
