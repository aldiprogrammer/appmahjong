<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\hargajammain;
use App\Models\jam_main;
use App\Models\Meja;
use App\Models\paket_hargajammain;
use App\Models\Pengguna;
use App\Models\Statusvoucher;
use App\Models\Vanue;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaketHargaJamMainTest extends TestCase
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

    private function buatPengguna(string $kode): Pengguna
    {
        return Pengguna::create([
            'kode_user' => $kode,
            'nama_depan' => 'Aldi',
            'nama_balakang' => 'Tester',
            'email' => strtolower($kode).'@example.com',
            'nowa' => '081234567890',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);
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

    private function buatPaket(Vanue $vanue, Carbon $tanggal, int $minimalJam, string $diskon, string $jamMulai = '00:00', string $jamSelesai = '00:00', ?int $maksimalJam = null, ?string $hargaPaket = null): paket_hargajammain
    {
        return paket_hargajammain::create([
            'vanue_id' => $vanue->id,
            'hari' => $this->hariDari($tanggal),
            'minimal_jam' => $minimalJam,
            'maksimal_jam' => $maksimalJam,
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $jamSelesai,
            'diskon_persen' => $diskon === '' ? null : $diskon,
            'harga_paket' => $hargaPaket,
        ]);
    }

    /**
     * @param  array<int, jam_main>  $slots
     * @param  array<string, mixed>  $extra
     */
    private function booking(Meja $meja, array $slots, Carbon $tanggal, Pengguna $pengguna, array $extra = []): void
    {
        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/booking', [
                'meja_id' => (string) $meja->id,
                'jam_main_ids' => array_map(fn (jam_main $jam): string => (string) $jam->id, $slots),
                'tgl_main' => $tanggal->toDateString(),
            ] + $extra)
            ->assertSessionHasNoErrors();

        $kodePembayaran = Str::afterLast((string) $response->headers->get('Location'), '/');
        $pakaiVoucher = filled($extra['kode_voucher'] ?? null);
        $payload = ['sistem_pembayaran' => $pakaiVoucher ? 'voucher' : 'transfer'];

        if (! $pakaiVoucher) {
            $this->fakeCloudinary();
            $payload['bukti_pembayaran'] = UploadedFile::fake()->image('bukti.jpg');
        }

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.pembayaran.store', $kodePembayaran), $payload)
            ->assertRedirect(route('riwayat'));
    }

    public function test_super_admin_membuat_paket_untuk_bebberapa_hari_sekaligus(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');

        $this->masukSebagai($this->superAdmin())
            ->post('/admin/harga-jam-mains/paket', [
                'vanue_id' => (string) $vanue->id,
                'hari_list' => ['Senin', 'Sabtu'],
                'minimal_jam' => '3',
                'jam_mulai' => '08:00',
                'jam_selesai' => '17:00',
                'diskon_persen' => '5',
            ])
            ->assertRedirect(route('admin.harga-jam-mains.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, paket_hargajammain::query()->count());
        $this->assertDatabaseHas('paket_hargajammains', [
            'vanue_id' => $vanue->id,
            'hari' => 'Senin',
            'minimal_jam' => 3,
            'jam_mulai' => '08:00',
            'jam_selesai' => '17:00',
        ]);
        $this->assertDatabaseHas('paket_hargajammains', [
            'vanue_id' => $vanue->id,
            'hari' => 'Sabtu',
            'minimal_jam' => 3,
        ]);
        $this->assertSame(
            [5.0, 5.0],
            paket_hargajammain::query()->orderBy('id')->pluck('diskon_persen')->map(fn ($persen): float => (float) $persen)->all(),
        );

        $this->masukSebagai($this->superAdmin())
            ->get('/admin/harga-jam-mains')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/HargaJamMain/Index')
                ->has('paketHargas', 2)
                ->where('paketHargas.0.minimal_jam', 3)
            );
    }

    public function test_validasi_input_paket_diskon(): void
    {
        $this->masukSebagai($this->superAdmin())
            ->post('/admin/harga-jam-mains/paket', [
                'hari_list' => [],
                'minimal_jam' => '0',
                'jam_mulai' => '',
                'jam_selesai' => '',
                'diskon_persen' => '150',
            ])
            ->assertSessionHasErrors([
                'hari_list',
                'minimal_jam',
                'jam_mulai',
                'jam_selesai',
                'diskon_persen',
            ]);

        $this->masukSebagai($this->superAdmin())
            ->post('/admin/harga-jam-mains/paket', [
                'hari_list' => ['Senin'],
                'minimal_jam' => '3',
                'jam_mulai' => '8:00',
                'jam_selesai' => '25:00',
                'diskon_persen' => '10',
            ])
            ->assertSessionHasErrors(['jam_mulai', 'jam_selesai']);

        $this->assertSame(0, paket_hargajammain::query()->count());
    }

    public function test_super_admin_membuat_paket_dengan_maksimal_jam(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');

        $this->masukSebagai($this->superAdmin())
            ->post('/admin/harga-jam-mains/paket', [
                'vanue_id' => (string) $vanue->id,
                'hari_list' => ['Jumat'],
                'minimal_jam' => '2',
                'maksimal_jam' => '4',
                'jam_mulai' => '00:00',
                'jam_selesai' => '00:00',
                'diskon_persen' => '10',
            ])
            ->assertRedirect(route('admin.harga-jam-mains.index'))
            ->assertSessionHasNoErrors();

        $paket = paket_hargajammain::query()->firstOrFail();

        $this->assertSame(4, $paket->maksimal_jam);
        $this->assertDatabaseHas('paket_hargajammains', [
            'id' => $paket->id,
            'minimal_jam' => 2,
            'maksimal_jam' => 4,
        ]);

        $this->masukSebagai($this->superAdmin())
            ->get('/admin/harga-jam-mains')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/HargaJamMain/Index')
                ->where('paketHargas.0.maksimal_jam', 4)
            );
    }

    public function test_validasi_maksimal_jam_paket(): void
    {
        $payload = [
            'hari_list' => ['Senin'],
            'jam_mulai' => '00:00',
            'jam_selesai' => '00:00',
            'diskon_persen' => '10',
        ];

        $this->masukSebagai($this->superAdmin())
            ->post('/admin/harga-jam-mains/paket', $payload + [
                'minimal_jam' => '3',
                'maksimal_jam' => '2',
            ])
            ->assertSessionHasErrors('maksimal_jam');

        $this->masukSebagai($this->superAdmin())
            ->post('/admin/harga-jam-mains/paket', $payload + [
                'minimal_jam' => '2',
                'maksimal_jam' => '1.5',
            ])
            ->assertSessionHasErrors('maksimal_jam');

        $this->assertSame(0, paket_hargajammain::query()->count());
    }

    public function test_super_admin_membuat_paket_dengan_harga_tetap(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');

        $this->masukSebagai($this->superAdmin())
            ->post('/admin/harga-jam-mains/paket', [
                'vanue_id' => (string) $vanue->id,
                'hari_list' => ['Sabtu'],
                'minimal_jam' => '2',
                'maksimal_jam' => '2',
                'jam_mulai' => '10:00',
                'jam_selesai' => '12:00',
                'harga_paket' => '30000',
            ])
            ->assertRedirect(route('admin.harga-jam-mains.index'))
            ->assertSessionHasNoErrors();

        $paket = paket_hargajammain::query()->firstOrFail();

        $this->assertSame(30000.0, $paket->harga_paket);
        $this->assertNull($paket->diskon_persen);
        $this->assertDatabaseHas('paket_hargajammains', [
            'id' => $paket->id,
            'harga_paket' => 30000,
            'diskon_persen' => null,
        ]);

        $this->masukSebagai($this->superAdmin())
            ->get('/admin/harga-jam-mains')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/HargaJamMain/Index')
                ->where('paketHargas.0.harga_paket', 30000)
            );
    }

    public function test_validasi_paket_wajib_memiliki_diskon_atau_harga(): void
    {
        $payload = [
            'hari_list' => ['Senin'],
            'minimal_jam' => '2',
            'jam_mulai' => '00:00',
            'jam_selesai' => '00:00',
        ];

        $this->masukSebagai($this->superAdmin())
            ->post('/admin/harga-jam-mains/paket', $payload)
            ->assertSessionHasErrors(['diskon_persen', 'harga_paket']);

        $this->masukSebagai($this->superAdmin())
            ->post('/admin/harga-jam-mains/paket', $payload + ['harga_paket' => '-1'])
            ->assertSessionHasErrors('harga_paket');

        $this->masukSebagai($this->superAdmin())
            ->post('/admin/harga-jam-mains/paket', $payload + ['harga_paket' => 'bukan-angka'])
            ->assertSessionHasErrors('harga_paket');

        $this->assertSame(0, paket_hargajammain::query()->count());
    }

    public function test_admin_venue_hanya_melihat_dan_mengelola_paket_venue_sendiri(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $tanggal = Carbon::tomorrow();
        $paketJakarta = $this->buatPaket($jakarta, $tanggal, 3, '5');
        $paketSurabaya = $this->buatPaket($surabaya, $tanggal, 3, '10');
        $adminJakarta = $this->adminVenue($jakarta, 'admin_jakarta');

        $this->masukSebagai($adminJakarta)
            ->get('/admin/harga-jam-mains')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/HargaJamMain/Index')
                ->has('paketHargas', 1)
                ->where('paketHargas.0.id', $paketJakarta->id)
            );

        $this->masukSebagai($adminJakarta)
            ->put("/admin/harga-jam-mains/paket/{$paketSurabaya->id}", [
                'hari_list' => [$this->hariDari($tanggal)],
                'minimal_jam' => '1',
                'jam_mulai' => '00:00',
                'jam_selesai' => '23:59',
                'diskon_persen' => '50',
            ])
            ->assertForbidden();

        $this->masukSebagai($adminJakarta)
            ->delete("/admin/harga-jam-mains/paket/{$paketSurabaya->id}")
            ->assertForbidden();

        $this->assertSame(10.0, $paketSurabaya->fresh()->diskon_persen);
        $this->assertDatabaseHas('paket_hargajammains', ['id' => $paketSurabaya->id]);
    }

    public function test_admin_venue_menambah_paket_otomatis_pada_venue_sendiri(): void
    {
        $jakarta = $this->buatVanue('Mahjong Center Jakarta');
        $surabaya = $this->buatVanue('Mahjong Center Surabaya');
        $adminJakarta = $this->adminVenue($jakarta, 'admin_jakarta');

        $this->masukSebagai($adminJakarta)
            ->post('/admin/harga-jam-mains/paket', [
                'vanue_id' => (string) $surabaya->id,
                'hari_list' => ['Jumat'],
                'minimal_jam' => '2',
                'jam_mulai' => '17:00',
                'jam_selesai' => '23:00',
                'diskon_persen' => '7.5',
            ])
            ->assertRedirect(route('admin.harga-jam-mains.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('paket_hargajammains', [
            'vanue_id' => $jakarta->id,
            'hari' => 'Jumat',
            'jam_mulai' => '17:00',
            'jam_selesai' => '23:00',
        ]);
        $this->assertSame(7.5, paket_hargajammain::query()->firstOrFail()->diskon_persen);
    }

    public function test_booking_tiga_jam_mendapat_diskon_paket(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(3);

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal);
        }

        $this->buatPaket($vanue, $tanggal, 3, '10');
        $meja = Meja::create(['kode_meja' => 'MJ-PKT', 'nama_meja' => 'Meja Paket', 'vanue_id' => $vanue->id]);
        $pengguna = $this->buatPengguna('PG-PKT01');

        $this->booking($meja, $slots, $tanggal, $pengguna);

        $totalPerSesi = Booking::query()->pluck('total_harga')->all();

        $this->assertCount(3, $totalPerSesi);
        $this->assertSame(['45000', '45000', '45000'], $totalPerSesi);
    }

    public function test_booking_durasi_kurang_dari_minimal_tidak_mendapat_diskon(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(2);

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal);
        }

        $this->buatPaket($vanue, $tanggal, 3, '10');
        $meja = Meja::create(['kode_meja' => 'MJ-KURANG', 'nama_meja' => 'Meja Kurang', 'vanue_id' => $vanue->id]);
        $pengguna = $this->buatPengguna('PG-PKT02');

        $this->booking($meja, [$slots[0]], $tanggal, $pengguna);

        $this->assertSame(['50000'], Booking::query()->pluck('total_harga')->all());
    }

    public function test_booking_dalam_batas_maksimal_jam_mendapat_diskon(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(4);

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal);
        }

        $this->buatPaket($vanue, $tanggal, 2, '10', '00:00', '00:00', 3);
        $meja = Meja::create(['kode_meja' => 'MJ-MAX-IN', 'nama_meja' => 'Meja Max In', 'vanue_id' => $vanue->id]);
        $pengguna = $this->buatPengguna('PG-MAXIN01');

        $this->booking($meja, array_slice($slots, 0, 3), $tanggal, $pengguna);

        $this->assertSame(['45000', '45000', '45000'], Booking::query()->pluck('total_harga')->all());
    }

    public function test_booking_melebihi_maksimal_jam_memakai_paket_sebagian_dan_sisanya_harga_normal(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(4);

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal);
        }

        $this->buatPaket($vanue, $tanggal, 3, '10', '00:00', '00:00', 3);
        $meja = Meja::create(['kode_meja' => 'MJ-MAX-OUT', 'nama_meja' => 'Meja Max Out', 'vanue_id' => $vanue->id]);
        $pengguna = $this->buatPengguna('PG-MAXOUT1');

        $this->booking($meja, $slots, $tanggal, $pengguna);

        $this->assertSame(['45000', '45000', '45000', '50000'], Booking::query()->pluck('total_harga')->all());
        $this->assertSame([10, 10, 10, null], Booking::query()->pluck('diskon_paket_persen')->all());
    }

    public function test_booking_melebihi_maksimal_jam_memakai_harga_paket_tetap_sebagian(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(4);

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal);
        }

        $this->buatPaket($vanue, $tanggal, 3, '', '00:00', '00:00', 3, '120000');
        $meja = Meja::create(['kode_meja' => 'MJ-HARGA-PARTIAL', 'nama_meja' => 'Meja Harga Partial', 'vanue_id' => $vanue->id]);
        $pengguna = $this->buatPengguna('PG-HRGBAG01');

        $this->booking($meja, $slots, $tanggal, $pengguna);

        $bookings = Booking::query()->orderBy('id')->get();

        $this->assertSame(['40000', '40000', '40000', '50000'], $bookings->pluck('total_harga')->all());
        $this->assertSame(['40000', '40000', '40000', null], $bookings->pluck('harga_paket')->all());
        $this->assertSame(['10000', '10000', '10000', '0'], $bookings->pluck('diskon_paket')->all());

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get(route('booking.pembayaran', $bookings->first()->kode_pembayaran))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('pembayaran.subtotal', 200000)
                ->where('pembayaran.harga_paket', 170000)
                ->where('pembayaran.total', 170000)
                ->where('pembayaran.bookings.0.harga_paket', 40000)
                ->where('pembayaran.bookings.3.harga_paket', null)
                ->where('pembayaran.bookings.3.total_harga', 50000)
            );
    }

    public function test_paket_sebagian_tidak_aktif_bila_sesi_dalam_rentang_kurang_dari_batas(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(4, '12:00');

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal);
        }

        // Hanya dua sesi pertama (12:00-14:00) yang berada di dalam rentang paket.
        $this->buatPaket($vanue, $tanggal, 3, '10', '08:00', '14:00', 3);
        $meja = Meja::create(['kode_meja' => 'MJ-RENTANG-PARTIAL', 'nama_meja' => 'Meja Rentang Partial', 'vanue_id' => $vanue->id]);
        $pengguna = $this->buatPengguna('PG-RNGBAG01');

        $this->booking($meja, $slots, $tanggal, $pengguna);

        $this->assertSame(['50000', '50000', '50000', '50000'], Booking::query()->pluck('total_harga')->all());
        $this->assertSame([null], Booking::query()->pluck('harga_paket')->unique()->all());
    }

    public function test_paket_multi_tingkatan_memilih_diskon_terbesar(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(3);

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal);
        }

        $this->buatPaket($vanue, $tanggal, 2, '5');
        $this->buatPaket($vanue, $tanggal, 3, '10');

        $mejaTigaJam = Meja::create(['kode_meja' => 'MJ-TIGA', 'nama_meja' => 'Meja Tiga Jam', 'vanue_id' => $vanue->id]);
        $mejaDuaJam = Meja::create(['kode_meja' => 'MJ-DUA', 'nama_meja' => 'Meja Dua Jam', 'vanue_id' => $vanue->id]);
        $pengguna = $this->buatPengguna('PG-PKT03');

        $this->booking($mejaTigaJam, $slots, $tanggal, $pengguna);
        $this->booking($mejaDuaJam, [$slots[0], $slots[1]], $tanggal, $pengguna);

        $totalPerMeja = Booking::query()
            ->pluck('total_harga', 'id_meja')
            ->all();

        $this->assertSame('45000', $totalPerMeja['MJ-TIGA']);
        $this->assertSame('47500', $totalPerMeja['MJ-DUA']);
    }

    public function test_paket_hanya_berlaku_pada_hari_dan_venue_yang_sama(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $vanueLain = $this->buatVanue('Mahjong Center Surabaya');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(3);

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal);
            $this->buatHarga($jam, $vanueLain, $tanggal);
        }

        // Paket hanya untuk venue lain dan hanya untuk hari lain.
        paket_hargajammain::create([
            'vanue_id' => $vanueLain->id,
            'hari' => $this->hariDari($tanggal),
            'minimal_jam' => 3,
            'diskon_persen' => '10',
        ]);
        paket_hargajammain::create([
            'vanue_id' => $vanue->id,
            'hari' => $this->hariDari($tanggal->copy()->addDays(3)),
            'minimal_jam' => 3,
            'diskon_persen' => '10',
        ]);

        $meja = Meja::create(['kode_meja' => 'MJ-HARI', 'nama_meja' => 'Meja Hari', 'vanue_id' => $vanue->id]);
        $pengguna = $this->buatPengguna('PG-PKT04');

        $this->booking($meja, $slots, $tanggal, $pengguna);

        $this->assertSame(['50000', '50000', '50000'], Booking::query()->pluck('total_harga')->all());
    }

    public function test_voucher_tidak_bertumpuk_dengan_paket(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(3);

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal);
        }

        $this->buatPaket($vanue, $tanggal, 3, '10');
        $meja = Meja::create(['kode_meja' => 'MJ-VOU', 'nama_meja' => 'Meja Voucher', 'vanue_id' => $vanue->id]);
        $pengguna = $this->buatPengguna('PG-PKT05');

        $statusVoucher = Statusvoucher::create([
            'status_voucher' => 'Voucher Tes',
            'jumah_booking' => '1',
            'jumah_jam' => '3',
        ]);
        $voucher = Voucher::create([
            'kode_user' => $pengguna->kode_user,
            'kode_voucher' => 'VC-PAKET-001',
            'tanggal_diterima' => Carbon::today()->toDateString(),
            'tanggal_digunakan' => '',
            'id_statusvoucher' => (string) $statusVoucher->id,
            'status' => 'aktif',
        ]);

        $this->booking($meja, $slots, $tanggal, $pengguna, ['kode_voucher' => $voucher->kode_voucher]);

        $bookings = Booking::query()->get();

        $this->assertCount(3, $bookings);
        $this->assertSame(['0', '0', '0'], $bookings->pluck('total_harga')->all());
        $this->assertSame(['lunas', 'lunas', 'lunas'], $bookings->pluck('status_pembayaran')->all());
        $this->assertSame(['voucher', 'voucher', 'voucher'], $bookings->pluck('sistem_pembayaran')->all());
        $this->assertSame(now()->toDateString(), $voucher->fresh()->tanggal_digunakan);
    }

    public function test_hapus_paket_menghilangkan_diskon_pada_booking_berikutnya(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(3);

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal);
        }

        $paket = $this->buatPaket($vanue, $tanggal, 3, '10');
        $meja = Meja::create(['kode_meja' => 'MJ-HAPUS', 'nama_meja' => 'Meja Hapus', 'vanue_id' => $vanue->id]);
        $pengguna = $this->buatPengguna('PG-PKT06');

        $this->masukSebagai($this->superAdmin())
            ->delete("/admin/harga-jam-mains/paket/{$paket->id}")
            ->assertRedirect(route('admin.harga-jam-mains.index'));

        $this->assertDatabaseMissing('paket_hargajammains', ['id' => $paket->id]);

        $this->booking($meja, $slots, $tanggal, $pengguna);

        $this->assertSame(['50000', '50000', '50000'], Booking::query()->pluck('total_harga')->all());
    }

    public function test_paket_rentang_pagi_dan_malam_memberi_diskon_beda(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $slotsPagi = $this->buatSlotBerturut(3, '08:00');
        $slotsMalam = $this->buatSlotBerturut(3, '18:00');

        foreach ([...$slotsPagi, ...$slotsMalam] as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal);
        }

        $this->buatPaket($vanue, $tanggal, 3, '10', '08:00', '17:00');
        $this->buatPaket($vanue, $tanggal, 3, '20', '17:00', '23:00');

        $mejaPagi = Meja::create(['kode_meja' => 'MJ-PAGI', 'nama_meja' => 'Meja Pagi', 'vanue_id' => $vanue->id]);
        $mejaMalam = Meja::create(['kode_meja' => 'MJ-MALAM', 'nama_meja' => 'Meja Malam', 'vanue_id' => $vanue->id]);
        $pengguna = $this->buatPengguna('PG-PKT07');

        $this->booking($mejaPagi, $slotsPagi, $tanggal, $pengguna);
        $this->booking($mejaMalam, $slotsMalam, $tanggal, $pengguna);

        $totalPerMeja = Booking::query()
            ->pluck('total_harga', 'id_meja')
            ->all();

        // Sesi 08:00 - 11:00 masuk rentang pagi (10%), sesi 18:00 - 21:00 masuk rentang malam (20%).
        $this->assertSame('45000', $totalPerMeja['MJ-PAGI']);
        $this->assertSame('40000', $totalPerMeja['MJ-MALAM']);
    }

    public function test_booking_melewati_batas_rentang_tidak_mendapat_diskon(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        // Sesi 15:00-16:00, 16:00-17:00, dan 17:00-18:00; total 3 jam tetapi sesi
        // terakhir berada di luar rentang paket 08:00 - 17:00.
        $slots = $this->buatSlotBerturut(3, '15:00');

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal);
        }

        $this->buatPaket($vanue, $tanggal, 3, '10', '08:00', '17:00');
        $meja = Meja::create(['kode_meja' => 'MJ-BATAS', 'nama_meja' => 'Meja Batas', 'vanue_id' => $vanue->id]);
        $pengguna = $this->buatPengguna('PG-PKT08');

        $this->booking($meja, $slots, $tanggal, $pengguna);

        $this->assertSame(['50000', '50000', '50000'], Booking::query()->pluck('total_harga')->all());
    }

    public function test_admin_venue_bisa_mengubah_rentang_jam_paket(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $paket = $this->buatPaket($vanue, $tanggal, 3, '10', '08:00', '17:00');
        $admin = $this->adminVenue($vanue, 'admin_rentang');

        $this->masukSebagai($admin)
            ->put("/admin/harga-jam-mains/paket/{$paket->id}", [
                'hari_list' => [$this->hariDari($tanggal)],
                'minimal_jam' => '3',
                'maksimal_jam' => '5',
                'jam_mulai' => '17:00',
                'jam_selesai' => '23:00',
                'diskon_persen' => '20',
            ])
            ->assertRedirect(route('admin.harga-jam-mains.index'))
            ->assertSessionHasNoErrors();

        $paket->refresh();

        $this->assertSame(5, $paket->maksimal_jam);
        $this->assertSame('17:00', $paket->jam_mulai);
        $this->assertSame('23:00', $paket->jam_selesai);
        $this->assertSame(20.0, $paket->diskon_persen);
        $this->assertSame($vanue->id, $paket->vanue_id);

        $this->masukSebagai($admin)
            ->delete("/admin/harga-jam-mains/paket/{$paket->id}")
            ->assertRedirect(route('admin.harga-jam-mains.index'));

        $this->assertDatabaseMissing('paket_hargajammains', ['id' => $paket->id]);
    }

    public function test_admin_bisa_mengubah_paket_diskon_menjadi_harga_tetap(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $paket = $this->buatPaket($vanue, $tanggal, 2, '10');
        $admin = $this->adminVenue($vanue, 'admin_harga_tetap');

        $this->masukSebagai($admin)
            ->put("/admin/harga-jam-mains/paket/{$paket->id}", [
                'hari_list' => [$this->hariDari($tanggal)],
                'minimal_jam' => '2',
                'jam_mulai' => '00:00',
                'jam_selesai' => '00:00',
                'diskon_persen' => '',
                'harga_paket' => '27000',
            ])
            ->assertRedirect(route('admin.harga-jam-mains.index'))
            ->assertSessionHasNoErrors();

        $paket->refresh();

        $this->assertNull($paket->diskon_persen);
        $this->assertSame(27000.0, $paket->harga_paket);
    }

    public function test_booking_paket_harga_tetap_dibagi_proporsional_ke_semua_sesi(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(2);

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal, '20000');
        }

        $this->buatPaket($vanue, $tanggal, 2, '', '00:00', '00:00', null, '30000');
        $meja = Meja::create(['kode_meja' => 'MJ-HARGA', 'nama_meja' => 'Meja Harga', 'vanue_id' => $vanue->id]);
        $pengguna = $this->buatPengguna('PG-HARGA01');

        $this->booking($meja, $slots, $tanggal, $pengguna);

        $bookings = Booking::query()->orderBy('id')->get();

        $this->assertSame(['15000', '15000'], $bookings->pluck('total_harga')->all());
        $this->assertSame(['5000', '5000'], $bookings->pluck('diskon_paket')->all());
        $this->assertSame([null, null], $bookings->pluck('diskon_paket_persen')->all());
        $this->assertSame(['15000', '15000'], $bookings->pluck('harga_paket')->all());

        $kodePembayaran = $bookings->first()->kode_pembayaran;

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get(route('booking.pembayaran', $kodePembayaran))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('pembayaran.harga_paket', 30000)
                ->where('pembayaran.total', 30000)
                ->where('pembayaran.bookings.0.harga_paket', 15000)
                ->where('pembayaran.bookings.1.harga_paket', 15000)
            );
    }

    public function test_harga_paket_mengalahkan_diskon_dan_memilih_harga_efektif_terendah(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(2);

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal, '20000');
        }

        // Diskon 5% dari Rp40.000 = Rp38.000, kalah dari harga paket Rp25.000.
        $this->buatPaket($vanue, $tanggal, 2, '5');
        $this->buatPaket($vanue, $tanggal, 2, '10', '00:00', '00:00', null, '25000');

        $meja = Meja::create(['kode_meja' => 'MJ-EFEKTIF', 'nama_meja' => 'Meja Efektif', 'vanue_id' => $vanue->id]);
        $pengguna = $this->buatPengguna('PG-EFEK001');

        $this->booking($meja, $slots, $tanggal, $pengguna);

        $bookings = Booking::query()->orderBy('id')->get();

        $this->assertSame(['12500', '12500'], $bookings->pluck('total_harga')->all());
        $this->assertSame(['7500', '7500'], $bookings->pluck('diskon_paket')->all());
        $this->assertSame([null, null], $bookings->pluck('diskon_paket_persen')->all());
        $this->assertSame(['12500', '12500'], $bookings->pluck('harga_paket')->all());
    }

    public function test_hapus_sesi_draft_mengembalikan_harga_normal_dari_paket_harga(): void
    {
        $vanue = $this->buatVanue('Mahjong Center Jakarta');
        $tanggal = Carbon::tomorrow();
        $slots = $this->buatSlotBerturut(2);

        foreach ($slots as $jam) {
            $this->buatHarga($jam, $vanue, $tanggal, '20000');
        }

        $this->buatPaket($vanue, $tanggal, 2, '', '00:00', '00:00', null, '30000');
        $meja = Meja::create(['kode_meja' => 'MJ-HAPUS-HARGA', 'nama_meja' => 'Meja Hapus Harga', 'vanue_id' => $vanue->id]);
        $pengguna = $this->buatPengguna('PG-HPSHRG01');

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post('/booking', [
                'meja_id' => (string) $meja->id,
                'jam_main_ids' => array_map(fn (jam_main $jam): string => (string) $jam->id, $slots),
                'tgl_main' => $tanggal->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $kodePembayaran = Str::afterLast((string) $response->headers->get('Location'), '/');

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get(route('booking.pembayaran', $kodePembayaran))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('pembayaran.harga_paket', 30000)
                ->where('pembayaran.total', 30000)
                ->has('pembayaran.bookings', 2)
                ->where('pembayaran.bookings.0.harga_paket', 15000)
                ->where('pembayaran.bookings.1.harga_paket', 15000)
            );

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->delete(route('booking.pembayaran.sesi.destroy', [$kodePembayaran, 'draft-0']))
            ->assertRedirect(route('booking.pembayaran', $kodePembayaran));

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get(route('booking.pembayaran', $kodePembayaran))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('pembayaran.harga_paket', null)
                ->where('pembayaran.total', 20000)
                ->has('pembayaran.bookings', 1)
                ->where('pembayaran.bookings.0.total_harga', 20000)
                ->where('pembayaran.bookings.0.diskon_paket', 0)
                ->where('pembayaran.bookings.0.harga_paket', null)
            );

        $this->fakeCloudinary();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.pembayaran.store', $kodePembayaran), [
                'sistem_pembayaran' => 'transfer',
                'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
            ])
            ->assertRedirect(route('riwayat'));

        $this->assertSame(['20000'], Booking::query()->pluck('total_harga')->all());
        $this->assertSame([null], Booking::query()->pluck('harga_paket')->all());
    }
}
