<?php

namespace App\Models;

use App\Services\BuktiPembayaranService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'id_meja',
        'jam_mulai',
        'jam_selesai',
        'tgl_main',
        'tgl_booking',
        'sistem_pembayaran',
        'harga',
        'total_harga',
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
            fn (): ?string => $this->bukti_pembayaran
                ? asset('storage/'.$this->bukti_pembayaran)
                : null,
        );
    }

    /**
     * URL thumbnail untuk daftar (list). Fallback ke gambar utama bila thumbnail
     * belum ada (booking lama sebelum fitur kompres aktif).
     */
    protected function buktiPembayaranThumbUrl(): Attribute
    {
        return Attribute::get(
            function (): ?string {
                if (! $this->bukti_pembayaran) {
                    return null;
                }

                $thumbnail = app(BuktiPembayaranService::class)->pathThumbnail($this->bukti_pembayaran);

                if ($thumbnail && Storage::disk('public')->exists($thumbnail)) {
                    return asset('storage/'.$thumbnail);
                }

                return asset('storage/'.$this->bukti_pembayaran);
            },
        );
    }
}
