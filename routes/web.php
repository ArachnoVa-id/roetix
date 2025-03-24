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
use App\Http\Controllers\TicketController;

// Guest Routes for Authentication
Route::middleware('guest')->group(function () {
    // Main Domain Login
    Route::domain(config('app.domain'))
        ->middleware('verify.maindomain')
        ->group(function () {
            Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');
        });

    // Subdomain Login
    Route::domain('{client}.' . config('app.domain'))
        ->middleware('verify.subdomain')
        ->group(function () {
            Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('client.login');
        });
});

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

        // Session Debugging Route
        Route::get('/check-session', function () {
            return response()->json([
                'user' => Auth::user(),
                'session' => session()->all(),
            ]);
        });

        // Redirect Home to Tenant Dashboard
        Route::get('/', function () {
            if (!Auth::check()) {
                return redirect()->route('login');
            }

            $user = Auth::user();
            $userInModel = User::find($user->user_id);

            if ($userInModel?->role === 'user') {
                Auth::logout();
                return redirect()->route('login');
            }

            return ($team = $userInModel?->teams()->first())
                ? redirect()->route('filament.admin.pages.dashboard', ['tenant' => $team->code])
                : redirect()->route('login');
        })->name('home');

        // Seat Management Routes (Protected)
        Route::middleware('auth')->group(function () {
            // Seats
            Route::get('/seats', [SeatController::class, 'index'])
                ->name('seats.index');
            Route::get('/seats/grid-edit', [SeatController::class, 'gridEdit'])
                ->middleware('venue.access')
                ->name('seats.grid-edit');
            Route::get('/seats/edit', [SeatController::class, 'edit'])
                ->middleware('event.access')
                ->name('seats.edit');
            // Post handlers
            Route::post('/seats/update-layout', [SeatController::class, 'updateLayout'])
                ->name('seats.update-layout');
            Route::post('/seats/update', [SeatController::class, 'update'])
                ->name('seats.update');
            Route::post('/seats/update-event-seats', [SeatController::class, 'updateEventSeats'])
                ->name('seats.update-event-seats');
            Route::post('/seats/save-grid-layout', [SeatController::class, 'saveGridLayout'])
                ->name('seats.save-grid-layout');
            // Route::prefix('api')->group(function () {
            //     Route::get('/events/{eventId}/timelines', [SeatController::class, 'getEventTimelines'])
            //         ->name('api.events.timelines');
            // });
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

        // Fix: Add auth middleware to my_tickets route to ensure user authentication
        Route::middleware('auth')->group(function () {
            Route::get('/my_tickets', [UserPageController::class, 'my_tickets'])
                ->name('client.my_tickets');
            Route::prefix('api')->group(function () {
                // Use a simple GET route with no path parameters
                Route::get('tickets/download', [TicketController::class, 'downloadTicket'])
                    ->name('api.tickets.download');

                Route::get('tickets/download-all', [TicketController::class, 'downloadAllTickets'])
                    ->name('api.tickets.download-all');
            });
            Route::get('/api/pending-transactions', [PaymentController::class, 'getPendingTransactions'])
                ->name('api.pending-transactions');

            Route::post('/payment/resume', [PaymentController::class, 'resumePayment'])
                ->name('payment.resume');
        });

        Route::get('/events/{eventId}/tickets', [EoTiketController::class, 'show'])
            ->name('events.tickets.show');
        Route::get('/events/tickets', [EoTiketController::class, 'show'])
            ->name('events.tickets.index');

        // Profile
        Route::get('/profile', [ProfileController::class, 'edit'])
            ->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])
            ->name('profile.update');
        Route::put('/profile', [ProfileController::class, 'updatePassword'])
            ->name('profile.password_update');
        Route::put('/profile', [ProfileController::class, 'updateContact'])
            ->name('profile.contact_update');
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
