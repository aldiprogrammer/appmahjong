<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_callback_creates_new_pengguna(): void
    {
        $this->fakeGoogleUser('google-123', 'Budi Santoso', 'budi@example.com');

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('home'));

        $pengguna = Pengguna::query()->where('email', 'budi@example.com')->firstOrFail();

        $this->assertSame('Budi', $pengguna->nama_depan);
        $this->assertSame('Santoso', $pengguna->nama_balakang);
        $this->assertSame('google-123', $pengguna->google_id);
        $this->assertSame('https://example.com/avatar.png', $pengguna->avatar);
        $this->assertSame('aktif', $pengguna->status);
        $this->assertNull($pengguna->password);
        $this->assertSame($pengguna->id, session('pengguna_id'));
    }

    public function test_google_callback_links_existing_pengguna_by_email(): void
    {
        $pengguna = Pengguna::create([
            'kode_user' => 'PG-TEST01',
            'nama_depan' => 'Budi',
            'nama_balakang' => 'Santoso',
            'email' => 'budi@example.com',
            'nowa' => '08123456789',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $this->fakeGoogleUser('google-123', 'Budi Santoso', 'budi@example.com');

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('home'));
        $this->assertSame('google-123', $pengguna->fresh()->google_id);
        $this->assertSame($pengguna->id, session('pengguna_id'));
    }

    public function test_google_callback_rejects_inactive_pengguna(): void
    {
        Pengguna::create([
            'kode_user' => 'PG-TEST02',
            'nama_depan' => 'Budi',
            'nama_balakang' => 'Santoso',
            'email' => 'budi@example.com',
            'nowa' => '08123456789',
            'password' => bcrypt('password'),
            'status' => 'nonaktif',
        ]);

        $this->fakeGoogleUser('google-123', 'Budi Santoso', 'budi@example.com');

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertNull(session('pengguna_id'));
    }

    private function fakeGoogleUser(string $id, string $name, string $email): void
    {
        $socialiteUser = (new SocialiteUser)->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'avatar' => 'https://example.com/avatar.png',
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
    }
}
