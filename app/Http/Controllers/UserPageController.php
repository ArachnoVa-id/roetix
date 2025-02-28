<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserPageController extends Controller
{
    public function landing(string $client = '')
    {
        if (Auth::check()) {
            return Inertia::render('User/Landing', [
                'client' => $client
            ]);
        } else {
            return Inertia::render('User/Auth', [
                'client' => $client
            ]);
        }
    }

    public function my_tickets()
    {
        return Inertia::render('User/MyTickets', []);
    }
}
