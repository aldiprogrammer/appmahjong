<?php

namespace Tests\Feature;

use App\Models\Vanue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PesanVenueTest extends TestCase
{
    use RefreshDatabase;

    public function test_vanue_tunggal_langsung_menampilkan_menu_venue(): void
    {
        $vanue = Vanue::create([
            'nama' => 'Venue Tunggal',
            'alamat' => 'Jl. Contoh',
            'logo' => 'vanues/tunggal.png',
            'status' => Vanue::STATUS_AKTIF,
        ]);

        $this->get('/pesan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Pesan')
                ->where('vanueAwalId', (string) $vanue->id));
    }

    public function test_lebih_dari_satu_vanue_tetap_menampilkan_pilihan_venue(): void
    {
        Vanue::create(['nama' => 'Venue A', 'alamat' => 'Jl. A', 'logo' => 'vanues/a.png', 'status' => Vanue::STATUS_AKTIF]);
        Vanue::create(['nama' => 'Venue B', 'alamat' => 'Jl. B', 'logo' => 'vanues/b.png', 'status' => Vanue::STATUS_AKTIF]);

        $this->get('/pesan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Pesan')
                ->where('vanueAwalId', null));
    }
}
