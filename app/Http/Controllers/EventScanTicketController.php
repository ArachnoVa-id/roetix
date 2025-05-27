<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Enums\TicketOrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EventScanTicketController extends Controller
{
    public function show(Request $request, $client, $event_slug)
    {
        // Check if user has receptionist role
        if (auth()->user()->role !== 'receptionist') {
            abort(403, 'Access denied. Receptionist role required.');
        }

        $event = Event::where('slug', $event_slug)->firstOrFail();

        return Inertia::render('EventScanTicket', [
            'event' => $event,
            'client' => $client,
            'tickets' => $this->getTickets($event->id)
        ]);
    }

    public function submit(Request $request, $client, $event_slug)
    {
        // Check if user has receptionist role
        if (auth()->user()->role !== 'receptionist') {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'ticket_code' => 'required|string',
        ]);

        $event = Event::where('slug', $event_slug)->firstOrFail();

        try {
            DB::beginTransaction();
            
            $ticket = Ticket::where('ticket_code', $request->ticket_code)
                ->where('event_id', $event->id)
                ->lockForUpdate()
                ->first();

            if (!$ticket) {
                throw new \Exception('Tiket tidak ditemukan atau tidak valid.');
            }

            $ticketOrder = TicketOrder::where('ticket_id', $ticket->id)->first();

            if (!$ticketOrder) {
                throw new \Exception('Order tiket tidak ditemukan.');
            }

            // Check if already scanned
            if ($ticketOrder->status === TicketOrderStatus::SCANNED) {
                throw new \Exception('Tiket sudah pernah discan sebelumnya.');
            }

            $ticketOrder->status = TicketOrderStatus::SCANNED;
            $ticketOrder->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Tiket dengan kode {$request->ticket_code} berhasil discan.",
                'tickets' => $this->getTickets($event->id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    private function getTickets($eventId)
    {
        return Ticket::where('event_id', $eventId)
            ->with('ticketOrders')
            ->get()
            ->map(function ($ticket) {
                $latestOrder = $ticket->ticketOrders->sortByDesc('created_at')->first();
                return [
                    'id' => $ticket->id,
                    'ticket_code' => $ticket->ticket_code,
                    'status' => $latestOrder ? TicketOrderStatus::from($latestOrder->status)->getLabel() : TicketOrderStatus::ENABLED->getLabel(),
                    'status_color' => $latestOrder ? TicketOrderStatus::from($latestOrder->status)->getColor() : TicketOrderStatus::ENABLED->getColor(),
                    'created_at' => $ticket->created_at->format('Y-m-d H:i:s'),
                ];
            });
    }
}