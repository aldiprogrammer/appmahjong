<?php

namespace App\Services;

use App\Models\NotifikasiBooking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotifikasiBookingService
{
    /**
     * Simpan notifikasi booking masuk ke panel admin dan kirim push
     * melalui OneSignal (bila kredensial OneSignal sudah dikonfigurasi).
     */
    public function catatBookingMasuk(
        ?int $vanueId,
        string $judul,
        string $pesan,
        ?string $kodeBooking = null,
        string $tipe = NotifikasiBooking::TIPE_BOOKING,
    ): ?NotifikasiBooking {
        if ($vanueId === null) {
            return null;
        }

        $notifikasi = NotifikasiBooking::create([
            'vanue_id' => $vanueId,
            'tipe' => $tipe,
            'judul' => $judul,
            'pesan' => $pesan,
            'kode_booking' => $kodeBooking,
            'dibaca' => false,
        ]);

        $this->kirimPush($vanueId, $judul, $pesan, $kodeBooking);

        return $notifikasi;
    }

    /**
     * Kirim push OneSignal ke admin venue terkait dan seluruh super admin.
     *
     * Admin panel mendaftarkan tag `vanue_id` (id venue atau "all") dan
     * `admin_role` pada perangkatnya, sehingga notifikasi hanya diterima
     * admin yang berhak atas venue tersebut.
     */
    public function kirimPush(int $vanueId, string $judul, string $pesan, ?string $kodeBooking = null): void
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
                    'headings' => ['en' => $judul],
                    'contents' => ['en' => $pesan],
                    'filters' => [
                        ['field' => 'tag', 'key' => 'vanue_id', 'relation' => '=', 'value' => (string) $vanueId],
                        ['operator' => 'OR'],
                        ['field' => 'tag', 'key' => 'admin_role', 'relation' => '=', 'value' => 'super_admin'],
                    ],
                    'data' => [
                        'tipe' => 'booking',
                        'kode_booking' => $kodeBooking,
                        'url' => route('admin.bookings.index'),
                    ],
                    'url' => route('admin.bookings.index'),
                ]);

            $body = $response->json();
            $errors = is_array($body) ? ($body['errors'] ?? []) : [];

            // OneSignal membalas 200 walau tidak ada perangkat yang cocok,
            // dengan field errors terisi dan id kosong. Catat agar mudah dilacak.
            if ($response->failed() || filled($errors) || blank($body['id'] ?? null)) {
                Log::warning('Gagal mengirim push OneSignal.', [
                    'status' => $response->status(),
                    'vanue_id' => $vanueId,
                    'errors' => $errors,
                    'body' => $body,
                ]);

                return;
            }

            Log::info('Push OneSignal terkirim.', [
                'id' => $body['id'] ?? null,
                'vanue_id' => $vanueId,
                'recipients' => $body['recipients'] ?? null,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Gagal menghubungi OneSignal: '.$exception->getMessage());
        }
    }
}
