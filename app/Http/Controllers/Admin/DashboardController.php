<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\hargajammain;
use App\Models\jam_main;
use App\Models\Meja;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'meja' => Meja::count(),
                'jam_main' => jam_main::count(),
                'harga' => hargajammain::count(),
            ],
            'version' => app()->version(),
        ]);
    }
}
