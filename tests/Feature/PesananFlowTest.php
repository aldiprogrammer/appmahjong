<?php

namespace Tests\Feature;

use App\Models\Kategorimenu;
use App\Models\Menu;
use App\Models\Pengguna;
use App\Models\Pesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PesananFlowTest extends TestCase
{
    use RefreshDatabase;

    private function buatMenu(string $nama = 'Nasi Goreng', string $harga = '25000', string $status = 'aktif'): Menu
    {
        $kategori = Kategorimenu::firstOrCreate(['kategori' => 'Makanan']);

        return Menu::create([
            'id_kategori' => (string) $kategori->id,
            'nama' => $nama,
            'harga' => $harga,
            'status' => $status,
        ]);
    }

    public function test_pelanggan_bisa_mengirim_pesanan_ke_kasir(): void
    {
        $menu = $this->buatMenu();

        $this->post('/pesan', [
            'atas_nama' => 'Rina',
            'nomor_meja' => '12',
            'catatan' => 'Tanpa sambal',
            'items' => [
                ['id' => $menu->id, 'jumlah' => 2],
            ],
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $pesanan = Pesanan::query()->firstOrFail();

        $this->assertSame('12', $pesanan->nomor_meja);
        $this->assertSame('Rina', $pesanan->nama_pemesan);
        $this->assertSame('Tanpa sambal', $pesanan->catatan);
        $this->assertSame(50000, $pesanan->total);
        $this->assertSame('baru', $pesanan->status);
        $this->assertStringStartsWith('PSN-', $pesanan->kode_pesanan);

        $this->assertDatabaseHas('pesanan_details', [
            'id_pesanan' => $pesanan->id,
            'id_menu' => (string) $menu->id,
            'nama_menu' => 'Nasi Goreng',
            'harga' => 25000,
            'jumlah' => 2,
            'subtotal' => 50000,
        ]);
    }

    public function test_total_pesanan_dihitung_dari_harga_menu_di_database(): void
    {
        $menu = $this->buatMenu('Es Teh', '8000');

        $this->post('/pesan', [
            'atas_nama' => 'Rina',
            'nomor_meja' => 'A3',
            'items' => [
                ['id' => $menu->id, 'jumlah' => 3, 'harga' => 1, 'subtotal' => 3],
            ],
        ])->assertRedirect();

        $this->assertSame(24000, Pesanan::query()->firstOrFail()->total);
    }

    public function test_pesanan_wajib_menyertakan_nomor_meja_dan_menu(): void
    {
        $this->post('/pesan', [
            'nomor_meja' => '',
            'items' => [],
        ])->assertSessionHasErrors(['atas_nama', 'nomor_meja', 'items']);

        $this->assertSame(0, Pesanan::query()->count());
    }

    public function test_menu_nonaktif_tidak_bisa_dipesan(): void
    {
        $menu = $this->buatMenu('Menu Lama', '10000', 'nonaktif');

        $this->post('/pesan', [
            'atas_nama' => 'Rina',
            'nomor_meja' => '5',
            'items' => [
                ['id' => $menu->id, 'jumlah' => 1],
            ],
        ])->assertSessionHasErrors('items');

        $this->assertSame(0, Pesanan::query()->count());
    }

    public function test_pesanan_pengguna_login_menyimpan_identitas_pemesan(): void
    {
        $pengguna = Pengguna::create([
            'kode_user' => 'PG-KASIR01',
            'nama_depan' => 'Budi',
            'nama_balakang' => 'Santoso',
            'email' => 'budi@example.com',
            'password' => 'password',
            'status' => 'aktif',
        ]);
        $menu = $this->buatMenu();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/pesan', [
                'atas_nama' => 'Budi Santoso',
                'nomor_meja' => '7',
                'items' => [
                    ['id' => $menu->id, 'jumlah' => 1],
                ],
            ])->assertRedirect();

        $pesanan = Pesanan::query()->firstOrFail();

        $this->assertSame((string) $pengguna->id, $pesanan->id_pengguna);
        $this->assertSame('Budi Santoso', $pesanan->nama_pemesan);
    }

    public function test_halaman_kasir_menampilkan_pesanan_masuk(): void
    {
        $menu = $this->buatMenu();
        $pesanan = Pesanan::create([
            'kode_pesanan' => 'PSN-TEST-0001',
            'atas_nama' => 'Rina',
            'nomor_meja' => '9',
            'total' => 25000,
            'status' => 'baru',
        ]);
        $pesanan->details()->create([
            'id_menu' => (string) $menu->id,
            'nama_menu' => $menu->nama,
            'harga' => 25000,
            'jumlah' => 1,
            'subtotal' => 25000,
        ]);

        $this->withSession(['admin_id' => 1])
            ->get('/admin/kasir')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Kasir/Index')
                ->has('pesanans.data', 1)
                ->where('pesanans.data.0.kode_pesanan', 'PSN-TEST-0001')
                ->where('pesanans.data.0.nomor_meja', '9')
                ->has('pesanans.data.0.details', 1)
                ->where('ringkasan.baru', 1)
            );
    }

    public function test_endpoint_notifikasi_kasir_hanya_mengembalikan_pesanan_baru(): void
    {
        Pesanan::create([
            'kode_pesanan' => 'PSN-NOTIF-0001',
            'nomor_meja' => '4',
            'nama_pemesan' => 'Dina',
            'total' => 18000,
            'status' => Pesanan::STATUS_BARU,
        ]);

        Pesanan::create([
            'kode_pesanan' => 'PSN-NOTIF-0002',
            'nomor_meja' => '6',
            'nama_pemesan' => 'Eko',
            'total' => 12000,
            'status' => Pesanan::STATUS_SELESAI,
        ]);

        $this->withSession(['admin_id' => 1])
            ->getJson('/admin/kasir/notifikasi')
            ->assertOk()
            ->assertJsonPath('jumlah_baru', 1)
            ->assertJsonCount(1, 'pesanan')
            ->assertJsonPath('pesanan.0.kode_pesanan', 'PSN-NOTIF-0001')
            ->assertJsonPath('pesanan.0.nomor_meja', '4');
    }

    public function test_endpoint_notifikasi_kasir_hanya_untuk_admin(): void
    {
        $this->getJson('/admin/kasir/notifikasi')->assertRedirect(route('admin.login'));
    }

    public function test_admin_kasir_bisa_mengubah_status_pesanan(): void
    {
        $pesanan = Pesanan::create([
            'kode_pesanan' => 'PSN-TEST-0002',
            'atas_nama' => 'Rina',
            'nomor_meja' => '3',
            'total' => 15000,
            'status' => 'baru',
        ]);

        $this->withSession(['admin_id' => 1])
            ->patch("/admin/kasir/{$pesanan->id}/status", ['status' => 'diproses'])
            ->assertSessionHas('success');

        $this->assertSame('diproses', $pesanan->fresh()->status);
    }

    public function test_status_pesanan_harus_valid(): void
    {
        $pesanan = Pesanan::create([
            'kode_pesanan' => 'PSN-TEST-0003',
            'atas_nama' => 'Rina',
            'nomor_meja' => '3',
            'total' => 15000,
            'status' => 'baru',
        ]);

        $this->withSession(['admin_id' => 1])
            ->patch("/admin/kasir/{$pesanan->id}/status", ['status' => 'asal'])
            ->assertSessionHasErrors('status');

        $this->assertSame('baru', $pesanan->fresh()->status);
    }

    public function test_halaman_kasir_hanya_untuk_admin(): void
    {
        $this->get('/admin/kasir')->assertRedirect(route('admin.login'));
    }

    public function test_pesanan_minuman_memakai_harga_sesuai_suhu_dan_dibuat_di_bar(): void
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

        $this->post('/pesan', [
            'atas_nama' => 'Rina',
            'nomor_meja' => '4',
            'items' => [
                ['id' => $menu->id, 'jumlah' => 2, 'suhu' => 'Panas'],
            ],
        ])->assertRedirect();

        $pesanan = Pesanan::query()->firstOrFail();

        $this->assertSame(20000, $pesanan->total);
        $this->assertDatabaseHas('pesanan_details', [
            'id_pesanan' => $pesanan->id,
            'nama_menu' => 'Es Teh',
            'suhu' => 'Panas',
            'dibuat_di' => 'Bar',
            'harga' => 10000,
            'subtotal' => 20000,
        ]);
    }

    public function test_minuman_tanpa_pilihan_suhu_ditolak(): void
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

        $this->post('/pesan', [
            'atas_nama' => 'Rina',
            'nomor_meja' => '4',
            'items' => [
                ['id' => $menu->id, 'jumlah' => 1],
            ],
        ])->assertSessionHasErrors('items');

        $this->assertSame(0, Pesanan::query()->count());
    }

    public function test_pesanan_makanan_menyimpan_tujuan_dapur(): void
    {
        $menu = $this->buatMenu('Nasi Goreng', '25000');

        $this->post('/pesan', [
            'atas_nama' => 'Rina',
            'nomor_meja' => '8',
            'items' => [
                ['id' => $menu->id, 'jumlah' => 1],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('pesanan_details', [
            'nama_menu' => 'Nasi Goreng',
            'dibuat_di' => 'Dapur',
            'suhu' => null,
        ]);
    }

    public function test_riwayat_pesanan_hanya_untuk_pengguna_login(): void
    {
        $this->get('/riwayat/pesanan')->assertRedirect(route('login'));
    }

    public function test_riwayat_pesanan_menampilkan_pesanan_milik_pengguna_dan_statusnya(): void
    {
        $pengguna = Pengguna::create([
            'kode_user' => 'PG-RIWAYAT01',
            'nama_depan' => 'Sinta',
            'nama_balakang' => 'Dewi',
            'email' => 'sinta@example.com',
            'password' => 'password',
            'status' => 'aktif',
        ]);

        $penggunaLain = Pengguna::create([
            'kode_user' => 'PG-RIWAYAT02',
            'nama_depan' => 'Andi',
            'nama_balakang' => 'Putra',
            'email' => 'andi@example.com',
            'password' => 'password',
            'status' => 'aktif',
        ]);

        $pesananSendiri = Pesanan::create([
            'kode_pesanan' => 'PSN-RWT-0001',
            'id_pengguna' => (string) $pengguna->id,
            'nomor_meja' => '5',
            'nama_pemesan' => 'Sinta Dewi',
            'total' => 25000,
            'status' => Pesanan::STATUS_SELESAI,
        ]);
        $pesananSendiri->details()->create([
            'id_menu' => '1',
            'nama_menu' => 'Nasi Goreng',
            'harga' => 25000,
            'jumlah' => 1,
            'subtotal' => 25000,
        ]);

        Pesanan::create([
            'kode_pesanan' => 'PSN-RWT-0002',
            'id_pengguna' => (string) $penggunaLain->id,
            'nomor_meja' => '9',
            'nama_pemesan' => 'Andi Putra',
            'total' => 10000,
            'status' => Pesanan::STATUS_BARU,
        ]);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get('/riwayat/pesanan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/RiwayatPesanan')
                ->has('pesanans.data', 1)
                ->where('pesanans.data.0.kode_pesanan', 'PSN-RWT-0001')
                ->where('pesanans.data.0.status', Pesanan::STATUS_SELESAI)
                ->has('pesanans.data.0.details', 1)
                ->where('ringkasan.total', 1)
                ->where('ringkasan.selesai', 1)
            );
    }

    public function test_riwayat_pesanan_bisa_disaring_berdasarkan_status(): void
    {
        $pengguna = Pengguna::create([
            'kode_user' => 'PG-RIWAYAT03',
            'nama_depan' => 'Rudi',
            'nama_balakang' => 'Hartono',
            'email' => 'rudi@example.com',
            'password' => 'password',
            'status' => 'aktif',
        ]);

        foreach ([
            ['PSN-RWT-0003', Pesanan::STATUS_BARU],
            ['PSN-RWT-0004', Pesanan::STATUS_DIPROSES],
        ] as [$kode, $status]) {
            Pesanan::create([
                'kode_pesanan' => $kode,
                'id_pengguna' => (string) $pengguna->id,
                'nomor_meja' => '3',
                'nama_pemesan' => 'Rudi Hartono',
                'total' => 15000,
                'status' => $status,
            ]);
        }

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get('/riwayat/pesanan?status=diproses')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('pesanans.data', 1)
                ->where('pesanans.data.0.kode_pesanan', 'PSN-RWT-0004')
                ->where('filters.status', 'diproses')
                ->where('ringkasan.total', 2)
            );
    }
}
