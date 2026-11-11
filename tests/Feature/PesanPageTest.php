<?php

namespace Tests\Feature;

use App\Models\Kategorimenu;
use App\Models\Meja;
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

    public function test_pencarian_meja_menemukan_kode_yang_terdaftar(): void
    {
        $meja = Meja::create(['kode_meja' => 'MJ-01', 'nama_meja' => 'Meja 1']);

        $this->getJson('/meja/cari?kode=mj-01')
            ->assertOk()
            ->assertJson([
                'found' => true,
                'meja' => [
                    'id' => $meja->id,
                    'kode_meja' => 'MJ-01',
                    'nama_meja' => 'Meja 1',
                ],
            ])
            ->assertJsonPath('point_url', route('point'))
            ->assertJsonPath('pesan_url', route('pesan', ['meja' => 'MJ-01']));
    }

    public function test_pencarian_meja_mengembalikan_tidak_ditemukan_untuk_kode_asing(): void
    {
        Meja::create(['kode_meja' => 'MJ-01', 'nama_meja' => 'Meja 1']);

        $this->getJson('/meja/cari?kode=MJ-99')
            ->assertOk()
            ->assertJson(['found' => false])
            ->assertJsonPath('message', 'Kode meja tidak ditemukan. Pastikan QR code yang dipindai benar.');
    }

    public function test_halaman_pesan_mengisi_nomor_meja_dari_kode_qr(): void
    {
        $meja = Meja::create(['kode_meja' => 'MJ-02', 'nama_meja' => 'Meja 2']);

        $this->get('/pesan?meja=MJ-02')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Pesan')
                ->where('mejaAwal.id', $meja->id)
                ->where('mejaAwal.kode_meja', 'MJ-02')
                ->where('mejaAwal.nama_meja', 'Meja 2')
            );
    }

    public function test_halaman_pesan_mengabaikan_kode_meja_yang_tidak_dikenal(): void
    {
        $this->get('/pesan?meja=TIDAK-ADA')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Pesan')
                ->where('mejaAwal', null)
            );
    }
}
