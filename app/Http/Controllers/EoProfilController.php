<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;

class EoProfilController extends Controller
{
    public function pengaturan()
    {
        return Inertia::render('EventOrganizer/EoProfil/Index', [
            'title' => 'Tiket',
            'subtitle' => 'Pengatauran',
        ]);
    }

    public function edit()
    {
        return Inertia::render('EventOrganizer/EoProfil/EditProfil', [
            'title' => 'Tiket',
            'subtitle' => 'Pengatauran',
        ]);
    }
}
