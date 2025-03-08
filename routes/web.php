<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeatController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\UserPageController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\EoTiketController;

Route::domain('{client}.' . config('app.domain'))->group(function () {
    Route::get('/', [UserPageController::class, 'landing'])
        ->name('client.home')
        ->middleware('auth')
    ;
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('client.login')
        ->middleware('guest');
    Route::get('/my_tickets', [UserPageController::class, 'my_tickets'])->name('client.my_tickets');

    Route::get('/events/{eventId}/tickets', [EoTiketController::class, 'show'])->name('events.tickets.show');
    Route::get('/events/tickets', [EoTiketController::class, 'show'])->name('events.tickets.index');
    Route::post('/payment/charge', [PaymentController::class, 'createCharge'])->name('payment.charge');
});

Route::controller(SocialiteController::class)->group(function () {
    Route::get('/auth/google', 'googleLogin')->name('auth.google');
    Route::get('/auth/google-callback', 'googleAuthentication')->name('auth.google-authentication');
});

Route::post('/payment/midtranscallback', [PaymentController::class, 'midtransCallback'])->name('payment.midtranscallback');

Route::get('/', function () {
    $user = Auth::user();

    $user = User::find($user->user_id);

    $firstTeam = optional($user->teams()->first())->name;

    return redirect()->route('filament.admin.pages.dashboard', ['tenant' => $firstTeam]);
})->name('home')
    ->middleware('auth');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->name('login')
    ->middleware('guest');

Route::get('/seats', [SeatController::class, 'index'])->name('seats.index');
Route::get('/seats/edit', [SeatController::class, 'edit'])->name('seats.edit');
Route::post('/seats/update-event-seats', [SeatController::class, 'updateEventSeats'])->name('seats.update-event-seats');
Route::post('/seats/update', [SeatController::class, 'update'])->name('seats.update');
Route::get('/seats/spreadsheet', [SeatController::class, 'spreadsheet'])->name('seats.spreadsheet');
Route::get('/seats/grid-edit', [SeatController::class, 'gridEdit'])->name('seats.grid-edit');
Route::post('/seats/update-layout', [SeatController::class, 'updateLayout'])->name('seats.update-layout');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/check-session', function () {
    return response()->json([
        'user' => Auth::user(),
        'session' => session()->all(),
    ]);
});


require __DIR__ . '/auth.php';
