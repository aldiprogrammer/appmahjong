<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Kategorimenu;
use App\Models\Menu;
use App\Models\Vanue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminMenuVanueTest extends TestCase
{
    use RefreshDatabase;

    private function buatVanue(string $nama): Vanue
    {
        return Vanue::create([
            'nama' => $nama,
            'alamat' => 'Jl. Contoh No. 1',
            'logo' => 'vanues/'.strtolower(str_replace(' ', '-', $nama)).'.png',
        ]);
    }

    private function adminVenue(Vanue $vanue, string $username, string $role = 'admin'): Admin
    {
        return Admin::create([
            'username' => $username,
            'role' => $role,
            'vanue_id' => $vanue->id,
            'password' => 'password123',
        ]);
    }

    private function superAdmin(): Admin
    {
        return Admin::create([
            'username' => 'super',
            'role' => 'super_admin',
            'password' => 'password123',
        ]);
    }

    private function masukSebagai(Admin $admin): static
    {
        return $this->withSession(['admin_id' => $admin->id]);
    }

    private function buatKategori(string $nama, ?Vanue $vanue): Kategorimenu
    {
        return Kategorimenu::create([
            'kategori' => $nama,
            'vanue_id' => $vanue?->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataMenu(Kategorimenu $kategori, string $nama = 'Nasi Goreng'): array
    {
        return [
            'id_kategori' => (string) $kategori->id,
            'nama' => $nama,
            'dibuat_di' => 'Dapur',
            'harga' => 25000,
            'status' => 'aktif',
        ];
    }

    public function test_admin_venue_hanya_melihat_kategori_dan_menu_venue_sendiri(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $kategoriJakarta = $this->buatKategori('Makanan', $jakarta);
        $kategoriSurabaya = $this->buatKategori('Minuman', $surabaya);
        Menu::create($this->dataMenu($kategoriJakarta) + ['vanue_id' => $jakarta->id]);
        Menu::create($this->dataMenu($kategoriSurabaya, 'Es Teh') + ['vanue_id' => $surabaya->id]);
        $adminJakarta = $this->adminVenue($jakarta, 'admin_jakarta');

        $this->masukSebagai($adminJakarta)
            ->get('/admin/kategori-menus')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/KategoriMenu/Index')
                ->has('kategoriMenus.data', 1)
                ->where('kategoriMenus.data.0.kategori', 'Makanan')
                ->where('kategoriMenus.data.0.vanue.nama', 'Mahjong Center Jakarta')
            );

        $this->masukSebagai($adminJakarta)
            ->get('/admin/menus')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Menu/Index')
                ->has('menus.data', 1)
                ->where('menus.data.0.nama', 'Nasi Goreng')
                ->where('menus.data.0.vanue.nama', 'Mahjong Center Jakarta')
                ->has('kategoriMenus', 1)
                ->where('kategoriMenus.0.kategori', 'Makanan')
            );
    }

    public function test_admin_venue_menambah_kategori_otomatis_pada_venue_sendiri(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $adminJakarta = $this->adminVenue($jakarta, 'admin_jakarta');

        $this->masukSebagai($adminJakarta)
            ->post('/admin/kategori-menus', [
                'kategori' => 'Minuman',
                'vanue_id' => $surabaya->id,
            ])
            ->assertRedirect(route('admin.kategori-menus.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('kategorimenus', [
            'kategori' => 'Minuman',
            'vanue_id' => $jakarta->id,
        ]);
    }

    public function test_kategori_dengan_nama_sama_boleh_ada_di_venue_berbeda(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $this->buatKategori('Makanan', $jakarta);
        $superAdmin = $this->superAdmin();

        $this->masukSebagai($superAdmin)
            ->post('/admin/kategori-menus', [
                'kategori' => 'Makanan',
                'vanue_id' => $surabaya->id,
            ])
            ->assertRedirect(route('admin.kategori-menus.index'))
            ->assertSessionHasNoErrors();

        $this->masukSebagai($superAdmin)
            ->post('/admin/kategori-menus', [
                'kategori' => 'Makanan',
                'vanue_id' => '',
            ])
            ->assertRedirect(route('admin.kategori-menus.index'))
            ->assertSessionHasNoErrors();

        $this->masukSebagai($superAdmin)
            ->post('/admin/kategori-menus', [
                'kategori' => 'Makanan',
                'vanue_id' => $jakarta->id,
            ])
            ->assertSessionHasErrors('kategori');

        $this->assertSame(3, Kategorimenu::query()->count());
    }

    public function test_admin_venue_tidak_bisa_mengubah_atau_menghapus_data_venue_lain(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $kategoriSurabaya = $this->buatKategori('Makanan', $surabaya);
        $menuSurabaya = Menu::create($this->dataMenu($kategoriSurabaya) + ['vanue_id' => $surabaya->id]);
        $adminJakarta = $this->adminVenue($jakarta, 'admin_jakarta');

        $this->masukSebagai($adminJakarta)
            ->put("/admin/kategori-menus/{$kategoriSurabaya->id}", ['kategori' => 'Makanan Baru'])
            ->assertForbidden();

        $this->masukSebagai($adminJakarta)
            ->delete("/admin/kategori-menus/{$kategoriSurabaya->id}")
            ->assertForbidden();

        $this->masukSebagai($adminJakarta)
            ->put("/admin/menus/{$menuSurabaya->id}", $this->dataMenu($kategoriSurabaya, 'Menu Baru'))
            ->assertForbidden();

        $this->masukSebagai($adminJakarta)
            ->patch("/admin/menus/{$menuSurabaya->id}/status")
            ->assertForbidden();

        $this->masukSebagai($adminJakarta)
            ->delete("/admin/menus/{$menuSurabaya->id}")
            ->assertForbidden();

        $this->assertSame('Makanan', $kategoriSurabaya->fresh()->kategori);
        $this->assertSame('Nasi Goreng', $menuSurabaya->fresh()->nama);
        $this->assertSame('aktif', $menuSurabaya->fresh()->status);
        $this->assertDatabaseHas('menus', ['id' => $menuSurabaya->id]);
    }

    public function test_admin_venue_bisa_mengelola_kategori_dan_menu_venue_sendiri(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $kategoriJakarta = $this->buatKategori('Makanan', $jakarta);
        $menuJakarta = Menu::create($this->dataMenu($kategoriJakarta) + ['vanue_id' => $jakarta->id]);
        $adminJakarta = $this->adminVenue($jakarta, 'admin_jakarta');

        $this->masukSebagai($adminJakarta)
            ->put("/admin/kategori-menus/{$kategoriJakarta->id}", ['kategori' => 'Makanan Utama'])
            ->assertRedirect(route('admin.kategori-menus.index'))
            ->assertSessionHasNoErrors();

        $this->masukSebagai($adminJakarta)
            ->put("/admin/menus/{$menuJakarta->id}", $this->dataMenu($kategoriJakarta, 'Nasi Goreng Spesial'))
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Makanan Utama', $kategoriJakarta->fresh()->kategori);
        $this->assertSame('Nasi Goreng Spesial', $menuJakarta->fresh()->nama);
        $this->assertSame($jakarta->id, $menuJakarta->fresh()->vanue_id);
    }

    public function test_admin_venue_menambah_menu_hanya_boleh_memakai_kategori_venue_sendiri(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $kategoriJakarta = $this->buatKategori('Makanan', $jakarta);
        $kategoriSurabaya = $this->buatKategori('Minuman', $surabaya);
        $adminJakarta = $this->adminVenue($jakarta, 'admin_jakarta');

        $this->masukSebagai($adminJakarta)
            ->post('/admin/menus', $this->dataMenu($kategoriSurabaya))
            ->assertSessionHasErrors('id_kategori');

        $this->assertSame(0, Menu::query()->count());

        $this->masukSebagai($adminJakarta)
            ->post('/admin/menus', $this->dataMenu($kategoriJakarta))
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('menus', [
            'nama' => 'Nasi Goreng',
            'vanue_id' => $jakarta->id,
        ]);
    }

    public function test_super_admin_bisa_memilih_venue_sa_menambah_kategori_dan_menu(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $kategoriJakarta = $this->buatKategori('Makanan', $jakarta);
        $kategoriGlobal = $this->buatKategori('Cemilan', null);
        $superAdmin = $this->superAdmin();

        $this->masukSebagai($superAdmin)
            ->post('/admin/kategori-menus', [
                'kategori' => 'Minuman',
                'vanue_id' => $jakarta->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('kategorimenus', [
            'kategori' => 'Minuman',
            'vanue_id' => $jakarta->id,
        ]);

        $this->masukSebagai($superAdmin)
            ->post('/admin/menus', $this->dataMenu($kategoriJakarta) + ['vanue_id' => $jakarta->id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('menus', [
            'nama' => 'Nasi Goreng',
            'vanue_id' => $jakarta->id,
        ]);

        $this->masukSebagai($superAdmin)
            ->post('/admin/menus', $this->dataMenu($kategoriJakarta) + ['vanue_id' => ''])
            ->assertSessionHasErrors('id_kategori');

        $this->masukSebagai($superAdmin)
            ->post('/admin/menus', $this->dataMenu($kategoriGlobal) + ['vanue_id' => ''])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Menu::query()->count());
    }
}
