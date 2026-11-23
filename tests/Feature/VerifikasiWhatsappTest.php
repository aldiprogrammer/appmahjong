<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Verifikasiwhatsapp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VerifikasiWhatsappTest extends TestCase
{
    use RefreshDatabase;

    private function buatAdmin(?array $hakAkses = null): Admin
    {
        return Admin::create([
            'username' => 'admin_utama',
            'role' => 'super_admin',
            'hak_akses' => $hakAkses,
            'password' => 'password123',
        ]);
    }

    private function masukSebagai(Admin $admin): static
    {
        return $this->withSession(['admin_id' => $admin->id]);
    }

    public function test_halaman_menampilkan_data_dari_tabel_verifikasiwhatsapps(): void
    {
        Verifikasiwhatsapp::create([
            'nowa' => '08123456789',
            'token' => 'token-pertama',
            'status' => Verifikasiwhatsapp::STATUS_AKTIF,
        ]);
        Verifikasiwhatsapp::create([
            'nowa' => '08198765432',
            'token' => 'token-kedua',
            'status' => Verifikasiwhatsapp::STATUS_NONAKTIF,
        ]);

        $this->masukSebagai($this->buatAdmin())
            ->get('/admin/verifikasi-whatsapps')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/VerifikasiWhatsapp/Index')
                ->has('verifikasiWhatsapps.data', 2)
                ->where('verifikasiWhatsapps.data.0.nowa', '08198765432')
                ->where('verifikasiWhatsapps.data.0.token', 'token-kedua')
                ->where('statuses', ['aktif', 'nonaktif'])
                ->where('filters.search', '')
                ->where('adminMenus.verifikasi-whatsapps', 'Verifikasi WhatsApp')
            );
    }

    public function test_admin_bisa_menambah_nomor_dan_token_verifikasi(): void
    {
        $this->masukSebagai($this->buatAdmin())
            ->post('/admin/verifikasi-whatsapps', [
                'nowa' => '08123456789',
                'token' => 'token-baru',
                'status' => Verifikasiwhatsapp::STATUS_AKTIF,
            ])
            ->assertSessionHas('success', 'Verifikasi WhatsApp berhasil ditambahkan.');

        $this->assertDatabaseHas('verifikasiwhatsapps', [
            'nowa' => '08123456789',
            'token' => 'token-baru',
            'status' => 'aktif',
        ]);
    }

    public function test_admin_bisa_mencari_data_verifikasi(): void
    {
        Verifikasiwhatsapp::create([
            'nowa' => '08123456789',
            'token' => 'token-pertama',
            'status' => Verifikasiwhatsapp::STATUS_AKTIF,
        ]);
        Verifikasiwhatsapp::create([
            'nowa' => '08198765432',
            'token' => 'token-kedua',
            'status' => Verifikasiwhatsapp::STATUS_NONAKTIF,
        ]);

        $this->masukSebagai($this->buatAdmin())
            ->get('/admin/verifikasi-whatsapps?search=token-kedua&status=nonaktif')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('verifikasiWhatsapps.data', 1)
                ->where('verifikasiWhatsapps.data.0.nowa', '08198765432')
                ->where('filters.search', 'token-kedua')
                ->where('filters.status', 'nonaktif')
            );
    }

    public function test_validasi_menolak_nomor_token_dan_status_kosong(): void
    {
        $this->masukSebagai($this->buatAdmin())
            ->post('/admin/verifikasi-whatsapps', [])
            ->assertSessionHasErrors(['nowa', 'token', 'status']);

        $this->assertSame(0, Verifikasiwhatsapp::query()->count());
    }

    public function test_format_nomor_whatsapp_divalidasi(): void
    {
        $this->masukSebagai($this->buatAdmin())
            ->post('/admin/verifikasi-whatsapps', [
                'nowa' => 'abc',
                'token' => 'token-baru',
                'status' => Verifikasiwhatsapp::STATUS_AKTIF,
            ])
            ->assertSessionHasErrors('nowa');
    }

    public function test_token_aktif_baru_menonaktifkan_token_lainnya(): void
    {
        $tokenLama = Verifikasiwhatsapp::create([
            'nowa' => '08111111111',
            'token' => 'token-lama',
            'status' => Verifikasiwhatsapp::STATUS_AKTIF,
        ]);

        $this->masukSebagai($this->buatAdmin())
            ->post('/admin/verifikasi-whatsapps', [
                'nowa' => '08222222222',
                'token' => 'token-baru',
                'status' => Verifikasiwhatsapp::STATUS_AKTIF,
            ]);

        $this->assertSame('nonaktif', $tokenLama->fresh()->status);
        $this->assertSame('token-baru', Verifikasiwhatsapp::tokenAktif());
    }

    public function test_admin_bisa_mengubah_dan_menghapus_data_verifikasi(): void
    {
        $tokenLama = Verifikasiwhatsapp::create([
            'nowa' => '08111111111',
            'token' => 'token-lama',
            'status' => Verifikasiwhatsapp::STATUS_AKTIF,
        ]);
        $tokenBaru = Verifikasiwhatsapp::create([
            'nowa' => '08222222222',
            'token' => 'token-baru',
            'status' => Verifikasiwhatsapp::STATUS_NONAKTIF,
        ]);

        $admin = $this->buatAdmin();

        $this->masukSebagai($admin)
            ->put("/admin/verifikasi-whatsapps/{$tokenBaru->id}", [
                'nowa' => '08333333333',
                'token' => 'token-diubah',
                'status' => Verifikasiwhatsapp::STATUS_AKTIF,
            ])
            ->assertSessionHas('success', 'Verifikasi WhatsApp berhasil diperbarui.');

        $this->assertSame('nonaktif', $tokenLama->fresh()->status);
        $this->assertDatabaseHas('verifikasiwhatsapps', [
            'id' => $tokenBaru->id,
            'nowa' => '08333333333',
            'token' => 'token-diubah',
            'status' => 'aktif',
        ]);

        $this->masukSebagai($admin)
            ->delete("/admin/verifikasi-whatsapps/{$tokenBaru->id}")
            ->assertSessionHas('success', 'Verifikasi WhatsApp berhasil dihapus.');

        $this->assertDatabaseMissing('verifikasiwhatsapps', ['id' => $tokenBaru->id]);
    }

    public function test_admin_tanpa_hak_akses_tidak_bisa_membuka_menu(): void
    {
        $adminTerbatas = $this->buatAdmin(['dashboard', 'mejas']);

        $this->masukSebagai($adminTerbatas)
            ->get('/admin/verifikasi-whatsapps')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error', 'Anda tidak memiliki hak akses untuk membuka menu "Verifikasi WhatsApp".');
    }
}
