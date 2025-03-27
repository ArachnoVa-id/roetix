<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;


class SocialiteController extends Controller
{
    public function googleLogin($client)
    {
        // dd(request()->fullUrl());
        session(['client' => $client]);
        return Socialite::driver('google')->redirect();
    }

    public function googleAuthentication()
    {
        $client = session('client');

        try {
            $google_user = Socialite::driver('google')->user();

            $user = User::where('google_id', $google_user->id)
                        ->orWhere('email', $google_user->email)
                        ->first();

            if ($user) {
                Auth::login($user);

                return redirect()->route('client.home', ['client' => $client]);
            } else {
                $userdata = User::create([
                    'id' => (string) Str::uuid(),
                    'email' => $google_user->email,
                    'password' => Hash::make('hallopwd123'),
                    'google_id' => $google_user->id,
                    'first_name' => 'tets',
                    'last_name' => 'test',
                ]);

                if ($userdata) {
                    Auth::login($userdata);
                    return redirect()->route('client.home', $client);
                }
            }
        } catch (Exception $e) {
            return redirect()->route('auth.google')->with('error', 'Google login failed: ' . $e->getMessage());
        }
    }
}
