<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;


class EoVenueController extends Controller
{
    public function index()
    {
        return Inertia::render('EventOrganizer/EoVenue/Index', [
            'title' => 'Venue',
            'subtitle' => 'Dafar',
        ]);
    }

    public function sewa()
    {
        return Inertia::render('EventOrganizer/EoVenue/SewaVenue', [
            'title' => 'Venue',
            'subtitle' => 'Sewa',
        ]);
    }

    public function pengaturan()
    {
        return Inertia::render('EventOrganizer/EoVenue/PengaturanVenue', [
            'title' => 'Venue',
            'subtitle' => 'Pengaturan',
        ]);
    }
}
