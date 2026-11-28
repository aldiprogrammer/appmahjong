<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Aturandasarfaan;
use App\Models\Aturanmahjong;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAturanDasarFaanTest extends TestCase
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

    private function buatAturanDasarFaan(Aturanmahjong $aturanMahjong, array $data = []): Aturandasarfaan
    {
        return Aturandasarfaan::create([
            'id_aturanmahjong' => (string) $aturanMahjong->id,
            'minimal_faan' => '1',
            'batas_faan' => '3',
            ...$data,
        ]);
    }

    public function test_tamu_diarahkan_ke_halaman_login_admin(): void
    {
        $this->get('/admin/aturan-dasar-faans')->assertRedirect(route('admin.login'));
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
            ->get('/admin/aturan-dasar-faans')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error', 'Anda tidak memiliki hak akses untuk membuka menu "Aturan Dasar Faan".');
    }

    public function test_admin_bisa_melihat_daftar_dari_tabel(): void
    {
        $aturanMahjong = $this->buatAturanMahjong();
        $this->buatAturanDasarFaan($aturanMahjong, [
            'minimal_faan' => '2',
            'batas_faan' => '5',
        ]);

        $this->admin()
            ->get('/admin/aturan-dasar-faans')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AturanDasarFaan/Index')
                ->has('aturanDasarFaans.data', 1)
                ->where('aturanDasarFaans.data.0.minimal_faan', '2')
                ->where('aturanDasarFaans.data.0.batas_faan', '5')
                ->where('aturanDasarFaans.data.0.aturan_mahjong.kode', 'AM001')
                ->has('aturanMahjongs', 1)
            );
    }

    public function test_admin_bisa_menambahkan_aturan_dasar_faan(): void
    {
        $aturanMahjong = $this->buatAturanMahjong();

        $this->admin()
            ->post('/admin/aturan-dasar-faans', [
                'id_aturanmahjong' => $aturanMahjong->id,
                'minimal_faan' => 1,
                'batas_faan' => 4,
            ])
            ->assertRedirect(route('admin.aturan-dasar-faans.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('aturandasarfaans', [
            'id_aturanmahjong' => (string) $aturanMahjong->id,
            'minimal_faan' => '1',
            'batas_faan' => '4',
        ]);
    }

    public function test_field_wajib_diisi(): void
    {
        $this->admin()
            ->post('/admin/aturan-dasar-faans', [])
            ->assertSessionHasErrors([
                'id_aturanmahjong' => 'Aturan mahjong wajib dipilih.',
                'minimal_faan' => 'Minimal faan wajib diisi.',
                'batas_faan' => 'Batas faan wajib diisi.',
            ]);

        $this->assertSame(0, Aturandasarfaan::query()->count());
    }

    public function test_aturan_mahjong_yang_tidak_ada_ditolak(): void
    {
        $this->admin()
            ->post('/admin/aturan-dasar-faans', [
                'id_aturanmahjong' => 999,
                'minimal_faan' => 1,
                'batas_faan' => 4,
            ])
            ->assertSessionHasErrors([
                'id_aturanmahjong' => 'Aturan mahjong yang dipilih tidak ditemukan.',
            ]);

        $this->assertSame(0, Aturandasarfaan::query()->count());
    }

    public function test_minimal_faan_tidak_boleh_negatif(): void
    {
        $aturanMahjong = $this->buatAturanMahjong();

        $this->admin()
            ->post('/admin/aturan-dasar-faans', [
                'id_aturanmahjong' => $aturanMahjong->id,
                'minimal_faan' => -1,
                'batas_faan' => 4,
            ])
            ->assertSessionHasErrors([
                'minimal_faan' => 'Minimal faan tidak boleh kurang dari 0.',
            ]);

        $this->assertSame(0, Aturandasarfaan::query()->count());
    }

    public function test_batas_faan_harus_berupa_angka_bulat(): void
    {
        $aturanMahjong = $this->buatAturanMahjong();

        $this->admin()
            ->post('/admin/aturan-dasar-faans', [
                'id_aturanmahjong' => $aturanMahjong->id,
                'minimal_faan' => 1,
                'batas_faan' => 'abc',
            ])
            ->assertSessionHasErrors([
                'batas_faan' => 'Batas faan harus berupa angka bulat.',
            ]);

        $this->assertSame(0, Aturandasarfaan::query()->count());
    }

    public function test_admin_bisa_memperbarui_aturan_dasar_faan(): void
    {
        $aturanMahjong = $this->buatAturanMahjong();
        $aturanDasarFaan = $this->buatAturanDasarFaan($aturanMahjong);

        $this->admin()
            ->put("/admin/aturan-dasar-faans/{$aturanDasarFaan->id}", [
                'id_aturanmahjong' => $aturanMahjong->id,
                'minimal_faan' => 2,
                'batas_faan' => 6,
            ])
            ->assertRedirect(route('admin.aturan-dasar-faans.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('aturandasarfaans', [
            'id' => $aturanDasarFaan->id,
            'minimal_faan' => '2',
            'batas_faan' => '6',
        ]);
    }

    public function test_admin_bisa_menghapus_aturan_dasar_faan(): void
    {
        $aturanMahjong = $this->buatAturanMahjong();
        $aturanDasarFaan = $this->buatAturanDasarFaan($aturanMahjong);

        $this->admin()
            ->delete("/admin/aturan-dasar-faans/{$aturanDasarFaan->id}")
            ->assertRedirect(route('admin.aturan-dasar-faans.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('aturandasarfaans', ['id' => $aturanDasarFaan->id]);
    }

    public function test_pencarian_berdasarkan_aturan_dan_rentang_faan(): void
    {
        $pertama = $this->buatAturanMahjong();
        $kedua = $this->buatAturanMahjong([
            'kode' => 'AM002',
            'nama_aturan' => 'Turnamen Regional',
        ]);

        $this->buatAturanDasarFaan($pertama, ['minimal_faan' => '1', 'batas_faan' => '3']);
        $this->buatAturanDasarFaan($kedua, ['minimal_faan' => '7', 'batas_faan' => '9']);

        $this->admin()
            ->get('/admin/aturan-dasar-faans?search=AM002')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('aturanDasarFaans.data', 1)
                ->where('aturanDasarFaans.data.0.aturan_mahjong.kode', 'AM002')
            );

        $this->admin()
            ->get('/admin/aturan-dasar-faans?search=9')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('aturanDasarFaans.data', 1)
                ->where('aturanDasarFaans.data.0.batas_faan', '9')
            );
    }
}
