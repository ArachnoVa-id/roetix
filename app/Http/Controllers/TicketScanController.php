<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Models\User;
use App\Enums\TicketOrderStatus;
use App\Enums\UserRole; // Make sure this is the correct path to your UserRole enum
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse; // Alias to avoid conflict with Illuminate\Http\Response
use Illuminate\Http\JsonResponse;

class TicketScanController extends Controller
{
    /**
     * Display the ticket scanning page for an event.
     * Only accessible by users with the 'receptionist' role.
     */
    public function show(Request $request, string $client, string $event_slug): InertiaResponse | \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();
        $userModel = User::find($user->id);
        if (!$user || !$userModel->isReceptionist()) { // Assuming isReceptionist() method exists on User model
            Log::warning('Unauthorized attempt to access scan page.', ['user_id' => $user?->id, 'event_slug' => $event_slug]);
            // Redirect to client home or login, or show a generic unauthorized page
            return redirect()->route('client.home', ['client' => $client])->with('error', 'Unauthorized access.');
        }

        $event = Event::where('slug', $event_slug)
            // ->where('client_id', $client_id) // If you also link events to a client entity
            ->first();

        if (!$event) {
            Log::error('Event not found for scanning.', ['event_slug' => $event_slug, 'client' => $client]);
            abort(404, 'Event not found.');
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
            'event' => [ // Pass only necessary, non-sensitive event data
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
            ],
            'client' => $client,
            // 'props' should be consistent with what AuthenticatedLayout expects/provides
            // This often comes from a middleware or view composer.
            // For now, let's assume it's passed or can be reconstructed here if needed.
            'props' => $eventVariables->getSecure(), 
            'appName' => config('app.name'),
            // 'userEndSessionDatetime' => ... // if applicable
        ]);
    }

    /**
     * Process the scanned ticket code.
     */
    public function scan(Request $request, string $client, string $event_slug): JsonResponse
    {
        $user = Auth::user();
        $userModel = User::find($user->id);
        if (!$user || !$userModel->isReceptionist()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'ticket_code' => 'required|string|max:255',
        ]);

        $ticketCode = $validated['ticket_code'];

        $event = Event::where('slug', $event_slug)->first();
        if (!$event) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        try {
            DB::beginTransaction();

            $ticket = Ticket::where('ticket_code', $ticketCode)
                ->where('event_id', $event->id)
                ->lockForUpdate() // Important for concurrency
                ->first();

            if (!$ticket) {
                DB::rollBack();
                return response()->json(['message' => 'Ticket not found or not valid for this event.'], 404);
            }

            // Get the latest ticket order associated with this ticket
            // Assuming a ticket might have multiple order entries if re-enabled, etc.
            // Or, if a ticket_id is unique to one order, TicketOrder::where('ticket_id', $ticket->id)->first() is fine.
            $ticketOrder = $ticket->ticketOrders()->orderByDesc('created_at')->first();

            if (!$ticketOrder) {
                DB::rollBack();
                return response()->json(['message' => 'Ticket order not found for this ticket.'], 404);
            }
            
            // Check if already scanned
            if ($ticketOrder->status === TicketOrderStatus::SCANNED->value) {
                 DB::rollBack(); // No change, so rollback (or commit, depending on desired behavior)
                 return response()->json(['message' => "Ticket {$ticketCode} has already been scanned."], 409); // 409 Conflict
            }

            // Check if ticket is in a scannable state (e.g., ENABLED or PAID)
            // This depends on your application's status flow.
            // Example: if ($ticketOrder->status !== TicketOrderStatus::ENABLED->value && $ticketOrder->status !== TicketOrderStatus::PAID->value) {
            // DB::rollBack();
            // return response()->json(['message' => "Ticket {$ticketCode} is not in a scannable status."], 400);
            // }


            $ticketOrder->status = TicketOrderStatus::SCANNED;
            $ticketOrder->save();

            DB::commit();

            // You could dispatch an event or send a real-time notification here if needed.

            return response()->json(['message' => "Ticket {$ticketCode} successfully scanned."]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error scanning ticket {$ticketCode} for event {$event_slug}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An server error occurred: ' . $e->getMessage()], 500);
        }
    }
}