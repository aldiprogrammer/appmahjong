<?php

namespace App\Http\Middleware;

use App\Models\Pengguna;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePenggunaAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->session()->get('pengguna_id');
        $pengguna = $id ? Pengguna::query()->find($id) : null;

        if (! $pengguna || $pengguna->status !== 'aktif') {
            $request->session()->forget('pengguna_id');

            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
