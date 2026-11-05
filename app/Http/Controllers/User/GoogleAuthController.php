<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Redirect pengguna ke halaman persetujuan Google.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Tangani callback dari Google lalu buat atau tautkan akun pengguna.
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()
                ->route('login')
                ->with('error', 'Gagal masuk dengan Google. Silakan coba lagi.');
        }

        $email = $googleUser->getEmail();

        if (! $email) {
            return redirect()
                ->route('login')
                ->with('error', 'Akun Google tidak memiliki alamat email.');
        }

        $pengguna = Pengguna::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->latest()
            ->first();

        if ($pengguna) {
            if ($pengguna->status !== 'aktif') {
                return redirect()
                    ->route('login')
                    ->with('error', 'Akun ini sedang nonaktif. Silakan hubungi admin.');
            }

            $pengguna->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);
        } else {
            $namaLengkap = trim((string) ($googleUser->getName() ?: $googleUser->getNickname() ?: 'Pengguna'));
            $bagianNama = preg_split('/\s+/', $namaLengkap, 2);

            $pengguna = Pengguna::create([
                'kode_user' => Pengguna::buatKodeUserUnik(),
                'nama_depan' => Str::substr($bagianNama[0] ?? 'Pengguna', 0, 50),
                'nama_balakang' => Str::substr($bagianNama[1] ?? '', 0, 50),
                'email' => $email,
                'password' => null,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'status' => 'aktif',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put('pengguna_id', $pengguna->id);

        return redirect()
            ->intended(route('home'))
            ->with('success', "Selamat datang, {$pengguna->nama_depan}.");
    }
}
