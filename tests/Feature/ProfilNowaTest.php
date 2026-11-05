<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilNowaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengguna_tanpa_nowa_bisa_menyimpan_nomor_whatsapp(): void
    {
        $pengguna = $this->buatPengguna(['nowa' => null]);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/profil/whatsapp', ['nowa' => '081234567890']);

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

        $response->assertSessionHasErrors('nowa');
        $this->assertNull($pengguna->fresh()->nowa);
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
