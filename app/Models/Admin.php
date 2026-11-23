<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admin extends Model
{
    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const MENU_DASHBOARD = 'dashboard';

    /**
     * Daftar menu panel admin. Kuncinya adalah segmen nama rute setelah "admin.",
     * sehingga hak akses dapat dicocokkan dengan rute yang dibuka.
     *
     * @var array<string, string>
     */
    public const MENU_AKSES = [
        'dashboard' => 'Dashboard',
        'bookings' => 'Booking',
        'kasir' => 'Kasir',
        'mejas' => 'Meja',
        'vanues' => 'Venue',
        'jam-mains' => 'Jam Main',
        'harga-jam-mains' => 'Harga Jam Main',
        'kategori-menus' => 'Kategori Menu',
        'menus' => 'Menu',
        'penggunas' => 'Pengguna',
        'setting-booking-rooms' => 'Setting Room Booking',
        'aturan-points' => 'Aturan Point',
        'mahjong-faan-rules' => 'Mahjong Faan Rules',
        'status-vouchers' => 'Status Voucher',
        'suplayer-foods' => 'Suplayer Food',
        'vouchers' => 'Voucher',
        'admins' => 'Akun Admin',
        'printers' => 'Printer',
        'pembayarans' => 'Pembayaran',
        'verifikasi-whatsapps' => 'Verifikasi WhatsApp',
    ];

    protected $fillable = [
        'username',
        'role',
        'vanue_id',
        'hak_akses',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'hak_akses' => 'array',
        ];
    }

    public function vanue(): BelongsTo
    {
        return $this->belongsTo(Vanue::class);
    }

    /**
     * Admin bebas membuka menu bila tidak ada pembatasan, menu bukan bagian
     * panel, atau menu termasuk dalam hak aksesnya.
     */
    public function bolehAksesMenu(string $menu): bool
    {
        if ($menu === self::MENU_DASHBOARD || ! array_key_exists($menu, self::MENU_AKSES)) {
            return true;
        }

        if ($this->hak_akses === null) {
            return true;
        }

        return in_array($menu, $this->hak_akses, true);
    }
}
