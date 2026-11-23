<?php

namespace App\Models;

use App\Services\BuktiPembayaranService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Booking extends Model
{
    protected $appends = [
        'bukti_pembayaran_url',
        'bukti_pembayaran_thumb_url',
    ];

    protected $fillable = [
        'kode_booking',
        'kode_pembayaran',
        'kode_voucher',
        'tipe_booking',
        'kode_room',
        'kapasitas_user',
        'mode_pembayaran',
        'id_pengguna',
        'nama_pengguna',
        'no_wa',
        'id_meja',
        'jam_mulai',
        'jam_selesai',
        'tgl_main',
        'tgl_booking',
        'sistem_pembayaran',
        'harga',
        'total_harga',
        'diskon_paket',
        'diskon_paket_persen',
        'harga_paket',
        'bukti_pembayaran',
        'status_booking',
        'status_pembayaran',
        'dibayar_pada',
        'status_hapus',
    ];

    protected function casts(): array
    {
        return [
            'dibayar_pada' => 'datetime',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna');
    }

    /**
     * Meja yang dibooking. Kolom id_meja menyimpan kode meja, dan bila kosong
     * fallback ke id meja.
     */
    public function meja(): HasOne
    {
        return $this->hasOne(Meja::class, 'kode_meja', 'id_meja');
    }

    /**
     * Nama pembuka room untuk tiap kode room (booking paling awal pada room tersebut).
     *
     * @param  array<int, string>  $kodeRooms
     * @return array<string, string>
     */
    public static function namaPembukaRoom(array $kodeRooms): array
    {
        if ($kodeRooms === []) {
            return [];
        }

        return static::query()
            ->whereIn('kode_room', $kodeRooms)
            ->where('status_hapus', '!=', '1')
            ->with('pengguna:id,nama_depan,nama_balakang')
            ->orderBy('id')
            ->get()
            ->unique('kode_room')
            ->mapWithKeys(function (Booking $booking): array {
                $nama = $booking->pengguna
                    ? trim($booking->pengguna->nama_depan.' '.$booking->pengguna->nama_balakang)
                    : '-';

                return [$booking->kode_room => $nama];
            })
            ->all();
    }

    protected function buktiPembayaranUrl(): Attribute
    {
        return Attribute::get(
            function (): ?string {
                if (! $this->bukti_pembayaran) {
                    return null;
                }

                if ($this->adalahUrl($this->bukti_pembayaran)) {
                    return $this->bukti_pembayaran;
                }

                return asset('storage/'.$this->bukti_pembayaran);
            },
        );
    }

    /**
     * URL thumbnail untuk daftar (list). Booking baru memakai transformasi
     * Cloudinary; booking lama masih memakai thumbnail di storage lokal.
     */
    protected function buktiPembayaranThumbUrl(): Attribute
    {
        return Attribute::get(
            function (): ?string {
                if (! $this->bukti_pembayaran) {
                    return null;
                }

                $thumbnail = app(BuktiPembayaranService::class)->pathThumbnail($this->bukti_pembayaran);

                if (! $thumbnail) {
                    return null;
                }

                if ($this->adalahUrl($thumbnail)) {
                    return $thumbnail;
                }

                if (Storage::disk('public')->exists($thumbnail)) {
                    return asset('storage/'.$thumbnail);
                }

                return $this->bukti_pembayaran_url;
            },
        );
    }

    private function adalahUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }
}
