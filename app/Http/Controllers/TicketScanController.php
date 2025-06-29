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
            $user = $this->authenticateUser($client);
            $event = $this->getEvent($client);
            $eventVariables = $this->getEventVariables($event);

            return Inertia::render('Receptionist/ScanTicket', [
                'event' => $this->formatEventData($event),
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
        try {
            $this->authenticateUser($client);
            $this->validateInput($request);
            
            $ticketCode = trim($request->input('ticket_code'));
            $event = $this->getEvent($client);
            
            $ticket = $this->findTicket($ticketCode, (int) $event->id);
            if (!$ticket) {
                return $this->errorResponse('Ticket not found or not valid for this event.', 'TICKET_NOT_FOUND', 404);
            }

            $ticketOrder = $this->getValidTicketOrder($ticket);
            if (!$ticketOrder) {
                $alreadyScannedOrder = $this->getAlreadyScannedOrder($ticket);
                if ($alreadyScannedOrder) {
                    return $this->errorResponse(
                        "Ticket {$ticketCode} has already been scanned.",
                        'ALREADY_SCANNED',
                        409,
                        $this->formatTicketData($ticket, $alreadyScannedOrder, 'already_scanned')
                    );
                }
                return $this->errorResponse('Ticket order not found or not in a scannable state.', 'ORDER_NOT_FOUND_OR_INVALID_STATE', 404);
            }

            return response()->json([
                'message' => 'Ticket validation successful',
                'data' => $this->formatTicketData($ticket, $ticketOrder, 'valid_for_scan')
            ], 200);

        } catch (\Exception $e) {
            return $this->handleException($e, 'validating', $request, $client);
        }
    }

    public function scan(Request $request, string $client): JsonResponse
    {
        try {
            $user = $this->authenticateUser($client);
            $this->validateInput($request);
            
            $ticketCode = trim($request->input('ticket_code'));
            $event = $this->getEvent($client);

            return DB::transaction(function () use ($ticketCode, $event, $user) {
                $ticket = $this->findTicketForUpdate($ticketCode, (int) $event->id);
                if (!$ticket) {
                    return $this->errorResponse('Ticket not found or not valid for this event.', 'TICKET_NOT_FOUND', 404);
                }

                $ticketOrder = $this->getValidTicketOrder($ticket);
                if (!$ticketOrder) {
                    $alreadyScannedOrder = $this->getAlreadyScannedOrder($ticket);
                    if ($alreadyScannedOrder) {
                        return $this->errorResponse(
                            "Ticket {$ticketCode} has already been scanned.",
                            'ALREADY_SCANNED',
                            409,
                            $this->formatScannedTicketData($ticket, $alreadyScannedOrder, 'error', "Ticket {$ticketCode} has already been scanned.")
                        );
                    }
                    return $this->errorResponse('Ticket order not found or not in a scannable state for this ticket.', 'ORDER_NOT_FOUND_OR_INVALID_STATE', 404);
                }

                $this->markTicketAsScanned($ticketOrder, $user->id);

                Log::info('Ticket successfully scanned.', [
                    'ticket_code' => $ticketCode,
                    'ticket_order_id' => $ticketOrder->id,
                    'event_id' => $event->id,
                    'scanned_at' => $ticketOrder->scanned_at,
                    'scanned_by' => $user->id
                ]);

                return response()->json([
                    'message' => "Ticket {$ticketCode} successfully scanned.",
                    'success' => true,
                    'data' => $this->formatScannedTicketData($ticket, $ticketOrder, 'success', "Ticket {$ticketCode} successfully scanned.")
                ], 200);
            });

        } catch (\Exception $e) {
            return $this->handleException($e, 'scanning', $request, $client);
        }
    }

    public function getScannedHistory(Request $request, string $client): JsonResponse
    {
        try {
            $this->authenticateUser($client);
            $event = $this->getEvent($client);

            $scannedOrders = $this->getScannedOrdersForEvent((int) $event->id);
            $formattedHistory = $scannedOrders->map(function ($order) {
                return $this->formatScannedTicketData($order->ticket, $order, 'success', "Ticket {$order->ticket->ticket_code} was scanned.");
            });

            return response()->json([
                'message' => 'Scanned tickets history fetched successfully.',
                'data' => $formattedHistory
            ], 200);

        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching history', $request, $client);
        }
    }

    // Helper Methods
    private function authenticateUser(string $client)
    {
        $user = Auth::user();
        if (!$user) {
            throw new \Exception('Authentication required', 401);
        }

        $userModel = User::find($user->id);
        if (!$userModel || !$userModel->isReceptionist()) {
            throw new \Exception('Unauthorized access', 403);
        }

        return $user;
    }

    private function getEvent(string $client)
    {
        $event = Event::where('slug', $client)->first();
        if (!$event) {
            throw new \Exception('Event not found', 404);
        }
        return $event;
    }

    private function getEventVariables($event)
    {
        $eventVariables = $event->eventVariables;
        if ($eventVariables) {
            $eventVariables->reconstructImgLinks();
        } else {
            $eventVariables = \App\Models\EventVariables::getDefaultValue();
        }
        return $eventVariables;
    }

    private function formatEventData($event)
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'slug' => $event->slug,
            'location' => $event->location,
            'event_date' => $event->event_date?->toDateString(),
            'event_time' => $event->event_date?->format('H:i'),
        ];
    }

    private function validateInput(Request $request)
    {
        try {
            $request->validate([
                'ticket_code' => 'required|string|max:255|min:1',
                'event_slug' => 'required|string|max:255|min:1',
            ]);
        } catch (ValidationException $e) {
            throw new \Exception('Invalid input data', 422);
        }
    }

    private function findTicket(string $ticketCode, int $eventId)
    {
        return Ticket::where('ticket_code', $ticketCode)
            ->where('event_id', $eventId)
            ->with(['ticketCategory', 'seat', 'ticketOrders.order.user.contactInfo', 'event'])
            ->first();
    }

    private function findTicketForUpdate(string $ticketCode, int $eventId)
    {
        return Ticket::where('ticket_code', $ticketCode)
            ->where('event_id', $eventId)
            ->with(['ticketCategory', 'seat', 'ticketOrders.order.user.contactInfo', 'event'])
            ->lockForUpdate()
            ->first();
    }

    private function getValidTicketOrder($ticket)
    {
        return $ticket->ticketOrders()
            ->where('status', '!=', TicketOrderStatus::SCANNED->value)
            ->with(['order.user.contactInfo'])
            ->orderByDesc('created_at')
            ->first();
    }

    private function getAlreadyScannedOrder($ticket)
    {
        return $ticket->ticketOrders()
            ->where('status', TicketOrderStatus::SCANNED->value)
            ->with(['order.user.contactInfo', 'scannedBy.contactInfo'])
            ->orderByDesc('created_at')
            ->first();
    }

    private function markTicketAsScanned($ticketOrder, $userId)
    {
        $ticketOrder->status = TicketOrderStatus::SCANNED;
        $ticketOrder->scanned_at = now();
        $ticketOrder->scanned_by = $userId;
        $ticketOrder->save();
    }

    private function getScannedOrdersForEvent(int $eventId)
    {
        return TicketOrder::whereHas('ticket', function ($query) use ($eventId) {
            $query->where('event_id', $eventId);
        })
        ->with(['ticket.ticketCategory', 'ticket.seat', 'ticket.event', 'order.user.contactInfo', 'scannedBy.contactInfo'])
        ->where('status', TicketOrderStatus::SCANNED->value)
        ->whereNotNull('scanned_at')
        ->orderByDesc('scanned_at')
        ->get();
    }

    private function getUserDataFromOrder($order)
    {
        $devData = $order->devNoSQLUserData();
        $buyerContact = $order->user?->contactInfo ?? null;
        
        return [
            'full_name' => $devData?->data['user_full_name'] ?? ($buyerContact?->fullname ?? 'N/A'),
            'phone' => $devData?->data['user_phone_num'] ?? ($buyerContact?->phone_number ?? null),
            'id_number' => $devData?->data['user_id_no'] ?? null,
            'sizes' => isset($devData?->data['user_sizes']) ? implode(', ', $devData->data['user_sizes']) : null,
            'email' => $devData?->data['user_email'] ?? ($order->user?->email ?? null),
            'address' => $buyerContact?->address ?? null,
            'gender' => $buyerContact?->gender ?? null,
            'birth_date' => $buyerContact?->birth_date ?? null,
            'whatsapp' => $buyerContact?->whatsapp_number ?? null,
        ];
    }

    private function formatTicketData($ticket, $ticketOrder, $status)
    {
        $userData = $this->getUserDataFromOrder($ticketOrder->order);

        // Perbaikan di sini: Inisialisasi $scannedByData berdasarkan status
        $scannedByData = null;
        if ($status === 'already_scanned') {
            $scannedByData = $this->getScannedByData($ticketOrder);
        }

        return [
            'ticket_code' => $ticket->ticket_code,
            'attendee_name' => $ticket->attendee_name ?? $userData['full_name'],
            'ticket_type' => $ticket->ticketCategory->name ?? 'N/A',
            'ticket_price' => $ticket->price ?? 0,
            'order_code' => $ticketOrder->order->order_code ?? null,
            'order_date' => $ticketOrder->order->getOrderDateTimestamp() ?? null,
            'order_created_at' => $ticketOrder->order->order_date ?? null,
            'order_paid_at' => $ticketOrder->order->updated_at ?? null,
            'buyer_email' => $userData['email'],
            'buyer_name' => $userData['full_name'],
            'buyer_phone' => $userData['phone'],
            'buyer_id_number' => $userData['id_number'],
            'buyer_sizes' => $userData['sizes'],
            'seat_number' => $ticket->seat?->seat_number ?? null,
            'seat_row' => $ticket->seat?->row ?? null,
            'event_name' => $ticket->event->name ?? null,
            'event_location' => $ticket->event->location ?? null,
            'event_date' => $ticket->event->getEventDate() ?? null,
            'event_time' => $ticket->event->getEventTime() ?? null,
            'status' => $status,
            'scanned_at' => $status === 'already_scanned' ? $ticketOrder->scanned_at?->toIso8601String() : null,
            'scanned_by_id' => $scannedByData['id'] ?? null, // Sekarang $scannedByData sudah pasti terinisialisasi jika diperlukan
            'scanned_by_name' => $scannedByData['name'] ?? null, // Sekarang $scannedByData sudah pasti terinisialisasi jika diperlukan
            'scanned_by_email' => $scannedByData['email'] ?? null, // Sekarang $scannedByData sudah pasti terinisialisasi jika diperlukan
            'scanned_by_full_name' => $scannedByData['full_name'] ?? null, // Sekarang $scannedByData sudah pasti terinisialisasi jika diperlukan
        ];
    }

    private function formatScannedTicketData($ticket, $ticketOrder, $status, $message)
    {
        $userData = $this->getUserDataFromOrder($ticketOrder->order);
        
        // Get scanned by user information
        $scannedByData = $this->getScannedByData($ticketOrder);

        return [
            'id' => (string) $ticketOrder->id,
            'ticket_id' => $ticket->id,
            'ticket_code' => $ticket->ticket_code,
            'scanned_at' => $ticketOrder->scanned_at->toIso8601String(),
            'status' => $status,
            'message' => $message,
            
            // Ticket Information
            'attendee_name' => $ticket->attendee_name ?? $userData['full_name'],
            'ticket_type' => $ticket->ticketCategory->name ?? 'N/A',
            'ticket_price' => $ticket->price ?? 0,
            'ticket_color' => $ticket->ticketCategory->color ?? '#667eea',
            
            // Seat Information
            'seat_number' => $ticket->seat?->seat_number ?? 'General Admission',
            'seat_row' => $ticket->seat?->row ?? null,
            'seat_position' => $ticket->seat?->position ?? null,
            
            // Order Information
            'order_id' => $ticketOrder->order->id,
            'order_code' => $ticketOrder->order->order_code ?? null,
            'order_date' => $ticketOrder->order->getOrderDateTimestamp() ?? null,
            'order_created_at' => $ticketOrder->order->order_date ?? null,
            'order_paid_at' => $ticketOrder->order->updated_at ?? null,
            'total_price' => $ticketOrder->order->total_price ?? 0,
            'payment_gateway' => $ticketOrder->order->payment_gateway ?? null,
            
            // Buyer Information  
            'buyer_id' => $ticketOrder->order->user?->id ?? null,
            'buyer_email' => $userData['email'],
            'buyer_name' => $userData['full_name'],
            'buyer_phone' => $userData['phone'],
            'buyer_whatsapp' => $userData['whatsapp'],
            'buyer_id_number' => $userData['id_number'],
            'buyer_sizes' => $userData['sizes'],
            'buyer_address' => $userData['address'],
            'buyer_gender' => $userData['gender'],
            'buyer_birth_date' => $userData['birth_date'],
            
            // Event Information
            'event_id' => $ticket->event->id,
            'event_name' => $ticket->event->name ?? null,
            'event_location' => $ticket->event->location ?? null,
            'event_date' => $ticket->event->getEventDate() ?? null,
            'event_time' => $ticket->event->getEventTime() ?? null,
            'event_slug' => $ticket->event->slug ?? null,
            
            // Scanned By Information
            'scanned_by_id' => $scannedByData['id'] ?? null,
            'scanned_by_name' => $scannedByData['name'] ?? null,
            'scanned_by_email' => $scannedByData['email'] ?? null,
            'scanned_by_full_name' => $scannedByData['full_name'] ?? null,
        ];
    }

    private function getScannedByData($ticketOrder)
    {
        $scannedByUser = $ticketOrder->scannedBy ?? null;
        if ($scannedByUser) {
            $scannedByContact = $scannedByUser->contactInfo ?? null;
            return [
                'id' => $scannedByUser->id,
                'name' => $scannedByUser->getFilamentName(),
                'email' => $scannedByUser->email,
                'full_name' => $scannedByContact?->fullname ?? $scannedByUser->getFilamentName(),
            ];
        }
        return null;
    }

    private function errorResponse(string $message, string $error, int $status, $data = null): JsonResponse
    {
        $response = [
            'message' => $message,
            'error' => $error
        ];
        
        if ($data) {
            $response['data'] = $data;
        }
        
        return response()->json($response, $status);
    }

    private function handleException(\Exception $e, string $action, Request $request, string $client): JsonResponse
    {
        Log::error("Error {$action} ticket for event {$client}: " . $e->getMessage(), [
            'ticket_code' => $request->input('ticket_code'),
            'event_slug' => $client,
            'user_id' => Auth::id(),
            'trace' => $e->getTraceAsString()
        ]);

        $statusCode = method_exists($e, 'getCode') && $e->getCode() > 0 ? $e->getCode() : 500;
        
        return response()->json([
            'message' => $e->getMessage() ?: "An unexpected error occurred while {$action} the ticket.",
            'error' => 'SERVER_ERROR'
        ], $statusCode);
    }
}