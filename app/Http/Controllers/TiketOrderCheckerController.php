<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // Added the missing import

class TicketOrderCheckerController extends Controller
{
    /**
     * Debug endpoint to check ticket-order relationships
     */
    public function checkTicketOwnership(Request $request)
    {
        try {
            $ticketId = $request->input('ticket_id');

            if (!$ticketId) {
                return response()->json(['error' => 'Ticket ID is required'], 400);
            }

            // Get the ticket
            $ticket = Ticket::with(['event'])->where('ticket_id', $ticketId)->first();

            if (!$ticket) {
                return response()->json(['error' => 'Ticket not found'], 404);
            }

            // Get user's orders
            $userId = Auth::id();
            $userOrders = Order::where('id', $userId)->get();
            $userOrderIds = $userOrders->pluck('order_id')->toArray();

            // Check direct order relationship
            $directOrderMatch = null;
            if ($ticket->order_id) {
                $directOrderMatch = in_array($ticket->order_id, $userOrderIds);
            }

            // Check pivot table if it exists
            $pivotTableExists = Schema::hasTable('ticket_order');
            $pivotRelationships = null;

            if ($pivotTableExists) {
                $pivotRelationships = DB::table('ticket_order')
                    ->where('ticket_id', $ticket->ticket_id)
                    ->get();
            }

            // Check if any pivot relationships match user's orders
            $pivotMatches = [];
            if ($pivotRelationships) {
                foreach ($pivotRelationships as $rel) {
                    if (in_array($rel->order_id, $userOrderIds)) {
                        $pivotMatches[] = $rel->order_id;
                    }
                }
            }

            // Return all the debug info
            return response()->json([
                'success' => true,
                'ticket' => [
                    'id' => $ticket->ticket_id,
                    'order_id' => $ticket->order_id,
                    'event_id' => $ticket->event_id,
                    'event_name' => $ticket->event ? $ticket->event->name : null
                ],
                'user' => [
                    'id' => $userId,
                    'order_count' => count($userOrderIds),
                    'order_ids' => $userOrderIds
                ],
                'ownership' => [
                    'direct_order_match' => $directOrderMatch,
                    'pivot_table_exists' => $pivotTableExists,
                    'pivot_relationships_count' => $pivotRelationships ? count($pivotRelationships) : 0,
                    'pivot_matches' => $pivotMatches,
                    'has_access' => $directOrderMatch || count($pivotMatches) > 0
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to check ticket ownership',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
