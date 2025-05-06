<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\UserContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

use App\Models\Traffic;
use Carbon\Carbon;



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

            $user = User::where('email', $google_resp->email)
                ->first();

            $userExists = $user !== null;
            $userHasCorrectGoogleId = $userExists && $user->google_id === $google_resp->id;

            if ($userHasCorrectGoogleId) {
                session([
                    'auth_user' => $user,
                ]);

                Auth::login($user);

                Traffic::create([
                    'user_id' => $user->id,
                    'start_login' => Carbon::now()->format('H:i:s'),
                    'end_login' => Carbon::now()->addMinutes(1)->format('H:i:s'), // 1 menit sesi
                    'stop_at' => null,
                ]);

                $event = \App\Models\Event::where('slug', $client)->first();

                if ($event) {
                    $trafficNumber = \App\Models\TrafficNumbersSlug::where('event_id', $event->id)->first();
                    $trafficNumber->increment('active_sessions');
                }

                return redirect()->route($client ? 'client.home' : 'home', ['client' => $client]);
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

                // Check if $google_user has given_name and family_name
                $given_name = $google_user['given_name'] ?? null;
                $family_name = $google_user['family_name'] ?? null;

                $userBody = [
                    'email' => $userExists ? $user->email : $google_resp->email,
                    'password' => $userExists ? $user->password : Hash::make($google_resp->id), // If user exists, keep the original password
                    'role' => $userExists ? $user->role : UserRole::USER,
                    'google_id' => $google_resp->id,
                    'first_name' => $userExists ? $user->first_name : $given_name,
                    'last_name' => $userExists ? $user->last_name : $family_name,
                    'email_verified_at' => $userExists ? $user->email_verified_at : now(),
                ];

                // Do new user
                $userData = null;
                if ($userExists) {
                    $userData = $user;
                    $userData->update($userBody);
                } else {
                    $userData = User::create($userBody);
                    if (!$userData) throw new Exception('Failed to create user');
                }

                // Do user contact
                $userContactBody = [
                    'nickname' => $userExists ? $user->contactInfo->nickname ?? $google_resp->nickname : $google_resp->nickname,
                    'fullname' => $userExists ? $user->contactInfo->fullname ?? $google_resp->name : $google_resp->name,
                    'avatar' => $userExists ? $user->contactInfo->avatar ?? $google_resp->avatar : $google_resp->avatar,
                    'phone_number' => $userExists ? $user->contactInfo->phone_number : null,
                    'email' => $userExists ? $user->contactInfo->email ?? $google_resp->email : $google_resp->email,
                    'whatsapp_number' => $userExists ? $user->contactInfo->whatsapp_number : null,
                    'instagram' => $userExists ? $user->contactInfo->instagram : null,
                    'birth_date' => $userExists ? $user->contactInfo->birth_date : null,
                    'gender' => $userExists ? $user->contactInfo->gender : null,
                    'address' => $userExists ? $user->contactInfo->address : null,
                ];

                $userContact = null;
                if ($userExists) {
                    $userContact = UserContact::find($userData->contactInfo->id);
                    $userContact->update($userContactBody);
                } else {
                    $userContact = UserContact::create($userContactBody);

                    if (!$userContact) throw new Exception('Failed to create user contact');

                    // Link
                    $userData->contact_info = $userContact->id;
                    $userData->save();
                }

                DB::commit();

                session([
                    'auth_user' => $userData,
                ]);

                Auth::login($userData);
                return redirect()->route($client ? 'client.home' : 'home', ['client' => $client]);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()
                ->route('auth.google')
                ->with('error', 'Google login failed: ' . $e->getMessage());
        }
    }
}
