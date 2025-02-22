<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class EoTiketController extends Controller
{
    public function pengaturan()
    {
        return Inertia::render('EventOrganizer/EoTiket/PengaturanTiker', [
            'title' => 'Tiket',
            'subtitle' => 'Pengatauran',
        ]);
    }

    public function harga()
    {
        return Inertia::render('EventOrganizer/EoTiket/PengaturanTiker', [
            'title' => 'Tiket',
            'subtitle' => 'harga',
        ]);
    }

    public function verifikasi()
    {
        return Inertia::render('EventOrganizer/EoTiket/PengaturanTiker', [
            'title' => 'Tiket',
            'subtitle' => 'verivikasi',
        ]);
    }
}
