<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProfilNowaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengguna_tanpa_nowa_bisa_menyimpan_nomor_whatsapp(): void
    {
        $pengguna = $this->buatPengguna(['nowa' => null]);
        $kode = $this->kirimOtp($pengguna, '081234567890');

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/profil/whatsapp', [
                'nowa' => '081234567890',
                'otp' => $kode,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('081234567890', $pengguna->fresh()->nowa);
    }

    public function test_nomor_whatsapp_wajib_diisi(): void
    {
        $pengguna = $this->buatPengguna(['nowa' => null]);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/profil/whatsapp', ['nowa' => '']);

        $response->assertSessionHasErrors('nowa');
        $this->assertNull($pengguna->fresh()->nowa);
    }

    public function test_nomor_whatsapp_tidak_boleh_sama_dengan_pengguna_lain(): void
    {
        $pengguna = $this->buatPengguna(['nowa' => null]);

        $this->buatPengguna([
            'kode_user' => 'PG-LAIN01',
            'email' => 'lain@example.com',
            'nowa' => '081234567890',
        ]);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/profil/whatsapp', ['nowa' => '081234567890']);

        $response->assertSessionHasErrors([
            'nowa' => 'Nomor WhatsApp sudah terdaftar. Silakan masuk atau gunakan nomor lain.',
        ]);
        $this->assertNull($pengguna->fresh()->nowa);
    }

    public function test_nomor_whatsapp_tidak_boleh_sama_dengan_pengguna_lain_format_62(): void
    {
        $pengguna = $this->buatPengguna(['nowa' => null]);

        $this->buatPengguna([
            'kode_user' => 'PG-LAIN02',
            'email' => 'lain@example.com',
            'nowa' => '081234567890',
        ]);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/profil/whatsapp', ['nowa' => '6281234567890']);

        $response->assertSessionHasErrors([
            'nowa' => 'Nomor WhatsApp sudah terdaftar. Silakan masuk atau gunakan nomor lain.',
        ]);
        $this->assertNull($pengguna->fresh()->nowa);
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

    private function kirimOtp(Pengguna $pengguna, string $nowa): string
    {
        $this->fakeFonnte();

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

            return $request['target'] === $nowa;
        });

        $this->assertNotNull($kode, 'Kode OTP tidak ditemukan pada pesan Fonnte.');

        return $kode;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function buatPengguna(array $overrides = []): Pengguna
    {
        return Pengguna::create([
            'kode_user' => 'PG-TEST01',
            'nama_depan' => 'Budi',
            'nama_balakang' => 'Santoso',
            'email' => 'budi@example.com',
            'nowa' => '08123456789',
            'password' => bcrypt('password'),
            'status' => 'aktif',
            ...$overrides,
        ]);
    }
}
