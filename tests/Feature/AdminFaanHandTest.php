<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Aturanmahjong;
use App\Models\Faanhand;
use App\Models\Kategorifaan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminFaanHandTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withSession(['admin_id' => 1]);
    }

    private function buatAturanMahjong(array $data = []): Aturanmahjong
    {
        return Aturanmahjong::create([
            'kode' => 'AM001',
            'nama_aturan' => 'HK Klasik Tanpa Pangkat',
            ...$data,
        ]);
    }

    private function buatKategoriFaan(string $nama = 'Hand Khusus'): Kategorifaan
    {
        return Kategorifaan::create(['nama' => $nama]);
    }

    private function buatFaanHand(Aturanmahjong $aturanMahjong, array $data = []): Faanhand
    {
        return Faanhand::create([
            'id_aturanmahjong' => (string) $aturanMahjong->id,
            'id_kategorifaan' => (string) $this->buatKategoriFaan()->id,
            'nama_faan' => '清一色',
            'inggris' => 'Full Flush',
            'faan_asli' => '24',
            'faan' => '8',
            ...$data,
        ]);
    }

    public function test_tamu_diarahkan_ke_halaman_login_admin(): void
    {
        $this->get('/admin/faan-hands')->assertRedirect(route('admin.login'));
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
            ->get('/admin/faan-hands')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error', 'Anda tidak memiliki hak akses untuk membuka menu "Faan Hand".');
    }

    public function test_admin_bisa_melihat_daftar_dari_tabel(): void
    {
        $aturanMahjong = $this->buatAturanMahjong();
        $this->buatFaanHand($aturanMahjong, [
            'nama_faan' => 'Pung',
            'inggris' => 'Three of a Kind',
            'faan_asli' => '16',
            'faan' => '4',
        ]);

        $this->admin()
            ->get('/admin/faan-hands')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/FaanHand/Index')
                ->has('faanHands.data', 1)
                ->where('faanHands.data.0.nama_faan', 'Pung')
                ->where('faanHands.data.0.inggris', 'Three of a Kind')
                ->where('faanHands.data.0.faan_asli', '16')
                ->where('faanHands.data.0.faan', '4')
                ->where('faanHands.data.0.aturan_mahjong.kode', 'AM001')
                ->where('faanHands.data.0.kategori_faan.nama', 'Hand Khusus')
                ->has('aturanMahjongs', 1)
                ->has('kategoriFaans', 1)
            );
    }

    public function test_admin_bisa_menambahkan_faan_hand(): void
    {
        $aturanMahjong = $this->buatAturanMahjong();
        $kategoriFaan = $this->buatKategoriFaan();

        $this->admin()
            ->post('/admin/faan-hands', [
                'id_aturanmahjong' => $aturanMahjong->id,
                'id_kategorifaan' => $kategoriFaan->id,
                'nama_faan' => '清一色',
                'inggris' => 'Full Flush',
                'faan_asli' => 24,
                'faan' => 8,
            ])
            ->assertRedirect(route('admin.faan-hands.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('faanhands', [
            'id_aturanmahjong' => (string) $aturanMahjong->id,
            'id_kategorifaan' => (string) $kategoriFaan->id,
            'nama_faan' => '清一色',
            'inggris' => 'Full Flush',
            'faan_asli' => '24',
            'faan' => '8',
        ]);
    }

    public function test_field_wajib_diisi(): void
    {
        $this->admin()
            ->post('/admin/faan-hands', [])
            ->assertSessionHasErrors([
                'id_aturanmahjong' => 'Aturan mahjong wajib dipilih.',
                'id_kategorifaan' => 'Kategori faan wajib dipilih.',
                'nama_faan' => 'Nama faan wajib diisi.',
                'inggris' => 'Nama Inggris wajib diisi.',
                'faan_asli' => 'Faan asli wajib diisi.',
                'faan' => 'Faan wajib diisi.',
            ]);

        $this->assertSame(0, Faanhand::query()->count());
    }

    public function test_aturan_mahjong_yang_tidak_ada_ditolak(): void
    {
        $kategoriFaan = $this->buatKategoriFaan();

        $this->admin()
            ->post('/admin/faan-hands', [
                'id_aturanmahjong' => 999,
                'id_kategorifaan' => $kategoriFaan->id,
                'nama_faan' => '清一色',
                'inggris' => 'Full Flush',
                'faan_asli' => 24,
                'faan' => 8,
            ])
            ->assertSessionHasErrors([
                'id_aturanmahjong' => 'Aturan mahjong yang dipilih tidak ditemukan.',
            ]);

        $this->assertSame(0, Faanhand::query()->count());
    }

    public function test_kategori_faan_yang_tidak_ada_ditolak(): void
    {
        $aturanMahjong = $this->buatAturanMahjong();

        $this->admin()
            ->post('/admin/faan-hands', [
                'id_aturanmahjong' => $aturanMahjong->id,
                'id_kategorifaan' => 999,
                'nama_faan' => '清一色',
                'inggris' => 'Full Flush',
                'faan_asli' => 24,
                'faan' => 8,
            ])
            ->assertSessionHasErrors([
                'id_kategorifaan' => 'Kategori faan yang dipilih tidak ditemukan.',
            ]);

        $this->assertSame(0, Faanhand::query()->count());
    }

    public function test_faan_asli_tidak_boleh_negatif(): void
    {
        $aturanMahjong = $this->buatAturanMahjong();
        $kategoriFaan = $this->buatKategoriFaan();

        $this->admin()
            ->post('/admin/faan-hands', [
                'id_aturanmahjong' => $aturanMahjong->id,
                'id_kategorifaan' => $kategoriFaan->id,
                'nama_faan' => '清一色',
                'inggris' => 'Full Flush',
                'faan_asli' => -1,
                'faan' => 8,
            ])
            ->assertSessionHasErrors([
                'faan_asli' => 'Faan asli tidak boleh kurang dari 0.',
            ]);

        $this->assertSame(0, Faanhand::query()->count());
    }

    public function test_faan_harus_berupa_angka_bulat(): void
    {
        $aturanMahjong = $this->buatAturanMahjong();
        $kategoriFaan = $this->buatKategoriFaan();

        $this->admin()
            ->post('/admin/faan-hands', [
                'id_aturanmahjong' => $aturanMahjong->id,
                'id_kategorifaan' => $kategoriFaan->id,
                'nama_faan' => '清一色',
                'inggris' => 'Full Flush',
                'faan_asli' => 24,
                'faan' => 'abc',
            ])
            ->assertSessionHasErrors([
                'faan' => 'Faan harus berupa angka bulat.',
            ]);

        $this->assertSame(0, Faanhand::query()->count());
    }

    public function test_admin_bisa_memperbarui_faan_hand(): void
    {
        $aturanMahjong = $this->buatAturanMahjong();
        $kategoriFaan = $this->buatKategoriFaan('Chow Khusus');
        $faanHand = $this->buatFaanHand($aturanMahjong);

        $this->admin()
            ->put("/admin/faan-hands/{$faanHand->id}", [
                'id_aturanmahjong' => $aturanMahjong->id,
                'id_kategorifaan' => $kategoriFaan->id,
                'nama_faan' => '门清',
                'inggris' => 'Concealed Hand',
                'faan_asli' => 18,
                'faan' => 6,
            ])
            ->assertRedirect(route('admin.faan-hands.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('faanhands', [
            'id' => $faanHand->id,
            'id_kategorifaan' => (string) $kategoriFaan->id,
            'nama_faan' => '门清',
            'inggris' => 'Concealed Hand',
            'faan_asli' => '18',
            'faan' => '6',
        ]);
    }

    public function test_admin_bisa_menghapus_faan_hand(): void
    {
        $aturanMahjong = $this->buatAturanMahjong();
        $faanHand = $this->buatFaanHand($aturanMahjong);

        $this->admin()
            ->delete("/admin/faan-hands/{$faanHand->id}")
            ->assertRedirect(route('admin.faan-hands.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('faanhands', ['id' => $faanHand->id]);
    }

    public function test_pencarian_berdasarkan_nama_kategori_dan_aturan(): void
    {
        $pertama = $this->buatAturanMahjong();
        $kedua = $this->buatAturanMahjong([
            'kode' => 'AM002',
            'nama_aturan' => 'Turnamen Regional',
        ]);
        $kategoriPertama = $this->buatKategoriFaan('Hand Khusus');
        $kategoriKedua = $this->buatKategoriFaan('Suit Spesial');

        $this->buatFaanHand($pertama, ['id_kategorifaan' => (string) $kategoriPertama->id]);
        $this->buatFaanHand($kedua, [
            'id_kategorifaan' => (string) $kategoriKedua->id,
            'nama_faan' => '大三元',
            'inggris' => 'Big Three Dragons',
            'faan' => '16',
        ]);

        $this->admin()
            ->get('/admin/faan-hands?search=AM002')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('faanHands.data', 1)
                ->where('faanHands.data.0.aturan_mahjong.kode', 'AM002')
            );

        $this->admin()
            ->get('/admin/faan-hands?search=Dragons')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('faanHands.data', 1)
                ->where('faanHands.data.0.inggris', 'Big Three Dragons')
            );

        $this->admin()
            ->get('/admin/faan-hands?search=Suit Spesial')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('faanHands.data', 1)
                ->where('faanHands.data.0.kategori_faan.nama', 'Suit Spesial')
            );
    }
}
