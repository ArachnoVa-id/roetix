<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;


class EoAcaraController extends Controller
{
    public function index()
    {
        return Inertia::render('EventOrganizer/EoAcara/Index', [
            'title' => 'Acara',
            'subtitle' => 'Dafar',
        ]);
    }

    public function create()
    {
        return Inertia::render('EventOrganizer/EoAcara/CreateAcara', [
            'title' => 'Acara',
            'subtitle' => 'Buat Acara',
        ]);
    }

    public function edit()
    {
        return Inertia::render('EventOrganizer/EoAcara/EditAcara', [
            'title' => 'Acara',
            'subtitle' => 'Edit Acara',
        ]);
    }
}
