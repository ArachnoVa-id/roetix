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
            $userInModel = User::find($user->id);

            // Detect if the request is from a subdomain
            $subdomain = request()->route('client');

            // If user is in a subdomain, do not redirect to admin panel
            if ($subdomain) {
                return redirect()->route('client.home', ['client' => $subdomain]);
            }

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
            Route::controller(SeatController::class)
                ->group(function () {
                    // Seats
                    Route::get('/seats', 'index')
                        ->name('seats.index');
                    Route::get('/seats/grid-edit', 'gridEdit')
                        ->middleware('venue.access')
                        ->name('seats.grid-edit');
                    Route::get('/seats/edit', 'edit')
                        ->middleware('event.access')
                        ->name('seats.edit');

                    // Post handlers
                    Route::post('/seats/update-layout', 'updateLayout')
                        ->name('seats.update-layout');
                    Route::post('/seats/update', 'update')
                        ->name('seats.update');
                    Route::post('/seats/update-event-seats', 'updateEventSeats')
                        ->name('seats.update-event-seats');
                    Route::post('/seats/save-grid-layout', 'saveGridLayout')
                        ->name('seats.save-grid-layout');
                });
        });

        // Any unregistered route will be redirected to the main domain
        Route::fallback(function () {
            return redirect()->route('home');
        });
    });

Route::domain('{client}.' . config('app.domain'))
    ->middleware('verify.subdomain')
    ->group(function () {
        // Socialite Authentication
        Route::controller(SocialiteController::class)
            ->group(function () {
                Route::get('/auth/google', 'googleLogin')
                    ->name('client-auth.google');
            });

        // User Page
        Route::controller(UserPageController::class)
            ->group(function () {
                // Home Page
                Route::get('/', 'landing')
                    ->name('client.home');
                Route::post('/verify-event-password', 'verifyEventPassword')
                    ->name('client.verify-event-password');
                // Privacy Policy and Terms & Conditions pages
                Route::get('/privacy-policy', 'privacyPolicy')
                    ->name('client.privacy_policy');
                Route::get('/terms-conditions', 'termCondition')
                    ->name('client.terms_conditions');
            });

        // Fix: Add auth middleware to my_tickets route to ensure user authentication
        Route::middleware('auth')->group(function () {
            Route::get('/my_tickets', [UserPageController::class, 'my_tickets'])
                ->name('client.my_tickets');

            Route::prefix('api')->group(function () {
                // Use a simple GET route with no path parameters
                Route::controller(TicketController::class)
                    ->group(function () {
                        Route::get('tickets/download', 'downloadTicket')
                            ->name('api.tickets.download');

                        Route::get('tickets/download-all', 'downloadAllTickets')
                            ->name('api.tickets.download-all');
                    });
            });

            Route::controller(PaymentController::class)
                ->group(function () {
                    Route::get('/api/pending-transactions', 'getPendingTransactions')
                        ->name('api.pending-transactions');

                    Route::post('/payment/resume', 'resumePayment')
                        ->name('payment.resume');
                });
        });

        // Event Tickets
        Route::controller(EoTiketController::class)
            ->group(function () {
                Route::get('/events/{eventId}/tickets', 'show')
                    ->name('events.tickets.show');
                Route::get('/events/tickets', 'index')
                    ->name('events.tickets.index');
            });

        // Profile
        Route::controller(ProfileController::class)
            ->group(function () {
                Route::get('/profile', 'edit')
                    ->name('profile.edit');
                Route::patch('/profile', 'update')
                    ->name('profile.update');
                Route::put('/profile', 'updatePassword')
                    ->name('profile.password_update');
                Route::put('/profile', 'updateContact')
                    ->name('profile.contact_update');
                Route::delete('/profile', 'destroy')
                    ->name('profile.destroy');
            });

        // Ticket
        Route::post('/payment/charge', [PaymentController::class, 'charge'])
            ->name('payment.charge');

        // Any unregistered route will be redirected to the client's home page
        Route::fallback(function () {
            return redirect()->route('client.home', ['client' => request()->client]);
        });
    });

require __DIR__ . '/auth.php';
