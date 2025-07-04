<?php

use Inertia\Inertia;
use Illuminate\Support\Str;
use App\Models\EventVariables;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserPageController;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\TicketScanController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::domain(config('app.domain'))
    ->middleware('verify.maindomain')
    ->group(function () {
        // Auth
        Route::get('login', [AuthenticatedSessionController::class, 'login'])
            ->name('login');

        // Socialite Authentication
        Route::controller(SocialiteController::class)->group(function () {
            Route::get('/auth/google', 'googleLogin')
                ->name('auth.google');
            Route::get('/auth/google-callback', 'googleAuthentication')
                ->name('auth.google-authentication');
        });

        // Redirect Home to Tenant Dashboard
        if (config('app.name') !== 'NovaTix') {
            Route::get('/', [
                UserPageController::class,
                Str::lower(config('app.name')) . 'Landing'

            ])->name('home');
        } else
            Route::get('/', function () {
                return redirect()->route('login');
            })->name('home');

        // Privacy Policy and Terms & Conditions pages
        Route::get('/privacy-policy', function () {
            return Inertia::render('Legality/privacypolicy/PrivacyPolicy', [
                'props' => EventVariables::getDefaultValue()
            ]);
        })
            ->name('privacy_policy');

        Route::get('/terms-conditions', function () {
            return Inertia::render('Legality/termcondition/TermCondition', [
                'props' => EventVariables::getDefaultValue()
            ]);
        })
            ->name('terms_conditions');

        // Seat Management Routes (Protected)
        Route::middleware('auth')->group(function () {
            Route::controller(SeatController::class)->group(function () {
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

                // Export layout
                Route::get('/venues/{venue}', 'exportMap')
                    ->name('venues.export');
            });

            // Download Orders
            Route::controller(PaymentController::class)->group(function () {
                Route::get('/orders/download/{id?}', 'ordersDownload')
                    ->name('orders.export');
            });
        });

        // Any unregistered route will be redirected to the main domain
        Route::fallback(function () {
            return redirect()->route('home');
        });
    });

Route::domain('{client}.' . config('app.domain'))
    ->middleware(['verify.subdomain'])
    ->group(function () {
        // Auth
        Route::get('login/{message?}', [AuthenticatedSessionController::class, 'login'])
            ->name('client.login');
        Route::get('privateLogin', [AuthenticatedSessionController::class, 'privateLogin'])
            ->name('client.privateLogin');

        // Socialite Authentication
        Route::controller(SocialiteController::class)->group(function () {
            Route::get('/auth/google', 'googleLogin')
                ->name('client-auth.google');
        });

        Route::post('/verify-event-password', [UserPageController::class, 'verifyEventPassword'])
            ->middleware('event.props')
            ->name('client.verify-event-password');

        // User Page
        Route::middleware(['event.props', 'event.maintenance', 'event.lock', 'user.queue'])->group(function () {
            Route::controller(UserPageController::class)->group(function () {
                // Home Page
                Route::get('/', 'landing')
                    ->name('client.home');

                // Privacy Policy and Terms & Conditions pages
                Route::get('/privacy-policy', 'privacyPolicy')
                    ->name('client.privacy_policy');
                Route::get('/terms-conditions', 'termCondition')
                    ->name('client.terms_conditions');
            });

            // Fix: Add auth middleware to my_tickets route to ensure user authentication
            Route::middleware('auth.client')->group(function () {
                Route::get('/my_tickets', [UserPageController::class, 'my_tickets'])
                    ->name('client.my_tickets');

                // FOR THE SCAN PAGE (show method)
                Route::get('/scan', [TicketScanController::class, 'show'])->name('client.scan');

                // FOR THE TICKET VALIDATION (validate method, e.g., POST request)
                Route::post('/scan/validate', [TicketScanController::class, 'validateTicket'])->name('client.scan.validate');

                // FOR THE SCAN ACTION (store method, e.g., POST request)
                Route::post('/scan', [TicketScanController::class, 'scan'])->name('client.scan.store');

                // FOR THE HISTORY (get method)
                Route::get('/scanned-history', [TicketScanController::class, 'getScannedHistory'])->name('client.scanned.history');

                Route::prefix('api')->group(function () {
                    // Use a simple GET route with no path parameters
                    Route::controller(TicketController::class)
                        ->group(function () {
                            Route::get('tickets/download', 'downloadTickets')
                                ->name('api.tickets.download');
                        });

                    Route::controller(PaymentController::class)
                        ->group(function () {
                            Route::get('payment/pending', 'getPendingTransactions')
                                ->name('payment.pending');

                            // Route::post('payment/cancel', 'cancelPendingTransactions')
                            //     ->name('payment.cancel');

                            Route::get('payment/get-client', 'fetchMidtransClientKey')
                                ->name('payment.get-client');

                            // Ticket
                            Route::post('payment/charge', 'charge')
                                ->name('payment.charge');
                        });
                });
            });

            // Profile
            Route::controller(ProfileController::class)
                ->group(function () {
                    // Route::get('/profile', 'edit')
                    //     ->name('profile.edit');


                    // Route::patch('/profile', 'update')
                    //     ->name('profile.update');
                    // Route::put('/profile', 'updatePassword')
                    //     ->name('profile.password_update');


                    // Route::put('/profile', 'updateContact')
                    //     ->name('profile.contact_update');


                    // Route::delete('/profile', 'destroy')
                    //     ->name('profile.destroy');
                });
        });

        // Any unregistered route will be redirected to the client's home page
        Route::fallback(function () {
            return redirect()->route('client.home', ['client' => request()->client]);
        });
    });

require __DIR__ . '/auth.php';
