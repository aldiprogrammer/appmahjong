<?php

namespace Tests\Feature;

use App\Models\hargajammain;
use App\Models\jam_main;
use App\Models\Meja;
use App\Models\Pengguna;
use App\Models\Vanue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingDeferredPropsTest extends TestCase
{
    use RefreshDatabase;

    private const DATA_KEYS = ['mejas', 'jamMains', 'hargas', 'bookedSlots', 'settingBookingRooms', 'vouchers'];

    private function inertiaHeaders(): array
    {
        // Sama seperti HandleInertiaRequests::version() — hash manifest build.
        $manifest = public_path('build/manifest.json');

        return [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Inertia-Version' => file_exists($manifest) ? hash_file('xxh128', $manifest) : '',
        ];
    }

    public function test_halaman_awal_menandai_semua_prop_data_sebagai_deferred(): void
    {
        $pengguna = $this->buatPengguna();

        $response = $this
            ->withSession(['pengguna_id' => $pengguna->id])
            ->get('/booking', $this->inertiaHeaders());

        $response->assertOk();

        $page = $response->json();
        $keysDeferred = $page['deferredProps']['default'] ?? [];

        foreach (self::DATA_KEYS as $key) {
            $this->assertContains($key, $keysDeferred);
            $this->assertArrayNotHasKey($key, $page['props']);
        }
    }

    public function test_request_partial_deferred_mengembalikan_semua_prop_data(): void
    {
        $pengguna = $this->buatPengguna();

        $response = $this
            ->withSession(['pengguna_id' => $pengguna->id])
            ->get('/booking', array_merge($this->inertiaHeaders(), [
                'X-Inertia-Partial-Component' => 'User/Booking',
                'X-Inertia-Partial-Data' => implode(',', self::DATA_KEYS),
            ]));

        $response->assertOk();

        $props = $response->json('props');

        foreach (self::DATA_KEYS as $key) {
            $this->assertArrayHasKey($key, $props);
        }

        $this->assertIsArray($props['mejas']);
        $this->assertIsArray($props['vouchers']);
    }

    public function test_vanue_tunggal_langsung_mengarahkan_ke_pilihan_meja(): void
    {
        $pengguna = $this->buatPengguna();
        $vanue = Vanue::create([
            'nama' => 'Venue Tunggal',
            'alamat' => 'Jl. Contoh',
            'logo' => 'vanues/tunggal.png',
            'status' => Vanue::STATUS_AKTIF,
        ]);

        $response = $this
            ->withSession(['pengguna_id' => $pengguna->id])
            ->get('/booking', $this->inertiaHeaders());

        $response->assertOk();
        $this->assertSame((string) $vanue->id, (string) $response->json('props.vanueAwalId'));
    }

    public function test_lebih_dari_satu_vanue_tidak_memilih_vanue_otomatis(): void
    {
        $pengguna = $this->buatPengguna();
        Vanue::create(['nama' => 'Venue A', 'alamat' => 'Jl. A', 'logo' => 'vanues/a.png', 'status' => Vanue::STATUS_AKTIF]);
        Vanue::create(['nama' => 'Venue B', 'alamat' => 'Jl. B', 'logo' => 'vanues/b.png', 'status' => Vanue::STATUS_AKTIF]);

        $response = $this
            ->withSession(['pengguna_id' => $pengguna->id])
            ->get('/booking', $this->inertiaHeaders());

        $response->assertOk();
        $this->assertNull($response->json('props.vanueAwalId'));
    }

    private function buatPengguna(): Pengguna
    {
        $pengguna = Pengguna::create([
            'kode_user' => 'PG-DEFT01',
            'nama_depan' => 'Defi',
            'nama_balakang' => 'Tester',
            'email' => 'defi@example.com',
            'nowa' => '081298765432',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        Meja::create(['kode_meja' => 'MJ-D1', 'nama_meja' => 'Meja Deferred 1']);
        jam_main::create(['jam_mulai' => '09:00', 'jam_selesai' => '10:00', 'status' => 'aktif']);

        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][Carbon::today()->dayOfWeek];

        hargajammain::create([
            'jam_main_id' => (string) (jam_main::query()->first()->id),
            'hari' => $hari,
            'harga' => '50000',
            'diskon' => '0',
            'harga_akhir' => '50000',
        ]);

        return $pengguna;
    }
}
