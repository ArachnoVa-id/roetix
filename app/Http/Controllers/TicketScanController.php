<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Models\User;
use App\Enums\TicketOrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TicketScanController extends Controller
{
    public function show(Request $request, string $client): InertiaResponse | \Illuminate\Http\RedirectResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Log::warning('Unauthenticated attempt to access scan page.', ['client' => $client]);
                return redirect()->route('client.login', ['client' => $client])
                    ->with('error', 'Please login to access this page.');
            }

            $userModel = User::find($user->id);
            if (!$userModel || !$userModel->isReceptionist()) {
                Log::warning('Unauthorized attempt to access scan page.', [
                    'user_id' => $user->id,
                    'user_role' => $userModel?->role ?? 'unknown',
                    'client' => $client
                ]);
                return redirect()->route('client.home', ['client' => $client])
                    ->with('error', 'You do not have permission to access this page.');
            }

            $event = Event::where('slug', $client)->first();

            if (!$event) {
                Log::error('Event not found for scanning.', [
                    'event_slug' => $client,
                    'client' => $client,
                    'user_id' => $user->id
                ]);
                return redirect()->route('client.home', ['client' => $client])
                    ->with('error', 'Event not found.');
            }

            $eventVariables = $event->eventVariables;
            if ($eventVariables) {
                $eventVariables->reconstructImgLinks();
            } else {
                $eventVariables = \App\Models\EventVariables::getDefaultValue();
            }

            return Inertia::render('Receptionist/ScanTicket', [
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'slug' => $event->slug,
                    'location' => $event->location,
                ],
                'client' => $client,
                'props' => $eventVariables->getSecure(),
                'appName' => config('app.name'),
                'userEndSessionDatetime' => null,
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading scan page: ' . $e->getMessage(), [
                'client' => $client,
                'user_id' => $user?->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('client.home', ['client' => $client])
                ->with('error', 'An error occurred while loading the scan page.');
        }
    }

    public function validateTicket(Request $request, string $client): JsonResponse
    {
        Log::info('validateTicket method called', [
            'client' => $client,
            'request_data' => $request->all()
        ]);

        try {
            $user = Auth::user();

            if (!$user) {
                Log::warning('Unauthenticated validation attempt', ['client' => $client]);
                return response()->json([
                    'message' => 'Authentication required.',
                    'error' => 'UNAUTHENTICATED'
                ], 401);
            }

            $userModel = User::find($user->id);
            if (!$userModel || !$userModel->isReceptionist()) {
                Log::warning('Unauthorized validation attempt', [
                    'user_id' => $user->id,
                    'user_role' => $userModel?->role ?? 'unknown',
                    'client' => $client
                ]);
                return response()->json([
                    'message' => 'You do not have permission to validate tickets.',
                    'error' => 'UNAUTHORIZED'
                ], 403);
            }

            // Validasi input
            try {
                $request->validate([
                    'ticket_code' => 'required|string|max:255|min:1',
                    'event_slug' => 'required|string|max:255|min:1',
                ]);
            } catch (ValidationException $e) {
                Log::error('Validation failed in validateTicket', [
                    'errors' => $e->errors(),
                    'request_data' => $request->all()
                ]);
                return response()->json([
                    'message' => 'Invalid input data.',
                    'error' => 'VALIDATION_ERROR',
                    'details' => $e->errors()
                ], 422);
            }

            $ticketCode = trim($request->input('ticket_code'));
            $eventSlug = trim($request->input('event_slug'));

            Log::info('Processing ticket validation', [
                'ticket_code' => $ticketCode,
                'event_slug' => $eventSlug,
                'client' => $client
            ]);

            $event = Event::where('slug', $client)->first();
            if (!$event) {
                Log::error('Event not found for validation', [
                    'client' => $client,
                    'event_slug' => $eventSlug
                ]);
                return response()->json([
                    'message' => 'Event not found.',
                    'error' => 'EVENT_NOT_FOUND'
                ], 404);
            }

            Log::info('Event found', [
                'event_id' => $event->id,
                'event_name' => $event->name
            ]);

            // Cari ticket dengan relasi
            $ticket = Ticket::where('ticket_code', $ticketCode)
                ->where('event_id', $event->id)
                ->with(['ticketCategory', 'ticketOrders.order.user'])
                ->first();

            if (!$ticket) {
                Log::info('Ticket not found', [
                    'ticket_code' => $ticketCode,
                    'event_id' => $event->id
                ]);
                return response()->json([
                    'message' => 'Ticket not found or not valid for this event.',
                    'error' => 'TICKET_NOT_FOUND'
                ], 404);
            }

            Log::info('Ticket found', [
                'ticket_id' => $ticket->id,
                'ticket_code' => $ticket->ticket_code
            ]);

            // Cek ticket order
            $ticketOrder = $ticket->ticketOrders()
                ->where('status', '!=', TicketOrderStatus::SCANNED->value)
                ->orderByDesc('created_at')
                ->first();

            if (!$ticketOrder) {
                $alreadyScannedOrder = $ticket->ticketOrders()
                    ->where('status', TicketOrderStatus::SCANNED->value)
                    ->orderByDesc('created_at')
                    ->first();

                if ($alreadyScannedOrder) {
                    Log::info('Ticket already scanned', [
                        'ticket_code' => $ticketCode,
                        'scanned_at' => $alreadyScannedOrder->scanned_at
                    ]);
                    return response()->json([
                        'message' => "Ticket {$ticketCode} has already been scanned.",
                        'error' => 'ALREADY_SCANNED',
                        'data' => [
                            'ticket_code' => $ticket->ticket_code,
                            'attendee_name' => $ticket->attendee_name ?? null,
                            'ticket_type' => $ticket->ticketCategory->name ?? 'N/A',
                            'scanned_at' => $alreadyScannedOrder->scanned_at?->toIso8601String(),
                            'status' => 'already_scanned'
                        ]
                    ], 409);
                }

                Log::warning('No valid ticket order found', [
                    'ticket_id' => $ticket->id,
                    'ticket_code' => $ticketCode
                ]);
                return response()->json([
                    'message' => 'Ticket order not found or not in a scannable state.',
                    'error' => 'ORDER_NOT_FOUND_OR_INVALID_STATE'
                ], 404);
            }

            Log::info('Valid ticket order found', [
                'ticket_order_id' => $ticketOrder->id,
                'order_id' => $ticketOrder->order_id
            ]);

            // Return ticket information for confirmation
            $responseData = [
                'ticket_code' => $ticket->ticket_code,
                'attendee_name' => $ticket->attendee_name ?? null,
                'ticket_type' => $ticket->ticketCategory->name ?? 'N/A',
                'order_code' => $ticketOrder->order->order_code ?? null,
                'buyer_email' => $ticketOrder->order->user->email ?? null,
                'status' => 'valid_for_scan'
            ];

            Log::info('Returning validation success', ['data' => $responseData]);

            return response()->json([
                'message' => 'Ticket validation successful',
                'data' => $responseData
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error validating ticket for event {$client}: " . $e->getMessage(), [
                'ticket_code' => $request->input('ticket_code'),
                'event_slug' => $request->input('event_slug'),
                'client' => $client,
                'user_id' => $user?->id ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred while validating the ticket.',
                'error' => 'SERVER_ERROR',
                'debug' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    public function scan(Request $request, string $client): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'Authentication required.',
                    'error' => 'UNAUTHENTICATED'
                ], 401);
            }

            $userModel = User::find($user->id);
            if (!$userModel || !$userModel->isReceptionist()) {
                Log::warning('Unauthorized scan attempt.', [
                    'user_id' => $user->id,
                    'user_role' => $userModel?->role ?? 'unknown',
                    'client' => $client
                ]);
                return response()->json([
                    'message' => 'You do not have permission to scan tickets.',
                    'error' => 'UNAUTHORIZED'
                ], 403);
            }

            $request->validate([
                'ticket_code' => 'required|string|max:255|min:1',
                'event_slug' => 'required|string|max:255|min:1',
            ]);

            $ticketCode = trim($request->input('ticket_code'));

            $event = Event::where('slug', $client)->first();
            if (!$event) {
                return response()->json([
                    'message' => 'Event not found.',
                    'error' => 'EVENT_NOT_FOUND'
                ], 404);
            }

            DB::beginTransaction();

            try {
                $ticket = Ticket::where('ticket_code', $ticketCode)
                    ->where('event_id', $event->id)
                    ->lockForUpdate()
                    ->first();

                if (!$ticket) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Ticket not found or not valid for this event.',
                        'error' => 'TICKET_NOT_FOUND'
                    ], 404);
                }

                $ticketOrder = $ticket->ticketOrders()
                    ->where('status', '!=', TicketOrderStatus::SCANNED->value)
                    ->orderByDesc('created_at')
                    ->first();

                if (!$ticketOrder) {
                    $alreadyScannedOrder = $ticket->ticketOrders()
                        ->where('status', TicketOrderStatus::SCANNED->value)
                        ->orderByDesc('created_at')
                        ->first();

                    if ($alreadyScannedOrder) {
                        DB::rollBack();
                        return response()->json([
                            'message' => "Ticket {$ticketCode} has already been scanned.",
                            'error' => 'ALREADY_SCANNED',
                            'data' => [
                                'id' => (string) $alreadyScannedOrder->id,
                                'ticket_code' => $ticket->ticket_code,
                                'scanned_at' => $alreadyScannedOrder->scanned_at?->toIso8601String(),
                                'status' => 'error',
                                'message' => "Ticket {$ticketCode} has already been scanned.",
                                'attendee_name' => $ticket->attendee_name ?? null,
                                'ticket_type' => $ticket->ticketCategory->name ?? 'N/A',
                            ]
                        ], 409);
                    }

                    DB::rollBack();
                    return response()->json([
                        'message' => 'Ticket order not found or not in a scannable state for this ticket.',
                        'error' => 'ORDER_NOT_FOUND_OR_INVALID_STATE'
                    ], 404);
                }

                $ticketOrder->status = TicketOrderStatus::SCANNED;
                $ticketOrder->scanned_at = now();
                $ticketOrder->save();

                DB::commit();

                Log::info('Ticket successfully scanned.', [
                    'ticket_code' => $ticketCode,
                    'ticket_order_id' => $ticketOrder->id,
                    'event_id' => $event->id,
                    'scanned_at' => $ticketOrder->scanned_at
                ]);

                return response()->json([
                    'message' => "Ticket {$ticketCode} successfully scanned.",
                    'success' => true,
                    'data' => [
                        'id' => (string) $ticketOrder->id,
                        'ticket_code' => $ticket->ticket_code,
                        'scanned_at' => $ticketOrder->scanned_at->toIso8601String(),
                        'status' => 'success',
                        'message' => "Ticket {$ticketCode} successfully scanned.",
                        'attendee_name' => $ticket->attendee_name ?? null,
                        'ticket_type' => $ticket->ticketCategory->name ?? 'N/A',
                    ]
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Database transaction failed for scan: " . $e->getMessage(), [
                    'ticket_code' => $ticketCode,
                    'event_slug' => $client,
                    'user_id' => $user->id,
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'message' => 'An error occurred during ticket processing.',
                    'error' => 'TRANSACTION_ERROR'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error("Error scanning ticket for event {$client}: " . $e->getMessage(), [
                'ticket_code' => $request->input('ticket_code'),
                'event_slug' => $client,
                'client' => $client,
                'user_id' => $user?->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred while scanning the ticket.',
                'error' => 'SERVER_ERROR'
            ], 500);
        }
    }

    public function getScannedHistory(Request $request, string $client): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user || !User::find($user->id)?->isReceptionist()) {
                return response()->json([
                    'message' => 'Unauthorized access.',
                    'error' => 'UNAUTHORIZED'
                ], 403);
            }

            $event = Event::where('slug', $client)->first();
            if (!$event) {
                return response()->json([
                    'message' => 'Event not found.',
                    'error' => 'EVENT_NOT_FOUND'
                ], 404);
            }

            $scannedOrders = TicketOrder::whereHas('ticket', function ($query) use ($event) {
                $query->where('event_id', $event->id);
            })
                ->with(['ticket.ticketCategory'])
                ->where('status', TicketOrderStatus::SCANNED->value)
                ->whereNotNull('scanned_at')
                ->orderByDesc('scanned_at')
                ->get();

            $formattedHistory = $scannedOrders->map(function ($order) {
                return [
                    'id' => (string) $order->id,
                    'ticket_code' => $order->ticket->ticket_code,
                    'scanned_at' => $order->scanned_at->toIso8601String(),
                    'status' => 'success',
                    'message' => "Ticket {$order->ticket->ticket_code} was scanned.",
                    'attendee_name' => $order->ticket->attendee_name ?? null,
                    'ticket_type' => $order->ticket->ticketCategory->name ?? 'N/A',
                ];
            });

            return response()->json([
                'message' => 'Scanned tickets history fetched successfully.',
                'data' => $formattedHistory
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error fetching scanned history for event {$client}: " . $e->getMessage(), [
                'event_slug' => $client,
                'user_id' => $user?->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An error occurred while fetching scan history.',
                'error' => 'SERVER_ERROR'
            ], 500);
        }
    }
}