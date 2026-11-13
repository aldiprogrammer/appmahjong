<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Vanue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminVanueTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withSession(['admin_id' => 1]);
    }

    public function test_tamu_diarahkan_ke_halaman_login_admin(): void
    {
        $this->get('/admin/vanues')->assertRedirect(route('admin.login'));
    }

    public function test_admin_bisa_melihat_daftar_venue(): void
    {
        Vanue::create([
            'nama' => 'Mahjong Center Jakarta',
            'alamat' => 'Jl. Sudirman No. 10',
            'logo' => 'vanues/logo.png',
        ]);

        $this->admin()
            ->get('/admin/vanues')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Vanue/Index')
                ->has('vanues.data', 1)
                ->where('vanues.data.0.nama', 'Mahjong Center Jakarta')
            );
    }

    public function test_admin_bisa_menambahkan_venue(): void
    {
        Storage::fake('public');

        $this->admin()
            ->post('/admin/vanues', [
                'nama' => 'Mahjong Center Jakarta',
                'alamat' => 'Jl. Sudirman No. 10',
                'logo' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertRedirect(route('admin.vanues.index'))
            ->assertSessionHas('success');

        $vanue = Vanue::query()->firstOrFail();

        $this->assertSame('Mahjong Center Jakarta', $vanue->nama);
        $this->assertSame('Jl. Sudirman No. 10', $vanue->alamat);
        Storage::disk('public')->assertExists($vanue->logo);
    }

    public function test_nama_alamat_dan_logo_venue_wajib_diisi(): void
    {
        $this->admin()
            ->post('/admin/vanues', [
                'nama' => '',
                'alamat' => '',
            ])
            ->assertSessionHasErrors(['nama', 'alamat', 'logo']);

        $this->assertSame(0, Vanue::query()->count());
    }

    public function test_logo_venue_harus_berupa_file_gambar_yang_valid(): void
    {
        Storage::fake('public');

        $this->admin()
            ->post('/admin/vanues', [
                'nama' => 'Mahjong Center Jakarta',
                'alamat' => 'Jl. Sudirman No. 10',
                'logo' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('logo');

        $this->assertSame(0, Vanue::query()->count());
    }

    public function test_admin_bisa_memperbarui_venue_via_method_spoofing_dengan_logo_baru(): void
    {
        Storage::fake('public');

        $vanue = Vanue::create([
            'nama' => 'Mahjong Center Jakarta',
            'alamat' => 'Jl. Sudirman No. 10',
            'logo' => UploadedFile::fake()->image('lama.png')->store('vanues', 'public'),
        ]);

        $logoLama = $vanue->logo;

        $this->admin()
            ->post("/admin/vanues/{$vanue->id}", [
                '_method' => 'put',
                'nama' => 'Mahjong Center Surabaya',
                'alamat' => 'Jl. Tunjungan No. 1',
                'logo' => UploadedFile::fake()->image('baru.png'),
            ])
            ->assertRedirect(route('admin.vanues.index'))
            ->assertSessionHasNoErrors();

        $vanue->refresh();
        $logoBaru = $vanue->logo;

        $this->assertSame('Mahjong Center Surabaya', $vanue->nama);
        $this->assertSame('Jl. Tunjungan No. 1', $vanue->alamat);
        $this->assertNotSame($logoLama, $logoBaru);
        Storage::disk('public')->assertMissing($logoLama);
        Storage::disk('public')->assertExists($logoBaru);
    }

    public function test_admin_bisa_memperbarui_venue_tanpa_mengganti_logo(): void
    {
        Storage::fake('public');

        $vanue = Vanue::create([
            'nama' => 'Mahjong Center Jakarta',
            'alamat' => 'Jl. Sudirman No. 10',
            'logo' => UploadedFile::fake()->image('logo.png')->store('vanues', 'public'),
        ]);

        $logoLama = $vanue->logo;

        $this->admin()
            ->put("/admin/vanues/{$vanue->id}", [
                'nama' => 'Mahjong Center Bandung',
                'alamat' => 'Jl. Asia Afrika No. 5',
            ])
            ->assertRedirect(route('admin.vanues.index'))
            ->assertSessionHasNoErrors();

        $vanue->refresh();

        $this->assertSame('Mahjong Center Bandung', $vanue->nama);
        $this->assertSame('Jl. Asia Afrika No. 5', $vanue->alamat);
        $this->assertSame($logoLama, $vanue->logo);
        Storage::disk('public')->assertExists($logoLama);
    }

    public function test_admin_bisa_memperbarui_venue_lewat_method_spoofing(): void
    {
        Storage::fake('public');

        $vanue = Vanue::create([
            'nama' => 'Mahjong Center Jakarta',
            'alamat' => 'Jl. Sudirman No. 10',
            'logo' => UploadedFile::fake()->image('logo.png')->store('vanues', 'public'),
        ]);

        $logoLama = $vanue->logo;

        $this->admin()
            ->post("/admin/vanues/{$vanue->id}", [
                '_method' => 'put',
                'nama' => 'Mahjong Center Medan',
                'alamat' => 'Jl. Gatot Subroto No. 1',
                'logo' => UploadedFile::fake()->image('logo-baru.png'),
            ])
            ->assertRedirect(route('admin.vanues.index'))
            ->assertSessionHasNoErrors();

        $vanue->refresh();

        $this->assertSame('Mahjong Center Medan', $vanue->nama);
        $this->assertSame('Jl. Gatot Subroto No. 1', $vanue->alamat);
        $this->assertNotSame($logoLama, $vanue->logo);
        Storage::disk('public')->assertMissing($logoLama);
        Storage::disk('public')->assertExists($vanue->logo);
    }

    public function test_mengganti_logo_venue_menghapus_logo_lama(): void
    {
        Storage::fake('public');

        $vanue = Vanue::create([
            'nama' => 'Mahjong Center Jakarta',
            'alamat' => 'Jl. Sudirman No. 10',
            'logo' => UploadedFile::fake()->image('lama.png')->store('vanues', 'public'),
        ]);

        $logoLama = $vanue->logo;

        $this->admin()
            ->put("/admin/vanues/{$vanue->id}", [
                'nama' => 'Mahjong Center Jakarta',
                'alamat' => 'Jl. Sudirman No. 10',
                'logo' => UploadedFile::fake()->image('baru.png'),
            ])
            ->assertRedirect(route('admin.vanues.index'));

        $logoBaru = $vanue->fresh()->logo;

        $this->assertNotSame($logoLama, $logoBaru);
        Storage::disk('public')->assertMissing($logoLama);
        Storage::disk('public')->assertExists($logoBaru);
    }

    public function test_admin_bisa_menghapus_venue_beserta_logonya(): void
    {
        Storage::fake('public');

        $vanue = Vanue::create([
            'nama' => 'Mahjong Center Jakarta',
            'alamat' => 'Jl. Sudirman No. 10',
            'logo' => UploadedFile::fake()->image('logo.png')->store('vanues', 'public'),
        ]);

        $logo = $vanue->logo;

        $this->admin()
            ->delete("/admin/vanues/{$vanue->id}")
            ->assertRedirect(route('admin.vanues.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('vanues', ['id' => $vanue->id]);
        Storage::disk('public')->assertMissing($logo);
    }

    public function test_pencarian_venue_berdasarkan_nama_dan_alamat(): void
    {
        Vanue::create([
            'nama' => 'Mahjong Center Jakarta',
            'alamat' => 'Jl. Sudirman No. 10',
            'logo' => 'vanues/jakarta.png',
        ]);
        Vanue::create([
            'nama' => 'Mahjong Center Bandung',
            'alamat' => 'Jl. Asia Afrika No. 5',
            'logo' => 'vanues/bandung.png',
        ]);

        $this->admin()
            ->get('/admin/vanues?search=Jakarta')
            ->assertInertia(fn (Assert $page) => $page
                ->has('vanues.data', 1)
                ->where('vanues.data.0.nama', 'Mahjong Center Jakarta')
            );

        $this->admin()
            ->get('/admin/vanues?search=Asia Afrika')
            ->assertInertia(fn (Assert $page) => $page
                ->has('vanues.data', 1)
                ->where('vanues.data.0.nama', 'Mahjong Center Bandung')
            );
    }

    private function buatVanue(string $nama): Vanue
    {
        return Vanue::create([
            'nama' => $nama,
            'alamat' => 'Jl. Contoh No. 1',
            'logo' => 'vanues/'.strtolower(str_replace(' ', '-', $nama)).'.png',
        ]);
    }

    private function adminVenue(Vanue $vanue, string $username): static
    {
        $admin = Admin::create([
            'username' => $username,
            'role' => 'admin',
            'vanue_id' => $vanue->id,
            'password' => 'password123',
        ]);

        return $this->withSession(['admin_id' => $admin->id]);
    }

    public function test_admin_venue_hanya_melihat_venue_miliknya_sendiri(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $this->buatVanue('Mahjong Center Surabaya');

        $this->adminVenue($jakarta, 'admin_jakarta')
            ->get('/admin/vanues')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Vanue/Index')
                ->has('vanues.data', 1)
                ->where('vanues.data.0.nama', 'Mahjong Center Jakarta')
            );
    }

    public function test_admin_venue_tidak_bisa_menambah_venue(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');

        $this->adminVenue($jakarta, 'admin_jakarta')
            ->post('/admin/vanues', [
                'nama' => 'Mahjong Center Baru',
                'alamat' => 'Jl. Baru No. 1',
            ])
            ->assertForbidden();

        $this->assertSame(1, Vanue::query()->count());
    }

    public function test_admin_venue_tidak_bisa_mengubah_venue_lain(): void
    {
        Storage::fake('public');

        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');

        $this->adminVenue($jakarta, 'admin_jakarta')
            ->put("/admin/vanues/{$surabaya->id}", [
                'nama' => 'Mahjong Center Dubah',
                'alamat' => 'Jl. Baru No. 9',
            ])
            ->assertForbidden();

        $this->assertSame('Mahjong Center Surabaya', $surabaya->fresh()->nama);
    }

    public function test_admin_venue_bisa_memperbarui_venue_miliknya(): void
    {
        Storage::fake('public');

        $jakarta = $this->buatVanue('Mahjong Center Jakarta');

        $this->adminVenue($jakarta, 'admin_jakarta')
            ->put("/admin/vanues/{$jakarta->id}", [
                'nama' => 'Mahjong Center Jakarta Pusat',
                'alamat' => 'Jl. Sudirman No. 10',
            ])
            ->assertRedirect(route('admin.vanues.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Mahjong Center Jakarta Pusat', $jakarta->fresh()->nama);
    }

    public function test_admin_venue_tidak_bisa_menghapus_venue(): void
    {
        Storage::fake('public');

        $jakarta = $this->buatVanue('Mahjong Center Jakarta');

        $this->adminVenue($jakarta, 'admin_jakarta')
            ->delete("/admin/vanues/{$jakarta->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('vanues', ['id' => $jakarta->id]);
    }
}
