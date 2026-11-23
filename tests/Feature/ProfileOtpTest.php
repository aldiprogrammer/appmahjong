<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProfileOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_kirim_otp_profil_menyimpan_kode_dan_mengirim_pesan_ke_fonnte(): void
    {
        $this->fakeFonnte();
        $pengguna = $this->buatPengguna();

        $kode = $this->kirimOtp($pengguna);

        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $kode);
        $this->assertTrue(Cache::has($this->kunciOtp('08123456789')));
        $this->assertTrue(Cache::has($this->kunciCooldown('08123456789')));
        Http::assertSentCount(1);
    }

    public function test_profil_menyimpan_nomor_whatsapp_dengan_otp_yang_benar(): void
    {
        $this->fakeFonnte();
        $pengguna = $this->buatPengguna();
        $kode = $this->kirimOtp($pengguna);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/profil/whatsapp', [
                'nowa' => '08123456789',
                'otp' => $kode,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Nomor WhatsApp berhasil disimpan.');
        $this->assertSame('08123456789', $pengguna->refresh()->nowa);
        $this->assertFalse(Cache::has($this->kunciOtp('08123456789')));
    }

    public function test_profil_menolak_otp_salah_tanpa_menyimpan_nomor(): void
    {
        $this->fakeFonnte();
        $pengguna = $this->buatPengguna();
        $kode = $this->kirimOtp($pengguna);
        $kodeSalah = $kode === '000000' ? '111111' : '000000';

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/profil/whatsapp', [
                'nowa' => '08123456789',
                'otp' => $kodeSalah,
            ]);

        $response->assertSessionHasErrors([
            'otp' => 'Kode OTP salah atau sudah kedaluwarsa. Minta kode baru jika perlu.',
        ]);
        $this->assertNull($pengguna->refresh()->nowa);
    }

    public function test_profil_menolak_penyimpanan_tanpa_kode_otp(): void
    {
        $pengguna = $this->buatPengguna();

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/profil/whatsapp', ['nowa' => '08123456789']);

        $response->assertSessionHasErrors(['otp' => 'Kode OTP wajib diisi.']);
        $this->assertNull($pengguna->refresh()->nowa);
    }

    public function test_kirim_otp_profil_menolak_nomor_milik_pengguna_lain(): void
    {
        $this->fakeFonnte();
        $pengguna = $this->buatPengguna();
        $this->buatPengguna([
            'kode_user' => 'PG-LAIN01',
            'email' => 'lain@example.com',
            'nowa' => '08129876543',
        ]);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/profil/whatsapp/otp', ['nowa' => '08129876543']);

        $response->assertSessionHasErrors([
            'nowa' => 'Nomor WhatsApp sudah terdaftar. Silakan masuk atau gunakan nomor lain.',
        ]);
        Http::assertNothingSent();
    }

    public function test_kirim_otp_profil_menolak_format_nomor_invalid(): void
    {
        $this->fakeFonnte();
        $pengguna = $this->buatPengguna();

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/profil/whatsapp/otp', ['nowa' => 'abc']);

        $response->assertSessionHasErrors([
            'nowa' => 'Format nomor WhatsApp tidak valid. Contoh: 08xxxxxxxxxx.',
        ]);
        Http::assertNothingSent();
    }

    public function test_kirim_otp_profil_dibatasi_saat_masih_dalam_cooldown(): void
    {
        $this->fakeFonnte();
        $pengguna = $this->buatPengguna();
        $this->travelTo('2026-01-01 08:00:00');

        $this->kirimOtp($pengguna);
        $this->travel(30)->seconds();

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/profil/whatsapp/otp', ['nowa' => '08123456789']);

        $response->assertSessionHasErrors([
            'nowa' => 'Tunggu 60 detik sebelum meminta kode OTP lagi.',
        ]);
        Http::assertSentCount(1);
    }

    /**
     * @param  array<string, mixed>  $tambahan
     */
    private function buatPengguna(array $tambahan = []): Pengguna
    {
        return Pengguna::create(array_merge([
            'kode_user' => 'PG-TEST01',
            'nama_depan' => 'Budi',
            'nama_balakang' => 'Santoso',
            'email' => 'budi@example.com',
            'nowa' => null,
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ], $tambahan));
    }

    private function fakeFonnte(): void
    {
        config()->set('services.fonnte.token', 'token-rahasia');
        Http::preventStrayRequests();
        Http::fake([
            'api.fonnte.com/send' => Http::response([
                'status' => true,
                'detail' => 'success! message in queue',
            ]),
        ]);
    }

    private function kirimOtp(Pengguna $pengguna, string $nowa = '08123456789'): string
    {
        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/profil/whatsapp/otp', ['nowa' => $nowa])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $kode = null;

        Http::assertSent(function (Request $request) use (&$kode, $nowa): bool {
            if (! preg_match('/Kode OTP Anda: ([0-9]{6})/', (string) $request['message'], $cocok)) {
                return false;
            }

            $kode = $cocok[1];

            return $request->url() === 'https://api.fonnte.com/send'
                && $request['target'] === $nowa;
        });

        $this->assertNotNull($kode, 'Kode OTP tidak ditemukan pada pesan Fonnte.');

        return $kode;
    }

    private function kunciOtp(string $nowa): string
    {
        return 'otp-register:'.hash('sha256', '62'.substr($nowa, 1));
    }

    private function kunciCooldown(string $nowa): string
    {
        return 'otp-register-cooldown:'.hash('sha256', '62'.substr($nowa, 1));
    }
}
