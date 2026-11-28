<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Aturanmahjong;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAturanMahjongTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withSession(['admin_id' => 1]);
    }

    private function buatAturan(array $data = []): Aturanmahjong
    {
        return Aturanmahjong::create([
            'kode' => 'AM001',
            'nama_aturan' => 'Susunan khusus',
            ...$data,
        ]);
    }

    public function test_tamu_diarahkan_ke_halaman_login_admin(): void
    {
        $this->get('/admin/aturan-mahjongs')->assertRedirect(route('admin.login'));
    }

    public function test_admin_dengan_hak_akses_terbatas_tidak_bisa_membuka_menu(): void
    {
        $admin = Admin::create([
            'username' => 'admin_terbatas',
            'role' => 'admin',
            'hak_akses' => ['dashboard'],
            'password' => 'password123',
        ]);

        $this->withSession(['admin_id' => $admin->id])
            ->get('/admin/aturan-mahjongs')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error', 'Anda tidak memiliki hak akses untuk membuka menu "Aturan Mahjong".');
    }

    public function test_admin_bisa_melihat_daftar_aturan_dari_tabel(): void
    {
        $this->buatAturan();

        $this->admin()
            ->get('/admin/aturan-mahjongs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AturanMahjong/Index')
                ->has('aturanMahjongs.data', 1)
                ->where('aturanMahjongs.data.0.kode', 'AM001')
                ->where('aturanMahjongs.data.0.nama_aturan', 'Susunan khusus')
            );
    }

    public function test_admin_bisa_menambahkan_aturan_dengan_kode_otomatis(): void
    {
        $this->admin()
            ->post('/admin/aturan-mahjongs', [
                'nama_aturan' => 'Aturan baru',
            ])
            ->assertRedirect(route('admin.aturan-mahjongs.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('aturanmahjongs', [
            'kode' => 'AM001',
            'nama_aturan' => 'Aturan baru',
        ]);
    }

    public function test_tamu_tidak_bisa_mengambil_kode_baru(): void
    {
        $this->get('/admin/aturan-mahjongs/kode-baru')->assertRedirect(route('admin.login'));
    }

    public function test_kode_baru_dibuat_berurutan_dan_tidak_duplikat(): void
    {
        $this->admin()
            ->get('/admin/aturan-mahjongs/kode-baru')
            ->assertOk()
            ->assertJson(['kode' => 'AM001']);

        $this->buatAturan();

        $this->admin()
            ->get('/admin/aturan-mahjongs/kode-baru')
            ->assertOk()
            ->assertJson(['kode' => 'AM002']);

        $this->admin()
            ->post('/admin/aturan-mahjongs', [
                'kode' => 'AM001',
                'nama_aturan' => 'Aturan kedua',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('aturanmahjongs', [
            'kode' => 'AM002',
            'nama_aturan' => 'Aturan kedua',
        ]);
    }

    public function test_nama_aturan_wajib_diisi(): void
    {
        $this->admin()
            ->post('/admin/aturan-mahjongs', [
                'nama_aturan' => '',
            ])
            ->assertSessionHasErrors(['nama_aturan' => 'Nama aturan wajib diisi.']);

        $this->assertSame(0, Aturanmahjong::query()->count());
    }

    public function test_update_tidak_mengubah_kode_otomatis(): void
    {
        $aturan = $this->buatAturan();

        $this->admin()
            ->put("/admin/aturan-mahjongs/{$aturan->id}", [
                'kode' => 'AM999',
                'nama_aturan' => 'Susunan khusus diperbarui',
            ])
            ->assertRedirect(route('admin.aturan-mahjongs.index'))
            ->assertSessionHasNoErrors();

        $aturan->refresh();

        $this->assertSame('AM001', $aturan->kode);
        $this->assertSame('Susunan khusus diperbarui', $aturan->nama_aturan);
    }

    public function test_admin_bisa_menghapus_aturan(): void
    {
        $aturan = $this->buatAturan();

        $this->admin()
            ->delete("/admin/aturan-mahjongs/{$aturan->id}")
            ->assertRedirect(route('admin.aturan-mahjongs.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('aturanmahjongs', ['id' => $aturan->id]);
    }

    public function test_pencarian_aturan_berdasarkan_kode_dan_nama(): void
    {
        $this->buatAturan();
        $this->buatAturan([
            'kode' => 'AM009',
            'nama_aturan' => 'Aturan turnamen',
        ]);

        $this->admin()
            ->get('/admin/aturan-mahjongs?search=AM009')
            ->assertInertia(fn (Assert $page) => $page
                ->has('aturanMahjongs.data', 1)
                ->where('aturanMahjongs.data.0.nama_aturan', 'Aturan turnamen')
            );

        $this->admin()
            ->get('/admin/aturan-mahjongs?search=turnamen')
            ->assertInertia(fn (Assert $page) => $page
                ->has('aturanMahjongs.data', 1)
                ->where('aturanMahjongs.data.0.kode', 'AM009')
            );
    }

    public function test_hanya_satu_aturan_yang_bisa_aktif(): void
    {
        $pertama = $this->buatAturan(['is_active' => true]);
        $kedua = $this->buatAturan([
            'kode' => 'AM002',
            'nama_aturan' => 'Aturan turnamen',
        ]);

        $this->admin()
            ->put("/admin/aturan-mahjongs/{$kedua->id}", [
                'nama_aturan' => 'Aturan turnamen aktif',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.aturan-mahjongs.index'))
            ->assertSessionHasNoErrors();

        $this->assertFalse($pertama->refresh()->is_active);
        $this->assertTrue($kedua->refresh()->is_active);
    }

    public function test_admin_bisa_menonaktifkan_aturan_aktif(): void
    {
        $aturan = $this->buatAturan(['is_active' => true]);

        $this->admin()
            ->put("/admin/aturan-mahjongs/{$aturan->id}", [
                'nama_aturan' => 'Susunan khusus',
            ])
            ->assertRedirect(route('admin.aturan-mahjongs.index'))
            ->assertSessionHasNoErrors();

        $this->assertFalse($aturan->refresh()->is_active);
    }

    public function test_aturan_aktif_muncul_lebih_awal_di_daftar(): void
    {
        $nonaktif = $this->buatAturan();
        $aktif = $this->buatAturan([
            'kode' => 'AM002',
            'nama_aturan' => 'Aturan aktif',
            'is_active' => true,
        ]);

        $this->admin()
            ->get('/admin/aturan-mahjongs')
            ->assertInertia(fn (Assert $page) => $page
                ->has('aturanMahjongs.data', 2)
                ->where('aturanMahjongs.data.0.id', $aktif->id)
                ->where('aturanMahjongs.data.1.id', $nonaktif->id)
            );
    }
}
