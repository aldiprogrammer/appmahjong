<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Meja;
use App\Models\Vanue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminMejaTest extends TestCase
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

    private function buatAdmin(?Vanue $vanue, string $role, string $username): Admin
    {
        return Admin::create([
            'username' => $username,
            'role' => $role,
            'vanue_id' => $vanue?->id,
            'password' => 'password123',
        ]);
    }

    private function masukSebagai(Admin $admin): static
    {
        return $this->withSession(['admin_id' => $admin->id]);
    }

    public function test_tamu_diarahkan_ke_halaman_login_admin(): void
    {
        $this->get('/admin/mejas')->assertRedirect(route('admin.login'));
    }

    public function test_halaman_meja_menampilkan_meja_beserta_venue(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $superAdmin = $this->buatAdmin(null, 'super_admin', 'super');
        Meja::create(['kode_meja' => 'MJ-01', 'nama_meja' => 'Meja VIP 1', 'vanue_id' => $vanue->id]);

        $this->masukSebagai($superAdmin)
            ->get('/admin/mejas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Meja/Index')
                ->has('mejas.data', 1)
                ->where('mejas.data.0.vanue.nama', 'Mahjong Center Jakarta')
                ->has('vanues', 1)
                ->where('vanues.0.nama', 'Mahjong Center Jakarta')
            );
    }

    public function test_menambah_meja_wajib_memilih_venue(): void
    {
        $superAdmin = $this->buatAdmin(null, 'super_admin', 'super');

        $this->masukSebagai($superAdmin)
            ->post('/admin/mejas', [
                'kode_meja' => 'MJ-01',
                'nama_meja' => 'Meja VIP 1',
            ])
            ->assertSessionHasErrors('vanue_id');

        $this->assertSame(0, Meja::query()->count());
    }

    public function test_menambah_meja_dengan_venue_yang_tidak_ada_ditolak(): void
    {
        $superAdmin = $this->buatAdmin(null, 'super_admin', 'super');

        $this->masukSebagai($superAdmin)
            ->post('/admin/mejas', [
                'kode_meja' => 'MJ-01',
                'nama_meja' => 'Meja VIP 1',
                'vanue_id' => 999,
            ])
            ->assertSessionHasErrors('vanue_id');

        $this->assertSame(0, Meja::query()->count());
    }

    public function test_admin_bisa_menambahkan_meja_pada_sebuah_venue(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $superAdmin = $this->buatAdmin(null, 'super_admin', 'super');

        $this->masukSebagai($superAdmin)
            ->post('/admin/mejas', [
                'kode_meja' => 'MJ-01',
                'nama_meja' => 'Meja VIP 1',
                'vanue_id' => $vanue->id,
            ])
            ->assertRedirect(route('admin.mejas.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('mejas', [
            'kode_meja' => 'MJ-01',
            'vanue_id' => $vanue->id,
        ]);
    }

    public function test_admin_venue_hanya_melihat_meja_pada_venue_nya(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $adminJakarta = $this->buatAdmin($jakarta, 'admin', 'admin_jakarta');
        Meja::create(['kode_meja' => 'MJ-JKT', 'nama_meja' => 'Meja Jakarta', 'vanue_id' => $jakarta->id]);
        Meja::create(['kode_meja' => 'MJ-SBY', 'nama_meja' => 'Meja Surabaya', 'vanue_id' => $surabaya->id]);

        $this->masukSebagai($adminJakarta)
            ->get('/admin/mejas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('mejas.data', 1)
                ->where('mejas.data.0.kode_meja', 'MJ-JKT')
                ->has('vanues', 1)
            );

        $this->masukSebagai($adminJakarta)
            ->get('/admin/mejas?vanue_id='.$surabaya->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('mejas.data', 0));
    }

    public function test_admin_venue_tidak_bisa_mengubah_menghapus_atau_mengunduh_qr_meja_venue_lain(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $adminJakarta = $this->buatAdmin($jakarta, 'admin', 'admin_jakarta');
        $mejaSurabaya = Meja::create([
            'kode_meja' => 'MJ-SBY',
            'nama_meja' => 'Meja Surabaya',
            'vanue_id' => $surabaya->id,
        ]);

        $this->masukSebagai($adminJakarta)
            ->put("/admin/mejas/{$mejaSurabaya->id}", [
                'kode_meja' => 'MJ-SBY',
                'nama_meja' => 'Meja Surabaya Baru',
                'vanue_id' => $surabaya->id,
            ])
            ->assertForbidden();

        $this->masukSebagai($adminJakarta)
            ->delete("/admin/mejas/{$mejaSurabaya->id}")
            ->assertForbidden();

        $this->masukSebagai($adminJakarta)
            ->get("/admin/mejas/{$mejaSurabaya->id}/qr-code/gambar")
            ->assertForbidden();

        $this->masukSebagai($adminJakarta)
            ->get("/admin/mejas/{$mejaSurabaya->id}/qr-code")
            ->assertForbidden();

        $this->assertSame('Meja Surabaya', $mejaSurabaya->fresh()->nama_meja);
        $this->assertDatabaseHas('mejas', ['id' => $mejaSurabaya->id]);
    }

    public function test_admin_venue_bisa_mengelola_meja_pada_venue_nya(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $adminJakarta = $this->buatAdmin($jakarta, 'admin', 'admin_jakarta');
        $mejaJakarta = Meja::create([
            'kode_meja' => 'MJ-JKT',
            'nama_meja' => 'Meja Jakarta',
            'vanue_id' => $jakarta->id,
        ]);

        $this->masukSebagai($adminJakarta)
            ->put("/admin/mejas/{$mejaJakarta->id}", [
                'kode_meja' => 'MJ-JKT-2',
                'nama_meja' => 'Meja Jakarta VIP',
                'vanue_id' => $jakarta->id,
            ])
            ->assertRedirect(route('admin.mejas.index'))
            ->assertSessionHasNoErrors();

        $this->masukSebagai($adminJakarta)
            ->put("/admin/mejas/{$mejaJakarta->id}", [
                'kode_meja' => 'MJ-JKT-2',
                'nama_meja' => 'Meja Jakarta VIP',
                'vanue_id' => $surabaya->id,
            ])
            ->assertForbidden();

        $this->assertSame($jakarta->id, $mejaJakarta->fresh()->vanue_id);
        $this->assertSame('Meja Jakarta VIP', $mejaJakarta->fresh()->nama_meja);
    }

    public function test_unduh_semua_qr_hanya_mencakup_meja_pada_venue_admin(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $adminJakarta = $this->buatAdmin($jakarta, 'admin', 'admin_jakarta');
        Meja::create(['kode_meja' => 'MJ-SBY', 'nama_meja' => 'Meja Surabaya', 'vanue_id' => $surabaya->id]);

        $this->masukSebagai($adminJakarta)
            ->get('/admin/mejas/qr-code/semua')
            ->assertRedirect(route('admin.mejas.index'))
            ->assertSessionHas('error');

        Meja::create(['kode_meja' => 'MJ-JKT', 'nama_meja' => 'Meja Jakarta', 'vanue_id' => $jakarta->id]);

        $this->masukSebagai($adminJakarta)
            ->get('/admin/mejas/qr-code/semua')
            ->assertOk()
            ->assertDownload('qr-code-semua-meja.pdf');
    }
}
