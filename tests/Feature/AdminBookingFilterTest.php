<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminBookingFilterTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'username' => 'super',
            'role' => 'super_admin',
            'password' => 'password123',
        ]);
    }

    public function test_filter_kode_booking_menampilkan_booking_yang_cocok(): void
    {
        $this->buatBooking(['kode_booking' => 'BK-20261124-AAAA']);
        $this->buatBooking(['kode_booking' => 'BK-20261124-BBBB']);

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/bookings?kode_booking=AAAA')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.kode_booking', 'AAAA')
                ->has('bookings.data', 1)
                ->where('bookings.data.0.kode_booking', 'BK-20261124-AAAA'));
    }

    public function test_filter_tanggal_main_hanya_menampilkan_tanggal_tersebut(): void
    {
        $this->buatBooking([
            'kode_booking' => 'BK-TGL-24',
            'tgl_main' => '2026-11-24',
        ]);
        $this->buatBooking([
            'kode_booking' => 'BK-TGL-25',
            'tgl_main' => '2026-11-25',
        ]);

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/bookings?tanggal=2026-11-24')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.tanggal', '2026-11-24')
                ->has('bookings.data', 1)
                ->where('bookings.data.0.kode_booking', 'BK-TGL-24'));
    }

    public function test_filter_user_mencari_nama_akun_dan_nama_booking_manual(): void
    {
        $pengguna = Pengguna::create([
            'kode_user' => 'PG-FILTER01',
            'nama_depan' => 'Siti',
            'nama_balakang' => 'Aminah',
            'email' => 'siti@example.com',
            'nowa' => '081200000001',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $this->buatBooking([
            'kode_booking' => 'BK-AKUN',
            'id_pengguna' => (string) $pengguna->id,
        ]);
        $this->buatBooking([
            'kode_booking' => 'BK-MANUAL',
            'id_pengguna' => null,
            'nama_pengguna' => 'Budi Manual',
            'no_wa' => '081200000002',
        ]);

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/bookings?user=Siti')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('bookings.data', 1)
                ->where('bookings.data.0.kode_booking', 'BK-AKUN'));

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/bookings?user=Budi')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('bookings.data', 1)
                ->where('bookings.data.0.kode_booking', 'BK-MANUAL'));

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/bookings?user=081200000002')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('bookings.data', 1)
                ->where('bookings.data.0.kode_booking', 'BK-MANUAL'));
    }

    /**
     * @param  array<string, mixed>  $tambahan
     */
    private function buatBooking(array $tambahan = []): Booking
    {
        return Booking::create(array_merge([
            'kode_booking' => 'BK-DEFAULT',
            'id_pengguna' => '1',
            'id_meja' => 'MJ-01',
            'jam_mulai' => '10:00',
            'jam_selesai' => '11:00',
            'tgl_main' => Carbon::today()->toDateString(),
            'tgl_booking' => Carbon::today()->toDateString(),
            'sistem_pembayaran' => 'transfer',
            'harga' => '50000',
            'total_harga' => '50000',
            'bukti_pembayaran' => '',
            'status_booking' => '200',
            'status_pembayaran' => 'lunas',
            'status_hapus' => '0',
        ], $tambahan));
    }
}
