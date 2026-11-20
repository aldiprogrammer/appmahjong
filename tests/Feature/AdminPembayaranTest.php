<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Vanue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminPembayaranTest extends TestCase
{
    use RefreshDatabase;

    private function buatVanue(): Vanue
    {
        return Vanue::create([
            'nama' => 'Mahjong Center Jakarta',
            'alamat' => 'Jl. Contoh No. 1',
            'logo' => 'vanues/jakarta.png',
        ]);
    }

    private function buatAdmin(?array $hakAkses, string $role = 'admin', ?Vanue $vanue = null): Admin
    {
        return Admin::create([
            'username' => 'admin_utama',
            'role' => $role,
            'vanue_id' => $vanue?->id,
            'hak_akses' => $hakAkses,
            'password' => 'password123',
        ]);
    }

    private function masukSebagai(Admin $admin): static
    {
        return $this->withSession(['admin_id' => $admin->id]);
    }

    public function test_admin_tanpa_pembatasan_bisa_membuka_menu_pembayaran(): void
    {
        $admin = $this->buatAdmin(null);

        $this->masukSebagai($admin)->get('/admin/pembayarans')->assertOk();
    }

    public function test_admin_dengan_hak_akses_terbatas_tidak_bisa_membuka_menu_pembayaran(): void
    {
        $admin = $this->buatAdmin(['dashboard']);

        $this->masukSebagai($admin)
            ->get('/admin/pembayarans')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_metode_qris_wajib_memiliki_gambar_qris(): void
    {
        $vanue = $this->buatVanue();
        $admin = $this->buatAdmin(null, 'super_admin');

        $this->masukSebagai($admin)
            ->post('/admin/pembayarans', [
                'id_venue' => $vanue->id,
                'metode_pembayaran' => 'qris',
                'atas_nama' => 'Mahjong Medan',
            ])
            ->assertSessionHasErrors('qris_image');

        $this->assertDatabaseCount('pembayarans', 0);
    }

    public function test_metode_qris_wajib_memiliki_atas_nama(): void
    {
        $vanue = $this->buatVanue();
        $admin = $this->buatAdmin(null, 'super_admin');

        $this->masukSebagai($admin)
            ->post('/admin/pembayarans', [
                'id_venue' => $vanue->id,
                'metode_pembayaran' => 'qris',
                'qris_image' => UploadedFile::fake()->image('qris.jpg'),
            ])
            ->assertSessionHasErrors('atas_nama');

        $this->assertDatabaseCount('pembayarans', 0);
    }

    public function test_metode_transfer_wajib_memiliki_data_rekening(): void
    {
        $vanue = $this->buatVanue();
        $admin = $this->buatAdmin(null, 'super_admin');

        $this->masukSebagai($admin)
            ->post('/admin/pembayarans', [
                'id_venue' => $vanue->id,
                'metode_pembayaran' => 'transfer',
            ])
            ->assertSessionHasErrors(['bank', 'atas_nama', 'norek']);

        $this->assertDatabaseCount('pembayarans', 0);
    }

    public function test_metode_qris_dapat_disimpan_dengan_gambar_qris(): void
    {
        $vanue = $this->buatVanue();
        $admin = $this->buatAdmin(null, 'super_admin');

        $this->masukSebagai($admin)
            ->post('/admin/pembayarans', [
                'id_venue' => $vanue->id,
                'metode_pembayaran' => 'qris',
                'atas_nama' => 'Mahjong Medan',
                'qris_image' => UploadedFile::fake()->image('qris.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pembayarans', [
            'id_venue' => (string) $vanue->id,
            'metode_pembayaran' => 'qris',
            'atas_nama' => 'Mahjong Medan',
        ]);
    }
}
