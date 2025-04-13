<?php

namespace App\Http\Controllers;

use App\Enums\TicketOrderStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    /**
     * Download a single ticket as PDF using query parameters instead of path parameters
     */
    public function downloadTickets(Request $request)
    {
        // Define variables
        $userOrderIds = [];
        $event = null;

        try {
            // Get parameters from query string
            $ticketIdsStr = $request->input('ticket_ids');
            $eventId = $request->input('event_id');

            if (!$ticketIdsStr || !$eventId) {
                return response()->json(['error' => 'Event ID and ticket IDs are required'], 400);
            }

            // Convert ticket IDs string to an array
            $ticketIds = explode(',', $request->input('ticket_ids'));

            // Get user's order IDs
            $userOrderIds = Order::where('user_id', Auth::id())->pluck('id');

            // Find accessible tickets in one query
            $accessibleTickets = Ticket::with(['seat', 'event', 'ticketOrders.order'])
                ->join('ticket_order', 'tickets.id', '=', 'ticket_order.ticket_id')
                ->join('orders', 'orders.id', '=', 'ticket_order.order_id')
                ->where('tickets.event_id', $eventId) // Prefix 'event_id' with the 'tickets' table
                ->whereIn('tickets.id', $ticketIds) // Prefix 'ticket_id' with the 'tickets' table
                ->whereHas('ticketOrders', function ($query) use ($userOrderIds) {
                    $query->whereIn('order_id', $userOrderIds);
                })
                ->whereIn('ticket_order.status', [TicketOrderStatus::ENABLED]) // Filter ticket order status
                ->select('tickets.*', 'orders.id')
                ->addSelect('orders.id as order_id')
                ->get();

            if ($accessibleTickets->isEmpty()) {
                return response()->json(['error' => 'No accessible tickets found'], 404);
            }

            foreach ($accessibleTickets as $ticket) {
                $order = Order::where('id', $ticket->order_id)->first();
                $ticket->order_date = $order->getOrderDateTimestamp();
            }

            // Get event details
            $event = Event::findOrFail($eventId);
            $user = Auth::user();

            // Prepare data for PDF
            $data = [
                'tickets' => $accessibleTickets,
                'event' => $event,
                'user' => $user,
            ];

            // Configure PDF options
            $pdfOptions = [
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ];

            // Generate PDF
            $pdf = PDF::loadView('tickets.ticket-general', $data)
                ->setPaper([0, 0, 300, 600], 'landscape')
                ->setOptions($pdfOptions);

            // Return the PDF download
            $pdfTitle = $accessibleTickets->count() === 1
                ? $accessibleTickets->first()->getTicketPDFTitle()
                : $event->getAllTicketsPDFTitle();

            return $pdf->download($pdfTitle);
        } catch (\Exception $e) {
            Log::error('Failed to download tickets: ' . $e->getMessage(), [
                'event_id' => $eventId,
                'ticket_ids' => $ticketIds ?? [],
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to download tickets: ' . $e->getMessage()], 500);
        }
    }
}
