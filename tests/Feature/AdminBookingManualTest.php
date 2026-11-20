<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\hargajammain;
use App\Models\jam_main;
use App\Models\Meja;
use App\Models\paket_hargajammain;
use App\Models\Pengguna;
use App\Models\Vanue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AdminBookingManualTest extends TestCase
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

    private function superAdmin(): Admin
    {
        return Admin::query()->firstOrCreate(
            ['username' => 'super'],
            ['role' => 'super_admin', 'password' => 'password123'],
        );
    }

    private function adminVenue(Vanue $vanue, string $username): Admin
    {
        return Admin::create([
            'username' => $username,
            'role' => 'admin',
            'vanue_id' => $vanue->id,
            'password' => 'password123',
        ]);
    }

    private function masukSebagai(Admin $admin): static
    {
        return $this->withSession(['admin_id' => $admin->id]);
    }

    /**
     * @return array<int, jam_main>
     */
    private function buatSlotBerturut(int $jumlah, string $mulaiAwal = '10:00'): array
    {
        $slots = [];
        [$jam, $menit] = array_map('intval', explode(':', $mulaiAwal));

        for ($urutan = 0; $urutan < $jumlah; $urutan++) {
            $mulai = sprintf('%02d:%02d', $jam + $urutan, $menit);
            $selesai = sprintf('%02d:%02d', $jam + $urutan + 1, $menit);
            $slots[] = jam_main::create([
                'jam_mulai' => $mulai,
                'jam_selesai' => $selesai,
                'status' => 'aktif',
            ]);
        }

        return $slots;
    }

    private function hariDari(Carbon $tanggal): string
    {
        return ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$tanggal->dayOfWeek];
    }

    private function buatHarga(jam_main $jam, Vanue $vanue, Carbon $tanggal, string $harga = '50000'): hargajammain
    {
        return hargajammain::create([
            'jam_main_id' => (string) $jam->id,
            'hari' => $this->hariDari($tanggal),
            'harga' => $harga,
            'diskon' => '0',
            'harga_akhir' => $harga,
            'vanue_id' => $vanue->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function submitBookingManual(array $extra, ?Admin $admin = null): TestResponse
    {
        return $this->masukSebagai($admin ?? $this->superAdmin())
            ->post('/admin/bookings', array_merge([
                'diskon_tipe' => 'persen',
                'diskon_nilai' => 0,
            ], $extra));
    }

    public function test_admin_bisa_input_booking_manual_dengan_sistem_pembayaran_tunai(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(2, '10:00');

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal);
        }

        $meja = Meja::create(['kode_meja' => 'MJ-01', 'nama_meja' => 'Meja 1', 'vanue_id' => $vanue->id]);

        $this->submitBookingManual([
            'tgl_main' => $tanggal->toDateString(),
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => array_map(fn (jam_main $jam): string => (string) $jam->id, $slots),
            'nama_pengguna' => 'Budi Santoso',
            'no_wa' => '081234567890',
            'sistem_pembayaran' => 'tunai',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $bookings = Booking::query()->orderBy('jam_mulai')->get();

        $this->assertCount(2, $bookings);
        $this->assertSame(['200', '200'], $bookings->pluck('status_booking')->all());
        $this->assertSame(['lunas', 'lunas'], $bookings->pluck('status_pembayaran')->all());
        $this->assertSame(['tunai', 'tunai'], $bookings->pluck('sistem_pembayaran')->all());
        $this->assertSame(['50000', '50000'], $bookings->pluck('total_harga')->all());
        $this->assertNotNull($bookings->first()->dibayar_pada);
        $this->assertSame(1, $bookings->pluck('kode_pembayaran')->unique()->count());

        // Identitas tamu disimpan di booking saja, tanpa akun pengguna baru.
        $this->assertSame(0, Pengguna::query()->count());
        $this->assertNull($bookings->first()->id_pengguna);
        $this->assertSame(['Budi Santoso', 'Budi Santoso'], $bookings->pluck('nama_pengguna')->all());
        $this->assertSame(['081234567890', '081234567890'], $bookings->pluck('no_wa')->all());
    }

    public function test_no_wa_opsional_boleh_dikosongkan(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Bandung');
        $tanggal = Carbon::tomorrow();
        $slot = $this->buatSlotBerturut(1, '09:00')[0];
        $this->buatHarga($slot, $vanue, $tanggal);
        $meja = Meja::create(['kode_meja' => 'MJ-WA', 'nama_meja' => 'Meja WA', 'vanue_id' => $vanue->id]);

        $this->submitBookingManual([
            'tgl_main' => $tanggal->toDateString(),
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $slot->id],
            'nama_pengguna' => 'Tamu Tanpa WA',
            'no_wa' => '',
            'sistem_pembayaran' => 'tunai',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $booking = Booking::query()->firstOrFail();

        $this->assertSame('Tamu Tanpa WA', $booking->nama_pengguna);
        $this->assertNull($booking->no_wa);
        $this->assertNull($booking->id_pengguna);
    }

    public function test_transfer_dan_qris_belum_bayar_sampai_verifikasi(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Surabaya');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(2, '13:00');

        foreach ($slots as $slot) {
            $this->buatHarga($slot, $vanue, $tanggal);
        }

        $meja = Meja::create(['kode_meja' => 'MJ-02', 'nama_meja' => 'Meja 2', 'vanue_id' => $vanue->id]);

        foreach (['transfer', 'qris'] as $urutan => $sistem) {
            $this->submitBookingManual([
                'tgl_main' => $tanggal->toDateString(),
                'meja_id' => (string) $meja->id,
                'jam_main_ids' => [(string) $slots[$urutan]->id],
                'nama_pengguna' => 'Pembeli '.$sistem,
                'sistem_pembayaran' => $sistem,
            ])->assertRedirect()->assertSessionHasNoErrors();

            $this->travel($urutan + 1)->minutes();
        }

        $bookings = Booking::query()->orderBy('id')->get();

        $this->assertCount(2, $bookings);
        $this->assertSame(['belum_bayar', 'belum_bayar'], $bookings->pluck('status_pembayaran')->all());
        $this->assertNull($bookings->first()->dibayar_pada);
        $this->assertSame('200', $bookings->first()->status_booking);
    }

    public function test_diskon_manual_persen_diterapkan_meski_ada_paket(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Bandung');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(3, '08:00');

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal, '40000');
        }

        // Paket 10% tetap ada, tapi booking manual tidak mengikutinya.
        paket_hargajammain::create([
            'vanue_id' => $vanue->id,
            'hari' => $this->hariDari($tanggal),
            'minimal_jam' => 3,
            'jam_mulai' => '08:00',
            'jam_selesai' => '17:00',
            'diskon_persen' => '10',
        ]);

        $meja = Meja::create(['kode_meja' => 'MJ-03', 'nama_meja' => 'Meja 3', 'vanue_id' => $vanue->id]);

        $this->submitBookingManual([
            'tgl_main' => $tanggal->toDateString(),
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => array_map(fn (jam_main $jam): string => (string) $jam->id, $slots),
            'nama_pengguna' => 'Diskon Hunter',
            'sistem_pembayaran' => 'qris',
            'diskon_tipe' => 'persen',
            'diskon_nilai' => 25,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $bookings = Booking::query()->orderBy('jam_mulai')->get();

        // 25% dari total 120.000 = 30.000, dibagi rata 10.000 per sesi.
        $this->assertSame(['30000', '30000', '30000'], $bookings->pluck('total_harga')->all());
        $this->assertSame(['10000', '10000', '10000'], $bookings->pluck('diskon_paket')->all());
        $this->assertSame(
            [25.0, 25.0, 25.0],
            $bookings->pluck('diskon_paket_persen')->map(fn ($persen): float => (float) $persen)->all(),
        );
        $this->assertSame('belum_bayar', $bookings->first()->status_pembayaran);
    }

    public function test_diskon_manual_nominal_diterapkan(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Yogyakarta');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(2, '14:00');

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal, '50000');
        }

        $meja = Meja::create(['kode_meja' => 'MJ-NOM', 'nama_meja' => 'Meja Nominal', 'vanue_id' => $vanue->id]);

        $this->submitBookingManual([
            'tgl_main' => $tanggal->toDateString(),
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => array_map(fn (jam_main $jam): string => (string) $jam->id, $slots),
            'nama_pengguna' => 'Tamu Nominal',
            'sistem_pembayaran' => 'tunai',
            'diskon_tipe' => 'nominal',
            'diskon_nilai' => 30000,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $bookings = Booking::query()->orderBy('jam_mulai')->get();

        // Total dasar 100.000 dikurangi nominal 30.000 (15.000 per sesi).
        $this->assertSame(['35000', '35000'], $bookings->pluck('total_harga')->all());
        $this->assertSame(['15000', '15000'], $bookings->pluck('diskon_paket')->all());
        $this->assertSame([null, null], $bookings->pluck('diskon_paket_persen')->all());
    }

    public function test_diskon_melebihi_total_harga_ditolak(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Semarang');
        $tanggal = Carbon::tomorrow();
        $slot = $this->buatSlotBerturut(1, '16:00')[0];
        $this->buatHarga($slot, $vanue, $tanggal, '50000');
        $meja = Meja::create(['kode_meja' => 'MJ-OVR', 'nama_meja' => 'Meja Override', 'vanue_id' => $vanue->id]);

        $this->submitBookingManual([
            'tgl_main' => $tanggal->toDateString(),
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $slot->id],
            'nama_pengguna' => 'Diskon Berlebih',
            'sistem_pembayaran' => 'tunai',
            'diskon_tipe' => 'nominal',
            'diskon_nilai' => 999999,
        ])->assertSessionHasErrors(['diskon_nilai']);

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_diskon_persen_lewat_100_ditolak(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Malang');
        $meja = Meja::create(['kode_meja' => 'MJ-PCT', 'nama_meja' => 'Meja Persen', 'vanue_id' => $vanue->id]);

        $this->submitBookingManual([
            'tgl_main' => Carbon::tomorrow()->toDateString(),
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => ['1'],
            'nama_pengguna' => 'Persen Berlebih',
            'sistem_pembayaran' => 'tunai',
            'diskon_tipe' => 'persen',
            'diskon_nilai' => 150,
        ])->assertSessionHasErrors(['diskon_nilai']);

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_slot_sudah_terbooking_ditolak(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Medan');
        $tanggal = Carbon::tomorrow();
        $slot = $this->buatSlotBerturut(1, '19:00')[0];
        $this->buatHarga($slot, $vanue, $tanggal);
        $meja = Meja::create(['kode_meja' => 'MJ-04', 'nama_meja' => 'Meja 4', 'vanue_id' => $vanue->id]);

        $payload = [
            'tgl_main' => $tanggal->toDateString(),
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $slot->id],
            'nama_pengguna' => 'Pemburu Slot',
            'sistem_pembayaran' => 'tunai',
        ];

        $this->submitBookingManual($payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->submitBookingManual($payload)->assertSessionHasErrors(['jam_main_ids']);

        $this->assertSame(1, Booking::query()->count());
    }

    public function test_validasi_wajib_dan_sistem_pembayaran_harus_valid(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Makassar');
        $meja = Meja::create(['kode_meja' => 'MJ-05', 'nama_meja' => 'Meja 5', 'vanue_id' => $vanue->id]);

        $this->submitBookingManual([
            'tgl_main' => Carbon::yesterday()->toDateString(),
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [],
            'nama_pengguna' => '',
            'sistem_pembayaran' => 'crypto',
            'diskon_tipe' => 'barter',
            'diskon_nilai' => '',
        ])->assertSessionHasErrors([
            'tgl_main',
            'jam_main_ids',
            'nama_pengguna',
            'sistem_pembayaran',
            'diskon_tipe',
            'diskon_nilai',
        ]);

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_admin_venue_hanya_bisa_input_booking_di_venue_sendiri(): void
    {
        $vanueJakarta = $this->buatVanue('Mahjong Center Jakarta');
        $vanueSurabaya = $this->buatVanue('Mahjong Center Surabaya');
        $tanggal = Carbon::tomorrow();
        $slot = $this->buatSlotBerturut(1, '15:00')[0];
        $this->buatHarga($slot, $vanueSurabaya, $tanggal);
        $meja = Meja::create(['kode_meja' => 'MJ-SBY', 'nama_meja' => 'Meja Surabaya', 'vanue_id' => $vanueSurabaya->id]);

        $adminJakarta = $this->adminVenue($vanueJakarta, 'admin_jakarta');

        $this->submitBookingManual([
            'tgl_main' => $tanggal->toDateString(),
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $slot->id],
            'nama_pengguna' => 'Tamu Luar',
            'sistem_pembayaran' => 'tunai',
        ], $adminJakarta)->assertForbidden();

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_admin_venue_bisa_input_booking_di_venue_sendiri(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $slot = $this->buatSlotBerturut(1, '21:00')[0];
        $this->buatHarga($slot, $vanue, $tanggal);
        $meja = Meja::create(['kode_meja' => 'MJ-JKT', 'nama_meja' => 'Meja Jakarta', 'vanue_id' => $vanue->id]);

        $admin = $this->adminVenue($vanue, 'admin_venue_input');

        $this->submitBookingManual([
            'tgl_main' => $tanggal->toDateString(),
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $slot->id],
            'nama_pengguna' => 'Tamu Venue',
            'sistem_pembayaran' => 'tunai',
        ], $admin)->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(1, Booking::query()->count());
    }

    public function test_booking_manual_tidak_membuat_atau_mengaitkan_pengguna(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $slot = $this->buatSlotBerturut(1, '11:00')[0];
        $this->buatHarga($slot, $vanue, $tanggal);
        $meja = Meja::create(['kode_meja' => 'MJ-06', 'nama_meja' => 'Meja 6', 'vanue_id' => $vanue->id]);

        // Walaupun ada pengguna dengan nama sama, booking manual tetap lepas
        // dari akun pengguna.
        Pengguna::create([
            'kode_user' => 'PG-EXIST',
            'nama_depan' => 'Aldi',
            'nama_balakang' => 'Tester',
            'email' => 'aldi@example.com',
            'nowa' => '081234567890',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $this->submitBookingManual([
            'tgl_main' => $tanggal->toDateString(),
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $slot->id],
            'nama_pengguna' => 'Aldi Tester',
            'sistem_pembayaran' => 'tunai',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(1, Pengguna::query()->count());
        $this->assertNull(Booking::query()->firstOrFail()->id_pengguna);
        $this->assertSame('Aldi Tester', Booking::query()->firstOrFail()->nama_pengguna);
    }

    public function test_halaman_admin_booking_mengirim_prop_form(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $this->buatSlotBerturut(1, '10:00');
        Meja::create(['kode_meja' => 'MJ-07', 'nama_meja' => 'Meja 7', 'vanue_id' => $vanue->id]);

        $manifest = public_path('build/manifest.json');
        $headers = [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Inertia-Version' => file_exists($manifest) ? hash_file('xxh128', $manifest) : '',
        ];

        // Prop data form memakai Inertia::defer, jadi ditandai deferred pada muat awal...
        $response = $this->masukSebagai($this->superAdmin())
            ->get('/admin/bookings', $headers);

        $response->assertOk();

        $page = $response->json();
        $keysDeferred = $page['deferredProps']['default'] ?? [];

        foreach (['mejas', 'jamMains', 'hargas', 'bookedSlots'] as $key) {
            $this->assertContains($key, $keysDeferred);
            $this->assertArrayNotHasKey($key, $page['props']);
        }

        // ...dan terkirim penuh lewat partial reload.
        $partial = $this->masukSebagai($this->superAdmin())
            ->get('/admin/bookings', array_merge($headers, [
                'X-Inertia-Partial-Component' => 'Admin/Booking/Index',
                'X-Inertia-Partial-Data' => 'mejas,jamMains,hargas,bookedSlots',
            ]));

        $partial->assertOk();

        $props = $partial->json('props');

        $this->assertSame('Admin/Booking/Index', $partial->json('component'));
        $this->assertCount(1, $props['mejas']);
        $this->assertCount(1, $props['jamMains']);
        $this->assertCount(0, $props['hargas']);
        $this->assertCount(0, $props['bookedSlots']);
        $this->assertArrayNotHasKey('paketHargas', $props);
    }
}
