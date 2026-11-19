<?php

namespace App\Services;

use App\Models\NotifikasiPengguna;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotifikasiPenggunaService
{
    /**
     * Simpan notifikasi untuk pengguna lalu kirim push OneSignal
     * (bila kredensial OneSignal sudah dikonfigurasi).
     */
    public function catat(
        int|string $penggunaId,
        string $judul,
        string $pesan,
        ?string $kodeBooking = null,
        string $tipe = NotifikasiPengguna::TIPE_BOOKING,
    ): NotifikasiPengguna {
        $notifikasi = NotifikasiPengguna::create([
            'id_pengguna' => (string) $penggunaId,
            'tipe' => $tipe,
            'judul' => $judul,
            'pesan' => $pesan,
            'kode_booking' => $kodeBooking,
            'dibaca' => false,
        ]);

        $this->kirimPush((string) $penggunaId, $judul, $pesan, $kodeBooking);

        return $notifikasi;
    }

    /**
     * Kirim push OneSignal ke akun pengguna terkait lewat external_id
     * `pengguna-{id}` yang didaftarkan SDK pada halaman user.
     */
    public function kirimPush(string $penggunaId, string $judul, string $pesan, ?string $kodeBooking = null): void
    {
        $appId = config('services.onesignal.app_id');
        $restApiKey = config('services.onesignal.rest_api_key');

        if (blank($appId) || blank($restApiKey)) {
            return;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Key '.$restApiKey,
                    'Accept' => 'application/json',
                ])
                ->post('https://api.onesignal.com/notifications', [
                    'app_id' => $appId,
                    'target_channel' => 'push',
                    'include_aliases' => [
                        'external_id' => ['pengguna-'.$penggunaId],
                    ],
                    'headings' => ['en' => $judul],
                    'contents' => ['en' => $pesan],
                    'data' => [
                        'tipe' => 'booking',
                        'kode_booking' => $kodeBooking,
                        'url' => route('riwayat'),
                    ],
                    'url' => route('riwayat'),
                ]);

            $body = $response->json();
            $errors = is_array($body) ? ($body['errors'] ?? []) : [];

            // OneSignal membalas 200 walau tidak ada perangkat yang cocok,
            // dengan field errors terisi dan id kosong. Catat agar mudah dilacak.
            if ($response->failed() || filled($errors) || blank($body['id'] ?? null)) {
                Log::warning('Gagal mengirim push OneSignal ke pengguna.', [
                    'status' => $response->status(),
                    'pengguna_id' => $penggunaId,
                    'errors' => $errors,
                    'body' => $body,
                ]);

                return;
            }

            Log::info('Push OneSignal terkirim ke pengguna.', [
                'id' => $body['id'] ?? null,
                'pengguna_id' => $penggunaId,
                'recipients' => $body['recipients'] ?? null,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Gagal menghubungi OneSignal: '.$exception->getMessage());
        }
    }
}
