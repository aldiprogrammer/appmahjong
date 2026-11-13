<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Vanue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAkunTest extends TestCase
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
        $this->get('/admin/admins')->assertRedirect(route('admin.login'));
    }

    public function test_halaman_admin_menampilkan_daftar_admin_beserta_venue(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $superAdmin = $this->buatAdmin(null, 'super_admin', 'super');
        $this->buatAdmin($vanue, 'staff', 'staff_jakarta');

        $this->masukSebagai($superAdmin)
            ->get('/admin/admins')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Admin/Index')
                ->has('admins.data', 2)
                ->where('admins.data', function ($admins) {
                    $staff = collect($admins)->firstWhere('username', 'staff_jakarta');

                    return data_get($staff, 'vanue.nama') === 'Mahjong Center Jakarta';
                })
                ->has('vanues', 1)
                ->where('vanues.0.nama', 'Mahjong Center Jakarta')
                ->where('auth.admin.username', 'super')
                ->where('auth.admin.vanue', null)
            );
    }

    public function test_admin_baru_wajib_memiliki_venue_kecuali_super_admin(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $superAdmin = $this->buatAdmin(null, 'super_admin', 'super');

        $this->masukSebagai($superAdmin)
            ->post('/admin/admins', [
                'username' => 'staff_baru',
                'role' => 'staff',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSessionHasErrors('vanue_id');

        $this->masukSebagai($superAdmin)
            ->post('/admin/admins', [
                'username' => 'admin_baru',
                'role' => 'super_admin',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.admins.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('admins', ['username' => 'staff_baru']);

        $this->assertDatabaseHas('admins', [
            'username' => 'admin_baru',
            'role' => 'super_admin',
            'vanue_id' => null,
        ]);

        $this->masukSebagai($superAdmin)
            ->post('/admin/admins', [
                'username' => 'staff_jakarta',
                'role' => 'staff',
                'vanue_id' => $vanue->id,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.admins.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('admins', [
            'username' => 'staff_jakarta',
            'vanue_id' => $vanue->id,
        ]);
    }

    public function test_venue_pada_admin_baru_harus_ada(): void
    {
        $superAdmin = $this->buatAdmin(null, 'super_admin', 'super');

        $this->masukSebagai($superAdmin)
            ->post('/admin/admins', [
                'username' => 'staff_baru',
                'role' => 'staff',
                'vanue_id' => 999,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSessionHasErrors('vanue_id');

        $this->assertSame(1, Admin::query()->count());
    }

    public function test_super_admin_bisa_memindahkan_venue_admin_lain(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $superAdmin = $this->buatAdmin(null, 'super_admin', 'super');
        $staff = $this->buatAdmin($jakarta, 'staff', 'staff_jakarta');

        $this->masukSebagai($superAdmin)
            ->put("/admin/admins/{$staff->id}", [
                'username' => 'staff_jakarta',
                'role' => 'admin',
                'vanue_id' => $surabaya->id,
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.admins.index'))
            ->assertSessionHasNoErrors();

        $staff->refresh();

        $this->assertSame($surabaya->id, $staff->vanue_id);
        $this->assertSame('admin', $staff->role);
    }

    public function test_admin_venue_hanya_melihat_admin_pada_venue_nya(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $adminJakarta = $this->buatAdmin($jakarta, 'admin', 'admin_jakarta');
        $this->buatAdmin($surabaya, 'admin', 'admin_surabaya');
        $this->buatAdmin($surabaya, 'super_admin', 'super');

        $this->masukSebagai($adminJakarta)
            ->get('/admin/admins')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Admin/Index')
                ->has('admins.data', 1)
                ->where('admins.data.0.username', 'admin_jakarta')
                ->has('vanues', 1)
                ->where('vanues.0.nama', 'Mahjong Center Jakarta')
                ->where('auth.admin.vanue.nama', 'Mahjong Center Jakarta')
            );

        $this->masukSebagai($adminJakarta)
            ->get('/admin/admins?vanue_id='.$surabaya->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('admins.data', 0)
                ->has('vanues', 1)
            );
    }

    public function test_admin_venue_tidak_bisa_menambahkan_admin_di_venue_lain(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $adminJakarta = $this->buatAdmin($jakarta, 'admin', 'admin_jakarta');

        $this->masukSebagai($adminJakarta)
            ->post('/admin/admins', [
                'username' => 'staff_surabaya',
                'role' => 'staff',
                'vanue_id' => $surabaya->id,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('admins', ['username' => 'staff_surabaya']);

        $this->masukSebagai($adminJakarta)
            ->post('/admin/admins', [
                'username' => 'staff_jakarta',
                'role' => 'staff',
                'vanue_id' => $jakarta->id,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.admins.index'));

        $this->assertDatabaseHas('admins', [
            'username' => 'staff_jakarta',
            'vanue_id' => $jakarta->id,
        ]);
    }

    public function test_admin_venue_tidak_bisa_mengubah_atau_menghapus_admin_venue_lain(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $adminJakarta = $this->buatAdmin($jakarta, 'admin', 'admin_jakarta');
        $staffSurabaya = $this->buatAdmin($surabaya, 'staff', 'staff_surabaya');

        $this->masukSebagai($adminJakarta)
            ->put("/admin/admins/{$staffSurabaya->id}", [
                'username' => 'staff_surabaya',
                'role' => 'staff',
                'vanue_id' => $surabaya->id,
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertForbidden();

        $this->masukSebagai($adminJakarta)
            ->delete("/admin/admins/{$staffSurabaya->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('admins', ['id' => $staffSurabaya->id, 'vanue_id' => $surabaya->id]);
    }
}
