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

Route::domain('{client}.' . config('app.domain'))->group(function () {
    Route::get('/', [UserPageController::class, 'landing'])->name('client.home');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('client.login');
    Route::get('/my_tickets', [UserPageController::class, 'my_tickets'])->name('client.my_tickets');
    Route::post('/payment/charge', [PaymentController::class, 'createCharge'])->name('payment.charge');
    Route::post('/payment/midtranscallback', [PaymentController::class, 'midtransCallback'])->name('payment.midtranscallback');
});

Route::controller(SocialiteController::class)->group(function () {
    Route::get('/auth/google', 'googleLogin')->name('auth.google');
    Route::get('/auth/google-callback', 'googleAuthentication')->name('auth.google-authentication');
});

Route::get('/', function () {
    $user = Auth::user();

    if ($user) {
        $userModel = User::find($user->user_id);

        $firstTeam = $userModel->teams()->first();

        return redirect()->to(route('filament.admin.pages.dashboard', ['tenant' => $firstTeam->name]));
    } else {
        return redirect()->route('login');
    }
})->name('home');

Route::get('/login', function () {
    $user = Auth::user();

    if ($user) {
        $userModel = User::find($user->user_id);

        $firstTeam = $userModel->teams()->first();

        if ($userModel->role == 'admin') {
            return redirect()->to(route('filament.novatix-admin.pages.dashboard'));
        }

        return redirect()->to(route('filament.admin.pages.dashboard', ['tenant' => $firstTeam->name]));
        
    } else {
        $page = new AuthenticatedSessionController();
        return $page->create();
    }
})->name('login');

Route::get('/seats', [SeatController::class, 'index'])->name('seats.index');
Route::get('/seats/edit', [SeatController::class, 'edit'])->name('seats.edit');
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
