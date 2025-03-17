<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SeatController;

use App\Http\Controllers\EoTiketController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserPageController;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// Route::get('/hello', function () {
//     return view('test-livewire');
// });

// Guest Routes for Authentication
Route::middleware('guest')->group(function () {
    // Main Domain Login
    Route::domain(config('app.domain'))
        ->middleware('verify.maindomain') // Middleware applied at correct scope
        ->group(function () {
            Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');
        });

    // Subdomain Login
    Route::domain('{client}.' . config('app.domain'))
        ->middleware('verify.subdomain') // Middleware applied at correct scope
        ->group(function () {
            Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('client.login');
        });
});

// Midtrans Payment Callback 2
Route::post('/payment/callback', [PaymentController::class, 'callback']);

Route::domain(config('app.domain'))
    ->middleware('verify.maindomain')
    ->group(function () {
        // Socialite Authentication
        Route::controller(SocialiteController::class)
            ->group(function () {
                Route::get('/auth/google', 'googleLogin')
                    ->name('auth.google');
                Route::get('/auth/google-callback', 'googleAuthentication')
                    ->name('auth.google-authentication');
            });

        // Midtrans Payment Callback
        Route::post('/payment/midtranscallback', [PaymentController::class, 'midtransCallback'])
            ->name('payment.midtranscallback');

        // Session Debugging Route
        Route::get('/check-session', function () {
            return response()->json([
                'user' => Auth::user(),
                'session' => session()->all(),
            ]);
        });

        // Redirect Home to Tenant Dashboard
        Route::get('/', function () {})->name('home');

        // Seat Management Routes (Protected)
        Route::middleware('auth')->group(function () {
            // Seats
            Route::get('/seats', [SeatController::class, 'index'])
                ->name('seats.index');
            Route::get('/seats/grid-edit', [SeatController::class, 'gridEdit'])
                ->middleware('venue.access')
                ->name('seats.grid-edit');
            // Route::get('/seats/edit', [SeatController::class, 'edit'])->name('seats.edit');
            Route::get('/seats/edit', [SeatController::class, 'edit'])
                ->middleware('event.access')
                ->name('seats.edit');
            Route::post('/seats/update-layout', [SeatController::class, 'updateLayout'])
                ->name('seats.update-layout');
            Route::post('/seats/update', [SeatController::class, 'update'])
                ->name('seats.update');
            Route::post('/seats/update-event-seats', [SeatController::class, 'updateEventSeats'])
                ->name('seats.update-event-seats');
            Route::post('/seats/save-grid-layout', [SeatController::class, 'saveGridLayout'])
                ->name('seats.save-grid-layout');
        });

        // Any unregistered route will be redirected to the main domain
        Route::fallback(function () {
            return redirect()->route('home');
        });
    });

Route::domain('{client}.' . config('app.domain'))
    ->middleware('verify.subdomain')
    ->group(function () {
        // User Page
        Route::get('/', [UserPageController::class, 'landing'])
            ->name('client.home');
        Route::get('/my_tickets', [UserPageController::class, 'my_tickets'])
            ->name('client.my_tickets');
        Route::get('/events/{eventId}/tickets', [EoTiketController::class, 'show'])
            ->name('events.tickets.show');
        Route::get('/events/tickets', [EoTiketController::class, 'show'])
            ->name('events.tickets.index');
        // Profile
        Route::get('/profile', [ProfileController::class, 'edit'])
            ->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])
            ->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])
            ->name('profile.destroy');

        // Ticket
        Route::post('/payment/charge', [PaymentController::class, 'charge'])
            ->name('payment.charge');

        // Any unregistered route will be redirected to the client's home page
        Route::fallback(function () {
            return redirect()->route('client.home', ['client' => request()->client]);
        });
    });

require __DIR__ . '/auth.php';
