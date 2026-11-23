<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Verifikasiwhatsapp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VerifikasiwhatsappController extends Controller
{
    /**
     * Pengaturan token WhatsApp gateway yang dipakai untuk mengirim OTP.
     * Hanya satu token yang boleh berstatus aktif.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $verifikasiWhatsapps = Verifikasiwhatsapp::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nowa', 'like', "%{$search}%")
                        ->orWhere('token', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, $this->statusTersedia(), true), fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/VerifikasiWhatsapp/Index', [
            'verifikasiWhatsapps' => $verifikasiWhatsapps,
            'statuses' => $this->statusTersedia(),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());

        DB::transaction(function () use ($validated) {
            if ($validated['status'] === Verifikasiwhatsapp::STATUS_AKTIF) {
                Verifikasiwhatsapp::query()
                    ->where('status', Verifikasiwhatsapp::STATUS_AKTIF)
                    ->update(['status' => Verifikasiwhatsapp::STATUS_NONAKTIF]);
            }

            Verifikasiwhatsapp::create($validated);
        });

        return back()->with('success', 'Verifikasi WhatsApp berhasil ditambahkan.');
    }

    public function update(Request $request, Verifikasiwhatsapp $verifikasiwhatsapp): RedirectResponse
    {
        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());

        DB::transaction(function () use ($validated, $verifikasiwhatsapp) {
            if ($validated['status'] === Verifikasiwhatsapp::STATUS_AKTIF) {
                Verifikasiwhatsapp::query()
                    ->where('status', Verifikasiwhatsapp::STATUS_AKTIF)
                    ->whereKeyNot($verifikasiwhatsapp->getKey())
                    ->update(['status' => Verifikasiwhatsapp::STATUS_NONAKTIF]);
            }

            $verifikasiwhatsapp->update($validated);
        });

        return back()->with('success', 'Verifikasi WhatsApp berhasil diperbarui.');
    }

    public function destroy(Verifikasiwhatsapp $verifikasiwhatsapp): RedirectResponse
    {
        $verifikasiwhatsapp->delete();

        return back()->with('success', 'Verifikasi WhatsApp berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function aturanValidasi(): array
    {
        return [
            'nowa' => [
                'required',
                'string',
                'max:15',
                'regex:/^(\+?62|0|8)[0-9]{8,12}$/',
            ],
            'token' => ['required', 'string', 'max:200'],
            'status' => ['required', Rule::in($this->statusTersedia())],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasi(): array
    {
        return [
            'nowa.required' => 'Nomor WhatsApp wajib diisi.',
            'nowa.regex' => 'Format nomor WhatsApp tidak valid. Contoh: 08xxxxxxxxxx.',
            'nowa.max' => 'Nomor WhatsApp maksimal 15 karakter.',
            'token.required' => 'Token wajib diisi.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function statusTersedia(): array
    {
        return [
            Verifikasiwhatsapp::STATUS_AKTIF,
            Verifikasiwhatsapp::STATUS_NONAKTIF,
        ];
    }
}
