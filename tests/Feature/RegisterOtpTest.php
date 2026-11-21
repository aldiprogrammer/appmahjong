<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegisterOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_kirim_otp_menyimpan_kode_dan_mengirim_pesan_ke_fonnte(): void
    {
        $this->fakeFonnte();

        $kode = $this->kirimOtp();

        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $kode);
        $this->assertTrue(Cache::has($this->kunciOtp('08123456789')));
        $this->assertTrue(Cache::has($this->kunciCooldown('08123456789')));
        Http::assertSentCount(1);
    }

    public function test_register_membuat_akun_dengan_otp_yang_benar(): void
    {
        $this->fakeFonnte();
        $kode = $this->kirimOtp();

        $response = $this->post('/register', $this->payloadBaru(['otp' => $kode]));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('penggunas', [
            'email' => 'budi@example.com',
            'nowa' => '08123456789',
        ]);
        $this->assertSame(
            Pengguna::query()->where('email', 'budi@example.com')->value('id'),
            session('pengguna_id'),
        );
        $this->assertFalse(Cache::has($this->kunciOtp('08123456789')));
    }

    public function test_register_menolak_otp_salah_tanpa_membuat_akun(): void
    {
        $this->fakeFonnte();
        $kode = $this->kirimOtp();
        $kodeSalah = $kode === '000000' ? '111111' : '000000';

        $response = $this->post('/register', $this->payloadBaru(['otp' => $kodeSalah]));

        $response->assertSessionHasErrors([
            'otp' => 'Kode OTP salah atau sudah kedaluwarsa. Minta kode baru jika perlu.',
        ]);
        $this->assertDatabaseMissing('penggunas', ['email' => 'budi@example.com']);
        $this->assertNull(session('pengguna_id'));
    }

    public function test_register_menolak_pendaftaran_tanpa_kode_otp(): void
    {
        $response = $this->post('/register', $this->payloadBaru());

        $response->assertSessionHasErrors(['otp' => 'Kode OTP wajib diisi.']);
        $this->assertDatabaseMissing('penggunas', ['email' => 'budi@example.com']);
    }

    public function test_kirim_otp_menolak_nomor_yang_sudah_terdaftar(): void
    {
        $this->fakeFonnte();
        Pengguna::create([
            'kode_user' => 'PG-TEST01',
            'nama_depan' => 'Budi',
            'nama_balakang' => 'Santoso',
            'email' => 'lama@example.com',
            'nowa' => '08123456789',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $response = $this->post('/register/otp/kirim', ['nowa' => '08123456789']);

        $response->assertSessionHasErrors([
            'nowa' => 'Nomor WhatsApp sudah terdaftar. Silakan masuk atau gunakan nomor lain.',
        ]);
        $this->assertFalse(Cache::has($this->kunciOtp('08123456789')));
        Http::assertNothingSent();
    }

    public function test_kirim_otp_menolak_nomor_terdaftar_format_plus_62(): void
    {
        $this->fakeFonnte();
        Pengguna::create([
            'kode_user' => 'PG-TEST02',
            'nama_depan' => 'Budi',
            'nama_balakang' => 'Santoso',
            'email' => 'lama@example.com',
            'nowa' => '08123456789',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $response = $this->post('/register/otp/kirim', ['nowa' => '+628123456789']);

        $response->assertSessionHasErrors([
            'nowa' => 'Nomor WhatsApp sudah terdaftar. Silakan masuk atau gunakan nomor lain.',
        ]);
        Http::assertNothingSent();
    }

    public function test_register_menolak_nomor_terdaftar_format_62(): void
    {
        $this->fakeFonnte();
        Pengguna::create([
            'kode_user' => 'PG-TEST03',
            'nama_depan' => 'Budi',
            'nama_balakang' => 'Santoso',
            'email' => 'lama@example.com',
            'nowa' => '08123456789',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $response = $this->post('/register', $this->payloadBaru([
            'nowa' => '628123456789',
            'otp' => '123456',
        ]));

        $response->assertSessionHasErrors([
            'nowa' => 'Nomor WhatsApp sudah terdaftar. Silakan masuk atau gunakan nomor lain.',
        ]);
        $this->assertDatabaseMissing('penggunas', ['email' => 'budi@example.com']);
        Http::assertNothingSent();
    }

    public function test_kirim_otp_menolak_format_nomor_invalid(): void
    {
        $this->fakeFonnte();

        $response = $this->post('/register/otp/kirim', ['nowa' => 'abc']);

        $response->assertSessionHasErrors([
            'nowa' => 'Format nomor WhatsApp tidak valid. Contoh: 08xxxxxxxxxx.',
        ]);
        Http::assertNothingSent();
    }

    public function test_kirim_otp_gagal_saat_gateway_menolak(): void
    {
        config()->set('services.fonnte.token', 'token-rahasia');
        Http::preventStrayRequests();
        Http::fake([
            'api.fonnte.com/send' => Http::response([
                'status' => false,
                'reason' => 'insufficient quota',
            ]),
        ]);

        $response = $this->post('/register/otp/kirim', ['nowa' => '08123456789']);

        $response->assertSessionHasErrors([
            'nowa' => 'Kode OTP gagal dikirim. Periksa nomor WhatsApp lalu coba lagi.',
        ]);
        $this->assertFalse(Cache::has($this->kunciOtp('08123456789')));
        Http::assertSentCount(1);
    }

    public function test_kirim_otp_dibatasi_saat_masih_dalam_cooldown(): void
    {
        $this->fakeFonnte();
        $this->travelTo('2026-01-01 08:00:00');

        $this->kirimOtp();
        $this->travel(30)->seconds();

        $response = $this->post('/register/otp/kirim', ['nowa' => '08123456789']);

        $response->assertSessionHasErrors([
            'nowa' => 'Tunggu 60 detik sebelum meminta kode OTP lagi.',
        ]);
        Http::assertSentCount(1);
    }

    public function test_kode_otp_dibatalkan_setelah_lima_kali_salah(): void
    {
        $this->fakeFonnte();
        $kode = $this->kirimOtp();
        $kodeSalah = $kode === '000000' ? '111111' : '000000';

        for ($percobaan = 0; $percobaan < 5; $percobaan++) {
            $this->post('/register', $this->payloadBaru(['otp' => $kodeSalah]))
                ->assertSessionHasErrors('otp');
        }

        $response = $this->post('/register', $this->payloadBaru(['otp' => $kode]));

        $response->assertSessionHasErrors('otp');
        $this->assertDatabaseMissing('penggunas', ['email' => 'budi@example.com']);
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

    /**
     * @param  array<string, mixed>  $tambahan
     * @return array<string, mixed>
     */
    private function payloadBaru(array $tambahan = []): array
    {
        return array_merge([
            'nama_depan' => 'Budi',
            'nama_balakang' => 'Santoso',
            'email' => 'budi@example.com',
            'nowa' => '08123456789',
            'password' => 'password-rahasia',
            'password_confirmation' => 'password-rahasia',
        ], $tambahan);
    }

    private function kirimOtp(string $nowa = '08123456789'): string
    {
        $this->post('/register/otp/kirim', ['nowa' => $nowa])
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
