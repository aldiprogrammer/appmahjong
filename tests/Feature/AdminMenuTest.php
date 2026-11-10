<?php

namespace Tests\Feature;

use App\Models\Kategorimenu;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminMenuTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withSession(['admin_id' => 1]);
    }

    public function test_tamu_diarahkan_ke_halaman_login_admin(): void
    {
        $this->get('/admin/kategori-menus')->assertRedirect(route('admin.login'));
        $this->get('/admin/menus')->assertRedirect(route('admin.login'));
    }

    public function test_admin_bisa_melihat_daftar_kategori_menu(): void
    {
        Kategorimenu::create(['kategori' => 'Makanan']);

        $this->admin()
            ->get('/admin/kategori-menus')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/KategoriMenu/Index')
                ->has('kategoriMenus.data', 1)
                ->where('kategoriMenus.data.0.kategori', 'Makanan')
                ->where('kategoriMenus.data.0.menus_count', 0)
            );
    }

    public function test_admin_bisa_menambahkan_kategori_menu(): void
    {
        $this->admin()
            ->post('/admin/kategori-menus', ['kategori' => 'Minuman'])
            ->assertRedirect(route('admin.kategori-menus.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('kategorimenus', ['kategori' => 'Minuman']);
    }

    public function test_nama_kategori_menu_wajib_diisi_dan_unik(): void
    {
        Kategorimenu::create(['kategori' => 'Makanan']);

        $this->admin()
            ->post('/admin/kategori-menus', ['kategori' => ''])
            ->assertSessionHasErrors('kategori');

        $this->admin()
            ->post('/admin/kategori-menus', ['kategori' => 'Makanan'])
            ->assertSessionHasErrors('kategori');

        $this->assertSame(1, Kategorimenu::query()->count());
    }

    public function test_admin_bisa_memperbarui_kategori_menu(): void
    {
        $kategori = Kategorimenu::create(['kategori' => 'Minuman']);

        $this->admin()
            ->put("/admin/kategori-menus/{$kategori->id}", ['kategori' => 'Minuman Dingin'])
            ->assertRedirect(route('admin.kategori-menus.index'))
            ->assertSessionHas('success');

        $this->assertSame('Minuman Dingin', $kategori->fresh()->kategori);
    }

    public function test_kategori_yang_masih_memiliki_menu_tidak_bisa_dihapus(): void
    {
        $kategori = Kategorimenu::create(['kategori' => 'Makanan']);
        Menu::create([
            'id_kategori' => (string) $kategori->id,
            'nama' => 'Nasi Goreng',
            'dibuat_di' => 'Dapur',
            'harga' => '25000',
            'status' => 'aktif',
        ]);

        $this->admin()
            ->delete("/admin/kategori-menus/{$kategori->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('kategorimenus', ['id' => $kategori->id]);
    }

    public function test_kategori_tanpa_menu_bisa_dihapus(): void
    {
        $kategori = Kategorimenu::create(['kategori' => 'Makanan']);

        $this->admin()
            ->delete("/admin/kategori-menus/{$kategori->id}")
            ->assertRedirect(route('admin.kategori-menus.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('kategorimenus', ['id' => $kategori->id]);
    }

    public function test_admin_bisa_melihat_daftar_menu_beserta_kategorinya(): void
    {
        $kategori = Kategorimenu::create(['kategori' => 'Makanan']);
        Menu::create([
            'id_kategori' => (string) $kategori->id,
            'nama' => 'Nasi Goreng',
            'dibuat_di' => 'Dapur',
            'harga' => '25000',
            'status' => 'aktif',
        ]);

        $this->admin()
            ->get('/admin/menus')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Menu/Index')
                ->has('menus.data', 1)
                ->where('menus.data.0.nama', 'Nasi Goreng')
                ->where('menus.data.0.kategori.kategori', 'Makanan')
                ->has('kategoriMenus', 1)
            );
    }

    public function test_admin_bisa_menambahkan_menu(): void
    {
        $kategori = Kategorimenu::create(['kategori' => 'Makanan']);

        $this->admin()
            ->post('/admin/menus', [
                'id_kategori' => (string) $kategori->id,
                'nama' => 'Nasi Goreng',
                'dibuat_di' => 'Dapur',
                'harga' => '25000',
                'status' => 'aktif',
            ])
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('menus', [
            'id_kategori' => (string) $kategori->id,
            'nama' => 'Nasi Goreng',
            'dibuat_di' => 'Dapur',
            'harga' => '25000',
            'status' => 'aktif',
        ]);
    }

    public function test_menu_wajib_memiliki_kategori_yang_valid(): void
    {
        $this->admin()
            ->post('/admin/menus', [
                'id_kategori' => '999',
                'nama' => 'Nasi Goreng',
                'dibuat_di' => 'Dapur',
                'harga' => '25000',
                'status' => 'aktif',
            ])
            ->assertSessionHasErrors('id_kategori');

        $this->assertSame(0, Menu::query()->count());
    }

    public function test_harga_menu_harus_berupa_angka(): void
    {
        $kategori = Kategorimenu::create(['kategori' => 'Makanan']);

        $this->admin()
            ->post('/admin/menus', [
                'id_kategori' => (string) $kategori->id,
                'nama' => 'Nasi Goreng',
                'dibuat_di' => 'Dapur',
                'harga' => 'gratis',
                'status' => 'aktif',
            ])
            ->assertSessionHasErrors('harga');

        $this->assertSame(0, Menu::query()->count());
    }

    public function test_admin_bisa_memperbarui_menu(): void
    {
        $kategori = Kategorimenu::create(['kategori' => 'Makanan']);
        $menu = Menu::create([
            'id_kategori' => (string) $kategori->id,
            'nama' => 'Nasi Goreng',
            'dibuat_di' => 'Dapur',
            'harga' => '25000',
            'status' => 'aktif',
        ]);

        $this->admin()
            ->put("/admin/menus/{$menu->id}", [
                'id_kategori' => (string) $kategori->id,
                'nama' => 'Nasi Goreng Spesial',
                'dibuat_di' => 'Dapur',
                'harga' => '30000',
                'status' => 'nonaktif',
            ])
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('success');

        $menu->refresh();

        $this->assertSame('Nasi Goreng Spesial', $menu->nama);
        $this->assertSame('30000', $menu->harga);
        $this->assertSame('nonaktif', $menu->status);
    }

    public function test_admin_bisa_mengubah_status_menu(): void
    {
        $kategori = Kategorimenu::create(['kategori' => 'Makanan']);
        $menu = Menu::create([
            'id_kategori' => (string) $kategori->id,
            'nama' => 'Nasi Goreng',
            'dibuat_di' => 'Dapur',
            'harga' => '25000',
            'status' => 'aktif',
        ]);

        $this->admin()
            ->patch("/admin/menus/{$menu->id}/status")
            ->assertSessionHas('success');

        $this->assertSame('nonaktif', $menu->fresh()->status);

        $this->admin()->patch("/admin/menus/{$menu->id}/status");

        $this->assertSame('aktif', $menu->fresh()->status);
    }

    public function test_admin_bisa_menghapus_menu(): void
    {
        $kategori = Kategorimenu::create(['kategori' => 'Makanan']);
        $menu = Menu::create([
            'id_kategori' => (string) $kategori->id,
            'nama' => 'Nasi Goreng',
            'dibuat_di' => 'Dapur',
            'harga' => '25000',
            'status' => 'aktif',
        ]);

        $this->admin()
            ->delete("/admin/menus/{$menu->id}")
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
    }

    public function test_admin_bisa_mengunggah_gambar_saat_menambahkan_menu(): void
    {
        Storage::fake('public');
        $kategori = Kategorimenu::create(['kategori' => 'Makanan']);

        $this->admin()
            ->post('/admin/menus', [
                'id_kategori' => (string) $kategori->id,
                'nama' => 'Nasi Goreng',
                'gambar' => UploadedFile::fake()->image('nasi-goreng.jpg'),
                'dibuat_di' => 'Dapur',
                'harga' => '25000',
                'status' => 'aktif',
            ])
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('success');

        $menu = Menu::query()->firstOrFail();

        $this->assertNotNull($menu->gambar);
        Storage::disk('public')->assertExists($menu->gambar);
    }

    public function test_gambar_menu_harus_berupa_file_gambar_yang_valid(): void
    {
        Storage::fake('public');
        $kategori = Kategorimenu::create(['kategori' => 'Makanan']);

        $this->admin()
            ->post('/admin/menus', [
                'id_kategori' => (string) $kategori->id,
                'nama' => 'Nasi Goreng',
                'gambar' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
                'dibuat_di' => 'Dapur',
                'harga' => '25000',
                'status' => 'aktif',
            ])
            ->assertSessionHasErrors('gambar');

        $this->assertSame(0, Menu::query()->count());
    }

    public function test_mengganti_gambar_menu_menghapus_gambar_lama(): void
    {
        Storage::fake('public');
        $kategori = Kategorimenu::create(['kategori' => 'Makanan']);
        $menu = Menu::create([
            'id_kategori' => (string) $kategori->id,
            'nama' => 'Nasi Goreng',
            'dibuat_di' => 'Dapur',
            'harga' => '25000',
            'status' => 'aktif',
        ]);

        $this->admin()->put("/admin/menus/{$menu->id}", [
            'id_kategori' => (string) $kategori->id,
            'nama' => 'Nasi Goreng',
            'gambar' => UploadedFile::fake()->image('lama.jpg'),
            'dibuat_di' => 'Dapur',
            'harga' => '25000',
            'status' => 'aktif',
        ]);

        $gambarLama = $menu->fresh()->gambar;
        $this->assertNotNull($gambarLama);

        $this->admin()->put("/admin/menus/{$menu->id}", [
            'id_kategori' => (string) $kategori->id,
            'nama' => 'Nasi Goreng',
            'gambar' => UploadedFile::fake()->image('baru.jpg'),
            'dibuat_di' => 'Dapur',
            'harga' => '25000',
            'status' => 'aktif',
        ]);

        $gambarBaru = $menu->fresh()->gambar;

        $this->assertNotSame($gambarLama, $gambarBaru);
        Storage::disk('public')->assertMissing($gambarLama);
        Storage::disk('public')->assertExists($gambarBaru);
    }

    public function test_admin_bisa_menghapus_gambar_menu(): void
    {
        Storage::fake('public');
        $kategori = Kategorimenu::create(['kategori' => 'Makanan']);
        $menu = Menu::create([
            'id_kategori' => (string) $kategori->id,
            'nama' => 'Nasi Goreng',
            'dibuat_di' => 'Dapur',
            'harga' => '25000',
            'status' => 'aktif',
        ]);

        $this->admin()->put("/admin/menus/{$menu->id}", [
            'id_kategori' => (string) $kategori->id,
            'nama' => 'Nasi Goreng',
            'gambar' => UploadedFile::fake()->image('nasi-goreng.jpg'),
            'dibuat_di' => 'Dapur',
            'harga' => '25000',
            'status' => 'aktif',
        ]);

        $gambar = $menu->fresh()->gambar;
        $this->assertNotNull($gambar);

        $this->admin()
            ->put("/admin/menus/{$menu->id}", [
                'id_kategori' => (string) $kategori->id,
                'nama' => 'Nasi Goreng',
                'hapus_gambar' => true,
                'dibuat_di' => 'Dapur',
                'harga' => '25000',
                'status' => 'aktif',
            ])
            ->assertRedirect(route('admin.menus.index'));

        $this->assertNull($menu->fresh()->gambar);
        Storage::disk('public')->assertMissing($gambar);
    }

    public function test_menghapus_menu_menghapus_file_gambarnya(): void
    {
        Storage::fake('public');
        $kategori = Kategorimenu::create(['kategori' => 'Makanan']);
        $menu = Menu::create([
            'id_kategori' => (string) $kategori->id,
            'nama' => 'Nasi Goreng',
            'gambar' => UploadedFile::fake()->image('nasi-goreng.jpg')->store('menus', 'public'),
            'dibuat_di' => 'Dapur',
            'harga' => '25000',
            'status' => 'aktif',
        ]);

        $gambar = $menu->gambar;
        Storage::disk('public')->assertExists($gambar);

        $this->admin()
            ->delete("/admin/menus/{$menu->id}")
            ->assertRedirect(route('admin.menus.index'));

        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
        Storage::disk('public')->assertMissing($gambar);
    }

    public function test_pencarian_menu_berdasarkan_nama_dan_kategori(): void
    {
        $makanan = Kategorimenu::create(['kategori' => 'Makanan']);
        $minuman = Kategorimenu::create(['kategori' => 'Minuman']);

        Menu::create([
            'id_kategori' => (string) $makanan->id,
            'nama' => 'Nasi Goreng',
            'dibuat_di' => 'Dapur',
            'harga' => '25000',
            'status' => 'aktif',
        ]);
        Menu::create([
            'id_kategori' => (string) $minuman->id,
            'nama' => 'Es Teh',
            'dibuat_di' => 'Dapur',
            'harga' => '5000',
            'status' => 'aktif',
        ]);

        $this->admin()
            ->get('/admin/menus?search=Goreng')
            ->assertInertia(fn (Assert $page) => $page
                ->has('menus.data', 1)
                ->where('menus.data.0.nama', 'Nasi Goreng')
            );

        $this->admin()
            ->get('/admin/menus?kategori='.$minuman->id)
            ->assertInertia(fn (Assert $page) => $page
                ->has('menus.data', 1)
                ->where('menus.data.0.nama', 'Es Teh')
            );
    }

    public function test_admin_bisa_menambahkan_minuman_dengan_harga_dingin_dan_panas(): void
    {
        $kategori = Kategorimenu::create(['kategori' => 'Minuman']);

        $this->admin()
            ->post('/admin/menus', [
                'id_kategori' => (string) $kategori->id,
                'nama' => 'Es Teh',
                'dibuat_di' => 'Bar',
                'harga_dingin' => '8000',
                'harga_panas' => '10000',
                'status' => 'aktif',
            ])
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('success');

        $menu = Menu::query()->firstOrFail();

        $this->assertSame('Bar', $menu->dibuat_di);
        $this->assertSame('8000', $menu->harga_dingin);
        $this->assertSame('10000', $menu->harga_panas);
        $this->assertSame('8000', $menu->harga);
    }

    public function test_minuman_wajib_memiliki_harga_dingin_dan_panas(): void
    {
        $kategori = Kategorimenu::create(['kategori' => 'Minuman']);

        $this->admin()
            ->post('/admin/menus', [
                'id_kategori' => (string) $kategori->id,
                'nama' => 'Es Teh',
                'dibuat_di' => 'Bar',
                'status' => 'aktif',
            ])
            ->assertSessionHasErrors(['harga_dingin', 'harga_panas']);

        $this->assertSame(0, Menu::query()->count());
    }

    public function test_makanan_tidak_memakai_harga_varian_suhu(): void
    {
        $kategori = Kategorimenu::create(['kategori' => 'Makanan']);

        $this->admin()
            ->post('/admin/menus', [
                'id_kategori' => (string) $kategori->id,
                'nama' => 'Nasi Goreng',
                'dibuat_di' => 'Dapur',
                'harga' => '25000',
                'harga_dingin' => '1000',
                'harga_panas' => '2000',
                'status' => 'aktif',
            ])
            ->assertRedirect(route('admin.menus.index'));

        $menu = Menu::query()->firstOrFail();

        $this->assertNull($menu->harga_dingin);
        $this->assertNull($menu->harga_panas);
        $this->assertSame('25000', $menu->harga);
    }

    public function test_edit_menu_tanpa_mengganti_gambar_tetap_bisa_disimpan(): void
    {
        $kategori = Kategorimenu::create(['kategori' => 'Makanan']);
        $menu = Menu::create([
            'id_kategori' => (string) $kategori->id,
            'nama' => 'Nasi Goreng',
            'dibuat_di' => 'Dapur',
            'harga' => '25000',
            'status' => 'aktif',
        ]);

        $this->admin()
            ->put("/admin/menus/{$menu->id}", [
                'id_kategori' => (string) $kategori->id,
                'nama' => 'Nasi Goreng Spesial',
                'gambar' => '',
                'hapus_gambar' => '0',
                'dibuat_di' => 'Dapur',
                'harga' => '28000',
                'harga_dingin' => '',
                'harga_panas' => '',
                'status' => 'aktif',
            ])
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHasNoErrors();

        $menu->refresh();

        $this->assertSame('Nasi Goreng Spesial', $menu->nama);
        $this->assertSame('28000', $menu->harga);
    }

    public function test_edit_minuman_dengan_harga_varian_tetap_bisa_disimpan(): void
    {
        $kategori = Kategorimenu::create(['kategori' => 'Minuman']);
        $menu = Menu::create([
            'id_kategori' => (string) $kategori->id,
            'nama' => 'Es Teh',
            'dibuat_di' => 'Bar',
            'harga' => '8000',
            'harga_dingin' => '8000',
            'harga_panas' => '10000',
            'status' => 'aktif',
        ]);

        $this->admin()
            ->put("/admin/menus/{$menu->id}", [
                'id_kategori' => (string) $kategori->id,
                'nama' => 'Es Teh Manis',
                'gambar' => '',
                'hapus_gambar' => '0',
                'dibuat_di' => 'Bar',
                'harga_dingin' => '9000',
                'harga_panas' => '11000',
                'status' => 'aktif',
            ])
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHasNoErrors();

        $menu->refresh();

        $this->assertSame('Es Teh Manis', $menu->nama);
        $this->assertSame('9000', $menu->harga_dingin);
        $this->assertSame('11000', $menu->harga_panas);
        $this->assertSame('9000', $menu->harga);
    }
}
