<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showRegister(Request $request): Response|RedirectResponse
    {
        if ($this->currentPengguna($request)) {
            return redirect()->route('home');
        }

        return Inertia::render('User/Auth/Register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_depan' => ['required', 'string', 'max:50'],
            'nama_balakang' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:50', Rule::unique('penggunas', 'email')],
            'nowa' => ['required', 'string', 'max:20', Rule::unique('penggunas', 'nowa')],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);

        do {
            $kodeUser = 'PG-' . strtoupper(Str::random(6));
        } while (Pengguna::query()->where('kode_user', $kodeUser)->exists());

        $pengguna = Pengguna::create([
            'kode_user' => $kodeUser,
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
            'login' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        $pengguna = Pengguna::query()
            ->where('nowa', $validated['login'])
            ->orWhere('email', $validated['login'])
            ->latest()
            ->first();

        if (! $pengguna || ! Hash::check($validated['password'], $pengguna->password)) {
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
