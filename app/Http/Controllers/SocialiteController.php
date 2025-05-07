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

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

use App\Models\Traffic;
use Carbon\Carbon;
use PDO;

class SocialiteController extends Controller
{
    public function googleLogin(string $client = "")
    {
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
                $event = \App\Models\Event::where('slug', $client)->first();
                $path = storage_path("sql/events/{$event->id}.db");

                if (File::exists($path)) {
                    $pdo = new PDO("sqlite:" . $path);
                    $stmt = $pdo->prepare("INSERT INTO user_logs (user_id, status) VALUES (?, 'waiting')");
                    $stmt->execute([$user->id]);
                } else {
                    abort(404, 'Event database not found.');
                }

                // if ($event) {
                //     $trafficNumber = \App\Models\TrafficNumbersSlug::where('event_id', $event->id)->first();
                //     $trafficNumber->increment('active_sessions');
                //     $trafficNumber->save();
                // }

                Auth::login($user);

                return redirect()->route($client ? 'client.home' : 'home', ['client' => $client]);
            } else {
                dd("no");
                DB::beginTransaction();
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
