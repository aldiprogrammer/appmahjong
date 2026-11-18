<?php

namespace Tests\Feature;

use App\Models\Aturanpointvenue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAturanPointTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withSession(['admin_id' => 1]);
    }

    private function buatAturanPoint(array $data = []): Aturanpointvenue
    {
        return Aturanpointvenue::create([
            'nama_aturan' => 'Bonus Faan Dasar',
            'slug' => 'bonus-faan-dasar',
            'deskripsi' => 'Aturan bonus faan dasar permainan.',
            'min_faan' => '0',
            'max_faan' => '3',
            'status' => 'aktif',
            ...$data,
        ]);
    }

    public function test_tamu_diarahkan_ke_halaman_login_admin(): void
    {
        $this->get('/admin/aturan-points')->assertRedirect(route('admin.login'));
    }

    public function test_admin_bisa_melihat_daftar_aturan_point(): void
    {
        $this->buatAturanPoint();

        $this->admin()
            ->get('/admin/aturan-points')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AturanPoint/Index')
                ->has('aturanPoints.data', 1)
                ->where('aturanPoints.data.0.nama_aturan', 'Bonus Faan Dasar')
            );
    }

    public function test_admin_bisa_menambahkan_aturan_point_dengan_slug_otomatis(): void
    {
        $this->admin()
            ->post('/admin/aturan-points', [
                'nama_aturan' => 'Bonus Faan Dasar',
                'slug' => '',
                'deskripsi' => 'Aturan bonus faan dasar permainan.',
                'min_faan' => 0,
                'max_faan' => 3,
                'status' => 'aktif',
            ])
            ->assertRedirect(route('admin.aturan-points.index'))
            ->assertSessionHas('success');

        $aturanPoint = Aturanpointvenue::query()->firstOrFail();

        $this->assertSame('Bonus Faan Dasar', $aturanPoint->nama_aturan);
        $this->assertSame('bonus-faan-dasar', $aturanPoint->slug);
        $this->assertSame('0', $aturanPoint->min_faan);
        $this->assertSame('3', $aturanPoint->max_faan);
        $this->assertSame('aktif', $aturanPoint->status);
    }

    public function test_slug_aturan_point_dibuat_unik(): void
    {
        $this->buatAturanPoint();

        $this->admin()
            ->post('/admin/aturan-points', [
                'nama_aturan' => 'Bonus Faan Dasar',
                'slug' => '',
                'deskripsi' => 'Aturan bonus faan dasar kedua.',
                'min_faan' => 1,
                'max_faan' => 4,
                'status' => 'aktif',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Aturanpointvenue::query()->where('slug', 'bonus-faan-dasar-2')->count());
    }

    public function test_nama_deskripsi_faan_dan_status_wajib_diisi(): void
    {
        $this->admin()
            ->post('/admin/aturan-points', [
                'nama_aturan' => '',
                'deskripsi' => '',
                'min_faan' => '',
                'max_faan' => '',
                'status' => '',
            ])
            ->assertSessionHasErrors(['nama_aturan', 'deskripsi', 'min_faan', 'max_faan', 'status']);

        $this->assertSame(0, Aturanpointvenue::query()->count());
    }

    public function test_maksimal_faan_tidak_boleh_lebih_kecil_dari_minimal_faan(): void
    {
        $this->admin()
            ->post('/admin/aturan-points', [
                'nama_aturan' => 'Bonus Faan Dasar',
                'deskripsi' => 'Aturan bonus faan dasar permainan.',
                'min_faan' => 5,
                'max_faan' => 2,
                'status' => 'aktif',
            ])
            ->assertSessionHasErrors('max_faan');

        $this->assertSame(0, Aturanpointvenue::query()->count());
    }

    public function test_admin_bisa_memperbarui_aturan_point(): void
    {
        $aturanPoint = $this->buatAturanPoint();

        $this->admin()
            ->put("/admin/aturan-points/{$aturanPoint->id}", [
                'nama_aturan' => 'Bonus Faan Lanjutan',
                'slug' => '',
                'deskripsi' => 'Aturan bonus faan lanjutan.',
                'min_faan' => 4,
                'max_faan' => 13,
                'status' => 'nonaktif',
            ])
            ->assertRedirect(route('admin.aturan-points.index'))
            ->assertSessionHasNoErrors();

        $aturanPoint->refresh();

        $this->assertSame('Bonus Faan Lanjutan', $aturanPoint->nama_aturan);
        $this->assertSame('bonus-faan-lanjutan', $aturanPoint->slug);
        $this->assertSame('13', $aturanPoint->max_faan);
        $this->assertSame('nonaktif', $aturanPoint->status);
    }

    public function test_admin_bisa_menghapus_aturan_point(): void
    {
        $aturanPoint = $this->buatAturanPoint();

        $this->admin()
            ->delete("/admin/aturan-points/{$aturanPoint->id}")
            ->assertRedirect(route('admin.aturan-points.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('aturanpointvenues', ['id' => $aturanPoint->id]);
    }

    public function test_pencarian_aturan_point_berdasarkan_nama_deskripsi_dan_status(): void
    {
        $this->buatAturanPoint();
        $this->buatAturanPoint([
            'nama_aturan' => 'Aturan Khusus',
            'slug' => 'aturan-khusus',
            'deskripsi' => 'Aturan khusus turnamen.',
            'status' => 'nonaktif',
        ]);

        $this->admin()
            ->get('/admin/aturan-points?search=Khusus')
            ->assertInertia(fn (Assert $page) => $page
                ->has('aturanPoints.data', 1)
                ->where('aturanPoints.data.0.nama_aturan', 'Aturan Khusus')
            );

        $this->admin()
            ->get('/admin/aturan-points?search=turnamen')
            ->assertInertia(fn (Assert $page) => $page
                ->has('aturanPoints.data', 1)
                ->where('aturanPoints.data.0.nama_aturan', 'Aturan Khusus')
            );

        $this->admin()
            ->get('/admin/aturan-points?search=nonaktif')
            ->assertInertia(fn (Assert $page) => $page
                ->has('aturanPoints.data', 1)
                ->where('aturanPoints.data.0.nama_aturan', 'Aturan Khusus')
            );
    }
}
