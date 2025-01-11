<?php

use App\Http\Controllers\VenueController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\SeatAssignmentController;
use App\Http\Controllers\TicketCategoryController;
use App\Http\Controllers\TransactionHistoryController;
use App\Http\Controllers\SeatTransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Venue routes
    Route::get('/venues/{venueId}', [VenueController::class, 'show']);
    Route::patch('/venues/{venueId}/status', [VenueController::class, 'updateStatus']);

    // Seat routes
    Route::get('/venues/{venueId}/seats', [SeatController::class, 'index']);
    Route::patch('/seats/{seatId}/status', [SeatController::class, 'updateStatus']);
    Route::post('/seats/bulk-update', [SeatController::class, 'bulkUpdate']);

    // Seat Assignment routes
    Route::post('/seats/assign-category', [SeatAssignmentController::class, 'assignCategory']);
    Route::post('/seats/bulk-assign-categories', [SeatAssignmentController::class, 'bulkAssignCategories']);
    Route::get('/venues/{venueId}/seat-assignments', [SeatAssignmentController::class, 'getAssignments']);

    // Ticket Category Routes
    Route::get('/events/{eventId}/ticket-categories', [TicketCategoryController::class, 'index']);
    Route::post('/events/{eventId}/ticket-categories', [TicketCategoryController::class, 'store']);
    Route::put('/ticket-categories/{categoryId}', [TicketCategoryController::class, 'update']);

    // Timebound Price Routes
    Route::post('/ticket-categories/{categoryId}/prices', [TicketCategoryController::class, 'addPrice']);
    Route::put('/ticket-categories/prices/{priceId}', [TicketCategoryController::class, 'updatePrice']);
    Route::delete('/ticket-categories/prices/{priceId}', [TicketCategoryController::class, 'deletePrice']);
    Route::get('/ticket-categories/{categoryId}/current-price', [TicketCategoryController::class, 'getCurrentPrice']);
    Route::get('/ticket-categories/{categoryId}/price-history', [TicketCategoryController::class, 'getPriceHistory']);

    // Transaction History Routes
    Route::get('/users/{userId}/transactions', [TransactionHistoryController::class, 'getUserTransactions']);
    Route::get('/venues/{venueId}/transactions', [TransactionHistoryController::class, 'getVenueTransactions']);
    Route::get('/venues/{venueId}/transaction-statistics', [TransactionHistoryController::class, 'getTransactionStatistics']);

    // Seat Transaction Routes
    Route::post('/seat-transactions', [SeatTransactionController::class, 'create']);
    Route::post('/seat-transactions/{seatId}/release', [SeatTransactionController::class, 'release']);
    Route::post('/seat-transactions/confirm', [SeatTransactionController::class, 'confirm']);
    Route::post('/seat-transactions/clear-expired', [SeatTransactionController::class, 'clearExpiredTransactions'])
        ->middleware('admin');
});