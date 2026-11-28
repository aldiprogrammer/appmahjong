<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Faanhand;
use App\Models\Kategorifaan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminKategoriFaanTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withSession(['admin_id' => 1]);
    }

    private function buatKategori(array $data = []): Kategorifaan
    {
        return Kategorifaan::create([
            'nama' => 'Hand Khusus',
            ...$data,
        ]);
    }

    public function test_tamu_diarahkan_ke_halaman_login_admin(): void
    {
        $this->get('/admin/kategori-faans')->assertRedirect(route('admin.login'));
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
            ->get('/admin/kategori-faans')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error', 'Anda tidak memiliki hak akses untuk membuka menu "Kategori Faan".');
    }

    public function test_admin_bisa_melihat_daftar_dari_tabel(): void
    {
        $kategori = $this->buatKategori(['nama' => 'Hand Khusus']);
        Faanhand::create([
            'id_aturanmahjong' => '1',
            'id_kategorifaan' => (string) $kategori->id,
            'nama_faan' => '清一色',
            'inggris' => 'Full Flush',
            'faan_asli' => '24',
            'faan' => '8',
        ]);

        $this->admin()
            ->get('/admin/kategori-faans')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/KategoriFaan/Index')
                ->has('kategoriFaans.data', 1)
                ->where('kategoriFaans.data.0.nama', 'Hand Khusus')
                ->where('kategoriFaans.data.0.faan_hands_count', 1)
            );
    }

    public function test_admin_bisa_menambahkan_kategori(): void
    {
        $this->admin()
            ->post('/admin/kategori-faans', ['nama' => 'Suit Khusus'])
            ->assertRedirect(route('admin.kategori-faans.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('kategorifaans', ['nama' => 'Suit Khusus']);
    }

    public function test_nama_wajib_diisi(): void
    {
        $this->admin()
            ->post('/admin/kategori-faans', [])
            ->assertSessionHasErrors(['nama' => 'Nama kategori wajib diisi.']);

        $this->assertSame(0, Kategorifaan::query()->count());
    }

    public function test_nama_kategori_tidak_boleh_sama(): void
    {
        $this->buatKategori(['nama' => 'Hand Khusus']);

        $this->admin()
            ->post('/admin/kategori-faans', ['nama' => 'Hand Khusus'])
            ->assertSessionHasErrors(['nama' => 'Nama kategori sudah digunakan.']);

        $this->assertSame(1, Kategorifaan::query()->count());
    }

    public function test_nama_kategori_maksimal_50_karakter(): void
    {
        $this->admin()
            ->post('/admin/kategori-faans', ['nama' => str_repeat('a', 51)])
            ->assertSessionHasErrors(['nama' => 'Nama kategori maksimal 50 karakter.']);

        $this->assertSame(0, Kategorifaan::query()->count());
    }

    public function test_admin_bisa_memperbarui_kategori(): void
    {
        $kategori = $this->buatKategori();

        $this->admin()
            ->put("/admin/kategori-faans/{$kategori->id}", ['nama' => 'Chow Khusus'])
            ->assertRedirect(route('admin.kategori-faans.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('kategorifaans', [
            'id' => $kategori->id,
            'nama' => 'Chow Khusus',
        ]);
    }

    public function test_admin_bisa_menghapus_kategori_yang_tidak_dipakai(): void
    {
        $kategori = $this->buatKategori();

        $this->admin()
            ->delete("/admin/kategori-faans/{$kategori->id}")
            ->assertRedirect(route('admin.kategori-faans.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('kategorifaans', ['id' => $kategori->id]);
    }

    public function test_kategori_yang_masih_dipakai_faan_hand_tidak_bisa_dihapus(): void
    {
        $kategori = $this->buatKategori();
        Faanhand::create([
            'id_aturanmahjong' => '1',
            'id_kategorifaan' => (string) $kategori->id,
            'nama_faan' => '清一色',
            'inggris' => 'Full Flush',
            'faan_asli' => '24',
            'faan' => '8',
        ]);

        $this->admin()
            ->delete("/admin/kategori-faans/{$kategori->id}")
            ->assertSessionHas('error', 'Kategori tidak dapat dihapus karena masih memiliki faan hand.');

        $this->assertDatabaseHas('kategorifaans', ['id' => $kategori->id]);
    }

    public function test_pencarian_berdasarkan_nama_kategori(): void
    {
        $this->buatKategori(['nama' => 'Hand Khusus']);
        $this->buatKategori(['nama' => 'Suit Khusus']);

        $this->admin()
            ->get('/admin/kategori-faans?search=Suit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('kategoriFaans.data', 1)
                ->where('kategoriFaans.data.0.nama', 'Suit Khusus')
            );
    }
}
