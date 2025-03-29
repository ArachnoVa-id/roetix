<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactUpdateRequest;
use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Event;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request, string $client = ''): Response
    {
        // Get the event and associated venue
        $event = Event::where('slug', $client)
            ->first();

        $props = $event->eventVariables;

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'client' => $client,
            'props' => $props,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, string $client = ''): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit', ['client' => $client]);
    }

    public function updatePassword(PasswordUpdateRequest $request, string $client = ''): RedirectResponse
    {
        $request->user()->update([
            'password' => bcrypt($request->password),
        ]);

        return Redirect::route('profile.edit', ['client' => $client]);
    }

    public function updateContact(ContactUpdateRequest $request, string $client = ''): RedirectResponse
    {
        $request->user()->contactInfo()->update($request->validated());

        return Redirect::route('profile.edit', ['client' => $client]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request, string $client): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::route('login', ['client' => $client]);
    }
}
