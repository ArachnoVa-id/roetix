<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /**
     * Download a single ticket as PDF using query parameters instead of path parameters
     */
    public function downloadTicket(Request $request)
    {
        // Define variables
        $userOrderIds = [];
        $ticket = null;

        // Get the ticket ID from query parameter
        $ticketId = $request->input('ticket_id');
        $eventId = $request->input('event_id');

        try {
            // Authentication and access control checks...

            // Get the ticket from the database
            $ticket = Ticket::with(['seat', 'event'])->where('ticket_id', $ticketId)->first();

            // If ticket not found, return 404
            if (!$ticket) {
                Log::warning('Ticket not found', ['ticketId' => $ticketId]);
                return response()->json(['error' => 'Ticket not found'], 404);
            }

            // Authorization logic
            $userOrderIds = Order::where('id', Auth::id())->pluck('order_id')->toArray();

            $hasAccess = false;

            // Check direct relationship
            if ($ticket->order_id && in_array($ticket->order_id, $userOrderIds)) {
                $hasAccess = true;
            }

            // Check pivot relationship
            if (!$hasAccess) {
                $relatedOrders = DB::table('ticket_order')
                    ->where('ticket_id', $ticket->ticket_id)
                    ->whereIn('order_id', $userOrderIds)
                    ->count();

                if ($relatedOrders > 0) {
                    $hasAccess = true;
                    Log::info('Access granted through ticket_order relation');
                }
            }

            // For testing only - skip access check if needed
            // $hasAccess = true;

            if (!$hasAccess) {
                Log::warning('Unauthorized ticket access attempt', [
                    'user' => Auth::id(),
                    'ticketId' => $ticketId
                ]);
                return response()->json(['error' => 'Unauthorized access to ticket'], 403);
            }

            // Get event details
            $event = $ticket->event;
            if (!$event && $eventId) {
                $event = Event::find($eventId);
            }

            // Get user details
            $user = Auth::user();

            // Generate verification URL
            $verificationUrl = route('client.my_tickets', ['client' => $event->slug]) . '?ticket=' . $ticket->ticket_id;

            // Instead of using an image, create a simple verification box
            // No need for external images that might not load

            // Prepare data for PDF
            $data = [
                'ticket' => $ticket,
                'event' => $event,
                'user' => $user,
                'qrCode' => $verificationUrl,
                'generatedAt' => now()->format('d F Y, H:i'),
                'ticketId' => strtoupper(substr($ticket->ticket_id, 0, 8)),
            ];

            // Configure PDF options - make sure remote URLs are enabled
            $pdfOptions = [
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ];

            // Generate PDF
            $pdf = PDF::loadView('tickets.pdf', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions($pdfOptions);

            // Return PDF download
            return $pdf->download('ticket-' . $ticket->ticket_id . '.pdf');
        } catch (\Exception $e) {
            Log::error('Failed to download ticket: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to download ticket: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download multiple tickets as a ZIP of PDFs
     */
    public function downloadAllTickets(Request $request)
    {
        // Define variables
        $eventId = null;
        $ticketIds = [];
        $userOrderIds = [];
        $tickets = collect();

        try {
            // Get parameters from query string
            $eventId = $request->input('event_id');
            $ticketIdsStr = $request->input('ticket_ids');

            // Validate required parameters
            if (!$eventId || !$ticketIdsStr) {
                return response()->json(['error' => 'Event ID and ticket IDs are required'], 400);
            }

            // Convert comma-separated string to array
            $ticketIds = explode(',', $ticketIdsStr);

            // Get user's orders
            $userOrderIds = Order::where('id', Auth::id())->pluck('order_id')->toArray();

            // Find tickets
            $tickets = Ticket::with(['seat', 'event'])
                ->where('event_id', $eventId)
                ->whereIn('ticket_id', $ticketIds)
                ->get();

            // Filter accessible tickets
            $accessibleTickets = collect();
            foreach ($tickets as $ticket) {
                $hasAccess = false;

                // Check direct relationship
                if ($ticket->order_id && in_array($ticket->order_id, $userOrderIds)) {
                    $hasAccess = true;
                }

                // Check pivot relationship
                if (!$hasAccess) {
                    $relatedOrders = DB::table('ticket_order')
                        ->where('ticket_id', $ticket->ticket_id)
                        ->whereIn('order_id', $userOrderIds)
                        ->count();

                    if ($relatedOrders > 0) {
                        $hasAccess = true;
                    }
                }

                if ($hasAccess) {
                    $accessibleTickets->push($ticket);
                }
            }

            // If no tickets found, return error
            if ($accessibleTickets->isEmpty()) {
                return response()->json(['error' => 'No accessible tickets found'], 404);
            }

            // Get event details
            $event = Event::findOrFail($eventId);
            $user = Auth::user();

            // Prepare data for a single PDF with multiple tickets
            $data = [
                'tickets' => $accessibleTickets,
                'event' => $event,
                'user' => $user,
                'generatedAt' => now()->format('d F Y, H:i'),
            ];

            // PDF options
            $pdfOptions = [
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ];

            // Create a single PDF with all tickets
            $pdf = PDF::loadView('tickets.multiple', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions($pdfOptions);

            // Generate a filename based on the event
            $filename = 'tickets-' . $event->slug . '-' . now()->format('YmdHis') . '.pdf';

            // Return the PDF file
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Failed to download all tickets: ' . $e->getMessage(), [
                'event_id' => $eventId,
                'ticket_ids' => $ticketIds ?? [],
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to download tickets: ' . $e->getMessage()], 500);
        }
    }

    public function forceDownloadTicket(Request $request)
    {
        try {
            // Get parameters
            $ticketId = $request->input('ticket_id');
            $eventId = $request->input('event_id');

            // Log the request
            Log::info('Force download ticket request', [
                'ticketId' => $ticketId,
                'eventId' => $eventId,
                'user' => Auth::id()
            ]);

            if (!$ticketId || !$eventId) {
                return response()->json(['error' => 'Ticket ID and Event ID are required'], 400);
            }

            // Get the ticket (skip ownership verification for testing)
            $ticket = Ticket::with(['seat'])->where('ticket_id', $ticketId)->first();

            if (!$ticket) {
                return response()->json(['error' => 'Ticket not found'], 404);
            }

            // Get event details
            $event = Event::find($eventId);

            if (!$event) {
                return response()->json(['error' => 'Event not found'], 404);
            }

            // Get user details
            $user = Auth::user();

            // Prepare data for PDF
            $data = [
                'ticket' => $ticket,
                'event' => $event,
                'user' => $user,
                'qrCode' => route('client.my_tickets', ['client' => $event->slug]) . '?ticket=' . $ticket->ticket_id,
                'generatedAt' => now()->format('d F Y, H:i'),
                'ticketId' => strtoupper(substr($ticket->ticket_id, 0, 8)),
            ];

            // Generate PDF
            $pdf = PDF::loadView('tickets.pdf', $data);

            // Return PDF download
            return $pdf->download('ticket-' . $ticket->ticket_id . '.pdf');
        } catch (\Exception $e) {
            Log::error('Failed to force download ticket', [
                'ticketId' => $ticketId ?? 'not provided',
                'eventId' => $eventId ?? 'not provided',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Failed to download ticket: ' . $e->getMessage()], 500);
        }
    }
}
