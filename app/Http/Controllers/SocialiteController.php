<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserContact;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;


class SocialiteController extends Controller
{
    public function googleLogin(string $client = "")
    {
        // dd($client);
        session(['client' => $client]);

        $redirect = Socialite::driver('google')->redirect();
        return $redirect;
    }

    public function googleAuthentication()
    {
        $client = session('client');
        try {
            $google_resp = Socialite::driver('google')->user();
            $google_user = $google_resp->user;

            $user = User::where('google_id', $google_resp->id)
                ->orWhere('email', $google_resp->email)
                ->first();

            if ($user) {
                Auth::login($user);

                return redirect()->route('client.home', ['client' => $client]);
            } else {
                DB::beginTransaction();
                // Socialite resp structure: (var: $google_resp)
                //   Laravel\Socialite\Two\User {#2066 ▼ // app/Http/Controllers/SocialiteController.php:38
                //   +id: "11XXX55722XXX15454XXX"
                //   +nickname: null
                //   +name: "Yitzhak Edmund Tio Manalu"
                //   +email: "yitzhaketmanalu@gmail.com"
                //   +avatar: "https://lh3.googleusercontent.com/a/ACXXXXIQhRZvDovtmXXXXKtZZsJXXX0QESO2Ni1XXXXBfRCCnXXXXXkm=sXXXc"
                //   +user: array:10 [▼
                //     "sub" => "115455572242215454635"
                //     "name" => "Yitzhak Edmund Tio Manalu"
                //     "given_name" => "Yitzhak"
                //     "family_name" => "Edmund Tio Manalu"
                //     "picture" => "https://lh3.googleusercontent.com/a/ACXXXXIQhRZvDovtmXXXXKtZZsJXXX0QESO2Ni1XXXXBfRCCnXXXXXkm=sXXXc"
                //     "email" => "yitzhaketmanalu@gmail.com"
                //     "email_verified" => true
                //     "id" => "11XXX55722XXX15454XXX"
                //     "verified_email" => true
                //     "link" => null
                //   ]
                //   +attributes: array:6 [▼
                //     "id" => "11XXX55722XXX15454XXX"
                //     "nickname" => null
                //     "name" => "Yitzhak Edmund Tio Manalu"
                //     "email" => "yitzhaketmanalu@gmail.com"
                //     "avatar" => "https://lh3.googleusercontent.com/a/ACXXXXIQhRZvDovtmXXXXKtZZsJXXX0QESO2Ni1XXXXBfRCCnXXXXXkm=sXXXc"
                //     "avatar_original" => "https://lh3.googleusercontent.com/a/ACXXXXIQhRZvDovtmXXXXKtZZsJXXX0QESO2Ni1XXXXBfRCCnXXXXXkm=sXXXc"
                //   ]
                //   +token: "ya29.aXXXXRPp5kUYqSAtkuzXXXXXbFdg3PNDXXXpzxZvrnQ02INToxNoXXXXXtcIoPHHsvCKiYY6o_FL-lXXXXyQuZPx4vS72-XXXfF0zm_PBdYfScSXXXX1zt9NK8B1AEv0BXTXXXXXpBn2e_d5OCr3kKMXXXA ▶"
                //   +refreshToken: null
                //   +expiresIn: 3599
                //   +approvedScopes: array:3 [▼
                //     0 => "openid"
                //     1 => "https://www.googleapis.com/auth/userinfo.profile"
                //     2 => "https://www.googleapis.com/auth/userinfo.email"
                //   ]
                // }

                // Make new user
                $userData = User::create([
                    'email' => $google_resp->email,
                    'password' => Hash::make($google_resp->id),
                    'role' => UserRole::USER,
                    'google_id' => $google_resp->id,
                    'first_name' => $google_user['given_name'],
                    'last_name' => $google_user['family_name'],
                    'email_verified_at' => now(),
                ]);

                if (!$userData) throw new Exception('Failed to create user');

                // Make user contact
                $userContact = UserContact::create([
                    'nickname' => $google_resp->nickname,
                    'fullname' => $google_resp->name,
                    'avatar' => $google_resp->avatar,
                    'phone_number' => null,
                    'email' => $google_resp->email,
                    'whatsapp_number' => null,
                    'instagram' => null,
                    'birth_date' => null,
                    'gender' => null,
                    'address' => null,
                ]);

                if (!$userContact) throw new Exception('Failed to create user contact');

                // Link
                $userData->contact_info = $userContact->contact_id;
                $userData->save();

                DB::commit();

                Auth::login($userData);
                return redirect()
                    ->route('client.home', ['client' => $client]);
            }
        } catch (Exception $e) {
            DB::rollBack();
            dd($e->getMessage());
            return redirect()
                ->route('auth.google')
                ->with('error', 'Google login failed: ' . $e->getMessage());
        }
    }
}
