<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Rules\NomorWhatsappBelumTerdaftar;
use App\Services\OtpRegisterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AuthController extends Controller
{
    public function showRegister(Request $request): Response|RedirectResponse
    {
        if ($this->currentPengguna($request)) {
            return redirect()->route('home');
        }

        return Inertia::render('User/Auth/Register');
    }

    public function kirimOtpRegister(Request $request, OtpRegisterService $otp): RedirectResponse
    {
        $validated = $request->validate([
            'nowa' => ['required', 'string', 'max:20', 'regex:/^(\+?62|0|8)[0-9]{8,13}$/', new NomorWhatsappBelumTerdaftar],
        ], [
            'nowa.regex' => 'Format nomor WhatsApp tidak valid. Contoh: 08xxxxxxxxxx.',
        ]);

        if (! $otp->bisaKirim($validated['nowa'])) {
            throw ValidationException::withMessages([
                'nowa' => 'Tunggu 60 detik sebelum meminta kode OTP lagi.',
            ]);
        }

        try {
            $otp->kirimKode($validated['nowa']);
        } catch (Throwable $exception) {
            Log::warning('Gagal mengirim OTP registrasi: '.$exception->getMessage());

            throw ValidationException::withMessages([
                'nowa' => 'Kode OTP gagal dikirim. Periksa nomor WhatsApp lalu coba lagi.',
            ]);
        }

        return back()->with('otp_terkirim', true);
    }

    public function register(Request $request, OtpRegisterService $otp): RedirectResponse
    {
        $validated = $request->validate([
            'nama_depan' => ['required', 'string', 'max:50'],
            'nama_balakang' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', Rule::unique('penggunas', 'email')],
            'nowa' => ['required', 'string', 'max:20', 'regex:/^(\+?62|0|8)[0-9]{8,13}$/', new NomorWhatsappBelumTerdaftar],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'nowa.regex' => 'Format nomor WhatsApp tidak valid. Contoh: 08xxxxxxxxxx.',
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.size' => 'Kode OTP harus terdiri dari 6 angka.',
            'otp.regex' => 'Kode OTP harus terdiri dari 6 angka.',
        ]);

        if (! $otp->verifikasiKode($validated['nowa'], $validated['otp'])) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP salah atau sudah kedaluwarsa. Minta kode baru jika perlu.',
            ]);
        }

        $pengguna = Pengguna::create([
            'kode_user' => Pengguna::buatKodeUserUnik(),
            'nama_depan' => $validated['nama_depan'],
            'nama_balakang' => $validated['nama_balakang'] ?? '',
            'email' => $validated['email'],
            'nowa' => $validated['nowa'],
            'password' => Hash::make($validated['password']),
            'status' => 'aktif',
        ]);

        $request->session()->regenerate();
        $request->session()->put('pengguna_id', $pengguna->id);

        return redirect()
            ->route('home')
            ->with('success', "Akun berhasil dibuat. Selamat datang, {$pengguna->nama_depan}.");
    }

    public function showLogin(Request $request): Response|RedirectResponse
    {
        if ($this->currentPengguna($request)) {
            return redirect()->route('home');
        }

        return Inertia::render('User/Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $pengguna = Pengguna::query()
            ->where('nowa', $validated['login'])
            ->orWhere('email', $validated['login'])
            ->latest()
            ->first();

        if (! $pengguna || ! $pengguna->password || ! Hash::check($validated['password'], $pengguna->password)) {
            throw ValidationException::withMessages([
                'login' => 'Nomor WhatsApp/email atau password salah.',
            ]);
        }

        if ($pengguna->status !== 'aktif') {
            throw ValidationException::withMessages([
                'login' => 'Akun ini sedang nonaktif. Silakan hubungi admin.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put('pengguna_id', $pengguna->id);

        return redirect()
            ->intended(route('home'))
            ->with('success', "Selamat datang kembali, {$pengguna->nama_depan}.");
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('pengguna_id');
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Kamu berhasil keluar dari sesi.');
    }

    private function currentPengguna(Request $request): ?Pengguna
    {
        $id = $request->session()->get('pengguna_id');

        return $id ? Pengguna::query()->find($id) : null;
    }
}
