<?php

namespace Tests\Feature;

use App\Models\Kategorimenu;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PesanPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_pesan_hanya_menampilkan_kategori_dan_menu_aktif(): void
    {
        $makanan = Kategorimenu::create(['kategori' => 'Makanan']);
        $minuman = Kategorimenu::create(['kategori' => 'Minuman']);
        Kategorimenu::create(['kategori' => 'Kosong']);

        Menu::create([
            'id_kategori' => (string) $makanan->id,
            'nama' => 'Nasi Goreng',
            'harga' => '25000',
            'status' => 'aktif',
        ]);
        Menu::create([
            'id_kategori' => (string) $makanan->id,
            'nama' => 'Menu Nonaktif',
            'harga' => '10000',
            'status' => 'nonaktif',
        ]);
        Menu::create([
            'id_kategori' => (string) $minuman->id,
            'nama' => 'Es Teh',
            'harga' => '5000',
            'status' => 'aktif',
        ]);

        $response = $this->get('/pesan');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Pesan')
                ->where('kategoriMenus.0.kategori', 'Makanan')
                ->where('kategoriMenus.1.kategori', 'Minuman')
                ->has('kategoriMenus', 2)
                ->has('menus', 2)
                ->where('menus.0.nama', 'Es Teh')
                ->where('menus.1.nama', 'Nasi Goreng')
                ->where('menus.1.kategori', 'Makanan')
                ->where('menus.1.gambar_url', null)
                ->missing('kategoriMenus.2')
            );
    }

    public function test_halaman_pesan_menyertakan_url_gambar_menu(): void
    {
        $kategori = Kategorimenu::create(['kategori' => 'Makanan']);

        Menu::create([
            'id_kategori' => (string) $kategori->id,
            'nama' => 'Nasi Goreng',
            'gambar' => 'menus/nasi-goreng.jpg',
            'harga' => '25000',
            'status' => 'aktif',
        ]);

        $this->get('/pesan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('menus', 1)
                ->where('menus.0.gambar_url', asset('storage/menus/nasi-goreng.jpg'))
            );
    }

    public function test_halaman_pesan_tetap_tampil_saat_belum_ada_menu(): void
    {
        $this->get('/pesan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Pesan')
                ->has('kategoriMenus', 0)
                ->has('menus', 0)
            );
    }
}
