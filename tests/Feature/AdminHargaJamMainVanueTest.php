<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\hargajammain;
use App\Models\jam_main;
use App\Models\Meja;
use App\Models\Pengguna;
use App\Models\Vanue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminHargaJamMainVanueTest extends TestCase
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

    private function buatJamMain(): jam_main
    {
        return jam_main::create([
            'jam_mulai' => '10:00',
            'jam_selesai' => '11:00',
            'status' => 'aktif',
        ]);
    }

    private function buatHarga(jam_main $jam, ?Vanue $vanue, string $harga = '50000', ?Carbon $tanggal = null): hargajammain
    {
        $tanggal ??= Carbon::tomorrow();
        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$tanggal->dayOfWeek];

        return hargajammain::create([
            'jam_main_id' => (string) $jam->id,
            'hari' => $hari,
            'harga' => $harga,
            'diskon' => '0',
            'harga_akhir' => $harga,
            'vanue_id' => $vanue?->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataHarga(jam_main $jam, string $harga = '60000'): array
    {
        return [
            'jam_main_ids' => [(string) $jam->id],
            'hari_list' => ['Senin'],
            'harga' => $harga,
            'diskon' => '0',
        ];
    }

    public function test_admin_venue_hanya_melihat_harga_venue_sendiri(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $jam = $this->buatJamMain();
        $this->buatHarga($jam, $jakarta, '50000');
        $this->buatHarga($jam, $surabaya, '70000');
        $adminJakarta = $this->adminVenue($jakarta, 'admin_jakarta');

        $this->masukSebagai($adminJakarta)
            ->get('/admin/harga-jam-mains')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/HargaJamMain/Index')
                ->has('hargaJamMains.data', 1)
                ->where('hargaJamMains.data.0.harga', '50000')
                ->where('hargaJamMains.data.0.vanue.nama', 'Mahjong Center Jakarta')
                ->has('vanues', 1)
                ->where('vanues.0.nama', 'Mahjong Center Jakarta')
            );

        $this->masukSebagai($this->superAdmin())
            ->get('/admin/harga-jam-mains')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/HargaJamMain/Index')
                ->has('hargaJamMains.data', 2)
                ->has('vanues', 2)
            );
    }

    public function test_admin_venue_menambah_harga_otomatis_pada_venue_sendiri(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $jam = $this->buatJamMain();
        $adminJakarta = $this->adminVenue($jakarta, 'admin_jakarta');

        $this->masukSebagai($adminJakarta)
            ->post('/admin/harga-jam-mains', $this->dataHarga($jam) + ['vanue_id' => $surabaya->id])
            ->assertRedirect(route('admin.harga-jam-mains.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('hargajammains', [
            'jam_main_id' => (string) $jam->id,
            'harga' => '60000',
            'vanue_id' => $jakarta->id,
        ]);
    }

    public function test_super_admin_bisa_memilih_venue_atau_tanpa_venue(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $jam = $this->buatJamMain();
        $superAdmin = $this->superAdmin();

        $this->masukSebagai($superAdmin)
            ->post('/admin/harga-jam-mains', $this->dataHarga($jam, '50000') + ['vanue_id' => $jakarta->id])
            ->assertRedirect(route('admin.harga-jam-mains.index'))
            ->assertSessionHasNoErrors();

        $this->masukSebagai($superAdmin)
            ->post('/admin/harga-jam-mains', $this->dataHarga($jam, '80000') + ['vanue_id' => ''])
            ->assertRedirect(route('admin.harga-jam-mains.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('hargajammains', ['harga' => '50000', 'vanue_id' => $jakarta->id]);
        $this->assertDatabaseHas('hargajammains', ['harga' => '80000', 'vanue_id' => null]);
        $this->assertSame(2, hargajammain::query()->count());
    }

    public function test_admin_venue_tidak_bisa_mengubah_atau_menghapus_harga_venue_lain(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $jam = $this->buatJamMain();
        $hargaSurabaya = $this->buatHarga($jam, $surabaya, '70000');
        $adminJakarta = $this->adminVenue($jakarta, 'admin_jakarta');

        $this->masukSebagai($adminJakarta)
            ->put("/admin/harga-jam-mains/{$hargaSurabaya->id}", $this->dataHarga($jam, '10000'))
            ->assertForbidden();

        $this->masukSebagai($adminJakarta)
            ->delete("/admin/harga-jam-mains/{$hargaSurabaya->id}")
            ->assertForbidden();

        $this->assertSame('70000', $hargaSurabaya->fresh()->harga);
        $this->assertDatabaseHas('hargajammains', ['id' => $hargaSurabaya->id]);
    }

    public function test_admin_venue_bisa_mengelola_harga_venue_sendiri(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $jam = $this->buatJamMain();
        $hargaJakarta = $this->buatHarga($jam, $jakarta, '50000');
        $adminJakarta = $this->adminVenue($jakarta, 'admin_jakarta');

        $this->masukSebagai($adminJakarta)
            ->put("/admin/harga-jam-mains/{$hargaJakarta->id}", $this->dataHarga($jam, '65000'))
            ->assertRedirect(route('admin.harga-jam-mains.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('65000', $hargaJakarta->fresh()->harga);
        $this->assertSame($jakarta->id, $hargaJakarta->fresh()->vanue_id);

        $this->masukSebagai($adminJakarta)
            ->delete("/admin/harga-jam-mains/{$hargaJakarta->id}")
            ->assertRedirect(route('admin.harga-jam-mains.index'));

        $this->assertDatabaseMissing('hargajammains', ['id' => $hargaJakarta->id]);
    }

    public function test_booking_memakai_harga_sesuai_venue_meja(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $jam = $this->buatJamMain();
        $this->buatHarga($jam, $jakarta, '50000');
        $this->buatHarga($jam, $surabaya, '70000');
        $mejaJakarta = Meja::create(['kode_meja' => 'MJ-JKT', 'nama_meja' => 'Meja Jakarta', 'vanue_id' => $jakarta->id]);
        $mejaSurabaya = Meja::create(['kode_meja' => 'MJ-SBY', 'nama_meja' => 'Meja Surabaya', 'vanue_id' => $surabaya->id]);
        $pengguna = $this->buatPengguna('PG-VENUE01', 'venue1@example.com');
        $tanggal = Carbon::tomorrow();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/booking', [
                'meja_id' => (string) $mejaJakarta->id,
                'jam_main_ids' => [(string) $jam->id],
                'tgl_main' => $tanggal->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/booking', [
                'meja_id' => (string) $mejaSurabaya->id,
                'jam_main_ids' => [(string) $jam->id],
                'tgl_main' => $tanggal->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $hargaPerMeja = Booking::query()
            ->pluck('harga', 'id_meja')
            ->all();

        $this->assertSame('50000', $hargaPerMeja['MJ-JKT']);
        $this->assertSame('70000', $hargaPerMeja['MJ-SBY']);
    }

    public function test_booking_gagal_bila_venue_meja_belum_punya_harga(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $jam = $this->buatJamMain();
        $this->buatHarga($jam, $jakarta, '50000');
        $mejaSurabaya = Meja::create(['kode_meja' => 'MJ-SBY', 'nama_meja' => 'Meja Surabaya', 'vanue_id' => $surabaya->id]);
        $pengguna = $this->buatPengguna('PG-VENUE02', 'venue2@example.com');

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->from(route('booking'))
            ->post('/booking', [
                'meja_id' => (string) $mejaSurabaya->id,
                'jam_main_ids' => [(string) $jam->id],
                'tgl_main' => Carbon::tomorrow()->toDateString(),
            ])
            ->assertRedirect(route('booking'))
            ->assertSessionHasErrors([
                'jam_main_ids' => 'Harga untuk hari dan jam yang dipilih belum tersedia pada venue meja tersebut.',
            ]);

        $this->assertSame(0, Booking::query()->count());
    }

    private function buatPengguna(string $kode, string $email): Pengguna
    {
        return Pengguna::create([
            'kode_user' => $kode,
            'nama_depan' => 'Aldi',
            'nama_balakang' => 'Tester',
            'email' => $email,
            'nowa' => '081234567890',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);
    }
}
