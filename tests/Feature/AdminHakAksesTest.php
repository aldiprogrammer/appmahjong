<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Vanue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminHakAksesTest extends TestCase
{
    use RefreshDatabase;

    private function buatVanue(): Vanue
    {
        return Vanue::create([
            'nama' => 'Mahjong Center Jakarta',
            'alamat' => 'Jl. Contoh No. 1',
            'logo' => 'vanues/jakarta.png',
        ]);
    }

    private function buatAdmin(?array $hakAkses, string $role = 'admin', ?Vanue $vanue = null): Admin
    {
        return Admin::create([
            'username' => 'admin_utama',
            'role' => $role,
            'vanue_id' => $vanue?->id,
            'hak_akses' => $hakAkses,
            'password' => 'password123',
        ]);
    }

    private function masukSebagai(Admin $admin): static
    {
        return $this->withSession(['admin_id' => $admin->id]);
    }

    public function test_admin_tanpa_pembatasan_bisa_membuka_semua_menu(): void
    {
        $admin = $this->buatAdmin(null);

        $this->masukSebagai($admin)->get('/admin/dashboard')->assertOk();
        $this->masukSebagai($admin)->get('/admin/mejas')->assertOk();
        $this->masukSebagai($admin)->get('/admin/vouchers')->assertOk();
        $this->masukSebagai($admin)->get('/admin/admins')->assertOk();
    }

    public function test_admin_dengan_hak_akses_terbatas_hanya_bisa_membuka_menu_tercentang(): void
    {
        $admin = $this->buatAdmin(['dashboard', 'mejas']);

        $this->masukSebagai($admin)->get('/admin/dashboard')->assertOk();
        $this->masukSebagai($admin)->get('/admin/mejas')->assertOk();

        $this->masukSebagai($admin)
            ->get('/admin/vouchers')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error');

        $this->masukSebagai($admin)
            ->get('/admin/admins')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_admin_hanya_untuk_dashboard_tetap_bisa_masuk_panel(): void
    {
        $admin = $this->buatAdmin(['dashboard']);

        $this->masukSebagai($admin)->get('/admin/dashboard')->assertOk();

        $this->masukSebagai($admin)
            ->get('/admin/kasir')
            ->assertRedirect(route('admin.dashboard'));

        $this->masukSebagai($admin)
            ->post('/admin/logout')
            ->assertRedirect(route('admin.login'));
    }

    public function test_rute_turunan_sebuah_menu_turut_dibatasi_hak_akses(): void
    {
        $admin = $this->buatAdmin(['dashboard']);

        $this->masukSebagai($admin)
            ->get('/admin/mejas/qr-code/semua')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_form_admin_menerima_daftar_menu_dan_hak_akses_aktif(): void
    {
        $superAdmin = $this->buatAdmin(null, 'super_admin');
        $target = Admin::create([
            'username' => 'admin_terbatas',
            'role' => 'admin',
            'hak_akses' => ['dashboard', 'mejas'],
            'password' => 'password123',
        ]);

        $this->masukSebagai($target)
            ->get('/admin/admins')
            ->assertRedirect(route('admin.dashboard'));

        $this->masukSebagai($superAdmin)
            ->get('/admin/admins?search=admin_terbatas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Admin/Index')
                ->has('adminMenus', count(Admin::MENU_AKSES))
                ->where('adminMenus.mejas', 'Meja')
                ->where('auth.admin.hak_akses', null)
                ->has('admins.data', 1)
                ->where('admins.data.0.hak_akses', ['dashboard', 'mejas'])
            );
    }

    public function test_menyimpan_admin_menyimpan_daftar_menu_tercentang(): void
    {
        $vanue = $this->buatVanue();
        $superAdmin = $this->buatAdmin(null, 'super_admin');

        $this->masukSebagai($superAdmin)
            ->post('/admin/admins', [
                'username' => 'staff_jakarta',
                'role' => 'staff',
                'vanue_id' => $vanue->id,
                'hak_akses' => ['dashboard', 'mejas'],
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.admins.index'))
            ->assertSessionHasNoErrors();

        $admin = Admin::query()->where('username', 'staff_jakarta')->firstOrFail();

        $this->assertSame(['dashboard', 'mejas'], $admin->hak_akses);
        $this->assertFalse($admin->bolehAksesMenu('bookings'));
        $this->assertTrue($admin->bolehAksesMenu('mejas'));
        $this->assertTrue($admin->bolehAksesMenu('dashboard'));
    }

    public function test_memilih_semua_menu_disimpan_sebagai_akses_penuh(): void
    {
        $superAdmin = $this->buatAdmin(null, 'super_admin');

        $this->masukSebagai($superAdmin)
            ->post('/admin/admins', [
                'username' => 'admin_baru',
                'role' => 'super_admin',
                'hak_akses' => array_keys(Admin::MENU_AKSES),
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.admins.index'))
            ->assertSessionHasNoErrors();

        $admin = Admin::query()->where('username', 'admin_baru')->firstOrFail();

        $this->assertNull($admin->hak_akses);
        $this->assertTrue($admin->bolehAksesMenu('vouchers'));
    }

    public function test_hak_akses_dengan_menu_tidak_dikenal_ditolak(): void
    {
        $superAdmin = $this->buatAdmin(null, 'super_admin');

        $this->masukSebagai($superAdmin)
            ->post('/admin/admins', [
                'username' => 'admin_baru',
                'role' => 'super_admin',
                'hak_akses' => ['dashboard', 'menu_tidak_ada'],
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSessionHasErrors('hak_akses.1');

        $this->assertSame(1, Admin::query()->count());
    }

    public function test_update_admin_bisa_mengubah_hak_akses(): void
    {
        $superAdmin = $this->buatAdmin(null, 'super_admin');
        $vanue = $this->buatVanue();
        $target = Admin::create([
            'username' => 'admin_coba',
            'role' => 'admin',
            'vanue_id' => $vanue->id,
            'hak_akses' => ['dashboard', 'mejas'],
            'password' => 'password123',
        ]);

        $this->masukSebagai($superAdmin)
            ->put("/admin/admins/{$target->id}", [
                'username' => 'admin_coba',
                'role' => 'admin',
                'vanue_id' => $vanue->id,
                'hak_akses' => ['dashboard', 'kasir', 'bookings'],
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.admins.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(['bookings', 'dashboard', 'kasir'], $target->fresh()->hak_akses);

        $this->masukSebagai($superAdmin)
            ->put("/admin/admins/{$target->id}", [
                'username' => 'admin_coba',
                'role' => 'admin',
                'vanue_id' => $vanue->id,
                'hak_akses' => array_keys(Admin::MENU_AKSES),
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.admins.index'))
            ->assertSessionHasNoErrors();

        $this->assertNull($target->fresh()->hak_akses);
    }
}
