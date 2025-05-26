<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Models\User;
use App\Enums\TicketOrderStatus;
use App\Enums\UserRole;
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
    /**
     * Display the ticket scanning page for an event.
     * Only accessible by users with the 'receptionist' role.
     */
    public function show(Request $request, string $client, string $event_slug): InertiaResponse | \Illuminate\Http\RedirectResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Log::warning('Unauthenticated attempt to access scan page.', ['event_slug' => $event_slug]);
                return redirect()->route('client.login', ['client' => $client])
                    ->with('error', 'Please login to access this page.');
            }

            $userModel = User::find($user->id);
            if (!$userModel || !$userModel->isReceptionist()) {
                Log::warning('Unauthorized attempt to access scan page.', [
                    'user_id' => $user->id,
                    'user_role' => $userModel?->role ?? 'unknown',
                    'event_slug' => $event_slug
                ]);
                return redirect()->route('client.home', ['client' => $client])
                    ->with('error', 'You do not have permission to access this page.');
            }

            $event = Event::where('slug', $event_slug)->first();

            if (!$event) {
                Log::error('Event not found for scanning.', [
                    'event_slug' => $event_slug,
                    'client' => $client,
                    'user_id' => $user->id
                ]);
                return redirect()->route('client.home', ['client' => $client])
                    ->with('error', 'Event not found.');
            }

            // Ensure event props are available similar to AuthenticatedLayout
            $eventVariables = $event->eventVariables;
            if ($eventVariables) {
                $eventVariables->reconstructImgLinks();
            } else {
                // Fallback or default event variables if necessary
                $eventVariables = \App\Models\EventVariables::getDefaultValue();
            }

            return Inertia::render('Receptionist/ScanTicket', [
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'slug' => $event->slug,
                ],
                'client' => $client,
                'props' => $eventVariables->getSecure(),
                'appName' => config('app.name'),
                'userEndSessionDatetime' => null, // Receptionist doesn't have session timeout
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading scan page: ' . $e->getMessage(), [
                'event_slug' => $event_slug,
                'client' => $client,
                'user_id' => $user?->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('client.home', ['client' => $client])
                ->with('error', 'An error occurred while loading the scan page.');
        }
    }

    /**
     * Process the scanned ticket code.
     */
    public function scan(Request $request, string $client, string $event_slug): JsonResponse
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
                    'event_slug' => $event_slug
                ]);
                return response()->json([
                    'message' => 'You do not have permission to scan tickets.',
                    'error' => 'UNAUTHORIZED'
                ], 403);
            }

            // Validate input with better error messages
            try {
                $validated = $request->validate([
                    'ticket_code' => 'required|string|max:255|min:1',
                ]);
            } catch (ValidationException $e) {
                return response()->json([
                    'message' => 'Invalid ticket code format.',
                    'error' => 'VALIDATION_ERROR',
                    'details' => $e->errors()
                ], 422);
            }

            $ticketCode = trim($validated['ticket_code']);

            if (empty($ticketCode)) {
                return response()->json([
                    'message' => 'Ticket code cannot be empty.',
                    'error' => 'EMPTY_CODE'
                ], 422);
            }

            $event = Event::where('slug', $event_slug)->first();
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
                    Log::info('Ticket not found for scanning.', [
                        'ticket_code' => $ticketCode,
                        'event_id' => $event->id,
                        'event_slug' => $event_slug,
                        'user_id' => $user->id
                    ]);
                    return response()->json([
                        'message' => 'Ticket not found or not valid for this event.',
                        'error' => 'TICKET_NOT_FOUND'
                    ], 404);
                }

                // Get the latest ticket order associated with this ticket
                $ticketOrder = $ticket->ticketOrders()
                    ->orderByDesc('created_at')
                    ->first();

                if (!$ticketOrder) {
                    DB::rollBack();
                    Log::error('Ticket order not found for ticket.', [
                        'ticket_id' => $ticket->id,
                        'ticket_code' => $ticketCode,
                        'event_id' => $event->id
                    ]);
                    return response()->json([
                        'message' => 'Ticket order not found for this ticket.',
                        'error' => 'ORDER_NOT_FOUND'
                    ], 404);
                }

                // Check if already scanned
                if ($ticketOrder->status === TicketOrderStatus::SCANNED->value) {
                    DB::rollBack();
                    Log::info('Attempt to scan already scanned ticket.', [
                        'ticket_code' => $ticketCode,
                        'ticket_order_id' => $ticketOrder->id,
                        'user_id' => $user->id
                    ]);
                    return response()->json([
                        'message' => "Ticket {$ticketCode} has already been scanned.",
                        'error' => 'ALREADY_SCANNED'
                    ], 409);
                }

                // Check if ticket is in a scannable state
                $scannableStatuses = [
                    TicketOrderStatus::ENABLED->value,
                    TicketOrderStatus::DEACTIVATED->value,
                    // Add other valid statuses as needed
                ];

                if (!in_array($ticketOrder->status, $scannableStatuses)) {
                    DB::rollBack();
                    Log::info('Attempt to scan ticket with invalid status.', [
                        'ticket_code' => $ticketCode,
                        'current_status' => $ticketOrder->status,
                        'ticket_order_id' => $ticketOrder->id,
                        'user_id' => $user->id
                    ]);
                    return response()->json([
                        'message' => "Ticket {$ticketCode} is not in a scannable status. Current status: {$ticketOrder->status}",
                        'error' => 'INVALID_STATUS'
                    ], 400);
                }

                // Update ticket status to scanned
                $ticketOrder->status = TicketOrderStatus::SCANNED;
                $ticketOrder->save();

                DB::commit();

                Log::info('Ticket successfully scanned.', [
                    'ticket_code' => $ticketCode,
                    'ticket_order_id' => $ticketOrder->id,
                    'event_id' => $event->id,
                ]);

                return response()->json([
                    'message' => "Ticket {$ticketCode} successfully scanned.",
                    'success' => true,
                    'data' => [
                        'ticket_code' => $ticketCode,
                        'status' => $ticketOrder->status
                    ]
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e; // Re-throw to be caught by outer try-catch
            }
        } catch (\Exception $e) {
            Log::error("Error scanning ticket for event {$event_slug}: " . $e->getMessage(), [
                'ticket_code' => $request->input('ticket_code'),
                'event_slug' => $event_slug,
                'client' => $client,
                'user_id' => $user?->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An error occurred while processing the ticket scan.',
                'error' => 'SERVER_ERROR'
            ], 500);
        }
    }
}
