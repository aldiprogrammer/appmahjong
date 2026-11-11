<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Mengompres dan meresize bukti pembayaran (JPG/PNG) saat diunggah:
 * - gambar utama maksimal 1000x1400px, kualitas JPEG 80
 * - thumbnail untuk daftar maksimal lebar 420px, kualitas JPEG 75
 *
 * File asli tidak disimpan; hanya hasil terkompres yang masuk ke disk 'public'
 * di folder bukti-pembayaran/. Nama file digenerasi acak.
 */
class BuktiPembayaranService
{
    private const MAX_LEBAR = 1000;

    private const MAX_TINGGI = 1400;

    private const LEBAR_THUMBNAIL = 420;

    private const KUALITAS = 80;

    private const KUALITAS_THUMBNAIL = 75;

    private const DISK = 'public';

    private const FOLDER = 'bukti-pembayaran';

    /**
     * Simpan bukti pembayaran terkompres, kembalikan path relatif gambar utama.
     * Path thumbnail selalu <nama>-thumb.jpg, bisa diakses via Booking::bukti_pembayaran_thumb_url.
     */
    public function simpan(UploadedFile $file): string
    {
        try {
            $gd = imagecreatefromstring((string) file_get_contents($file->getRealPath()));

            if ($gd === false) {
                // Fallback: simpan apa adanya bila GD tidak bisa memproses gambar.
                return $file->store(self::FOLDER, self::DISK);
            }

            $gd = $this->perbaikiOrientasiExif($file, $gd);
            $nama = bin2hex(random_bytes(12));

            $this->simpanJpeg($gd, self::MAX_LEBAR, self::MAX_TINGGI, self::KUALITAS, "{$nama}.jpg");
            $this->simpanJpeg($gd, self::LEBAR_THUMBNAIL, self::MAX_TINGGI, self::KUALITAS_THUMBNAIL, "{$nama}-thumb.jpg");

            imagedestroy($gd);

            return sprintf('%s/%s.jpg', self::FOLDER, $nama);
        } catch (\Throwable $exception) {
            // Bila server tidak sanggup memproses (mis. batas memori GD), simpan file asli
            // agar pengguna tetap bisa mengunggah bukti pembayaran.
            report($exception);

            return $file->store(self::FOLDER, self::DISK);
        }
    }

    /**
     * Thumbnail Pinpath dari path utama; null bila path utama kosong.
     */
    public function pathThumbnail(?string $pathUtama): ?string
    {
        if (! $pathUtama) {
            return null;
        }

        $info = pathinfo($pathUtama);

        return sprintf('%s/%s-thumb.jpg', $info['dirname'] ?? '', $info['filename']);
    }

    /**
     * Hapus gambar utama dan thumbnail bila ada.
     */
    public function hapus(?string $pathUtama): void
    {
        if (! $pathUtama) {
            return;
        }

        Storage::disk(self::DISK)->delete($pathUtama);
        $thumbnail = $this->pathThumbnail($pathUtama);

        if ($thumbnail) {
            Storage::disk(self::DISK)->delete($thumbnail);
        }
    }

    /**
     * Ukur ulang lebar gambar bila melebihi batas, lalu simpan sebagai JPEG.
     */
    private function simpanJpeg(\GdImage $gd, int $lebarMaks, int $tinggiMaks, int $kualitas, string $namaFile): void
    {
        $lebarAsli = max(1, imagesx($gd));
        $tinggiAsli = max(1, imagesy($gd));
        $skala = min(1, $lebarMaks / $lebarAsli, $tinggiMaks / $tinggiAsli);

        $lebarBaru = max(1, (int) round($lebarAsli * $skala));
        $tinggiBaru = max(1, (int) round($tinggiAsli * $skala));

        $hasil = $this->kanvasJpeg($gd, $lebarBaru, $tinggiBaru);

        ob_start();
        imagejpeg($hasil, null, $kualitas);
        $isi = (string) ob_get_clean();
        imagedestroy($hasil);

        Storage::disk(self::DISK)->put(self::FOLDER.'/'.$namaFile, $isi);
    }

    /**
     * Kanvas bersih dengan latar putih agar PNG transparan tidak hitam saat dijadikan JPEG.
     * Resize dilakukan lewat imagecopyresampled agar tidak bergantung pada imagescale()
     * yang bisa mengembalikan false pada sebagian konfigurasi GD.
     */
    private function kanvasJpeg(\GdImage $gd, int $lebar, int $tinggi): \GdImage
    {
        $kanvas = imagecreatetruecolor($lebar, $tinggi);

        if ($kanvas === false) {
            throw new \RuntimeException('Gagal membuat kanvas gambar bukti pembayaran.');
        }

        $putih = (int) imagecolorallocate($kanvas, 255, 255, 255);
        imagefill($kanvas, 0, 0, $putih);
        imagecopyresampled($kanvas, $gd, 0, 0, 0, 0, $lebar, $tinggi, imagesx($gd), imagesy($gd));

        return $kanvas;
    }

    /**
     * Koreksi rotasi foto HP (orientation EXIF dari kamera) sebelum resize.
     */
    private function perbaikiOrientasiExif(UploadedFile $file, \GdImage $gd): \GdImage
    {
        $orientasi = 0;

        try {
            $exif = @exif_read_data((string) $file->getRealPath());
            $orientasi = (int) ($exif['Orientation'] ?? 0);
        } catch (\Throwable) {
            // Gambar tanpa EXIF dipakai apa adanya.
        }

        $hasilRotasi = match ($orientasi) {
            3 => imagerotate($gd, 180, 0),
            6 => imagerotate($gd, -90, 0),
            8 => imagerotate($gd, 90, 0),
            default => $gd,
        };

        return $hasilRotasi instanceof \GdImage ? $hasilRotasi : $gd;
    }
}
