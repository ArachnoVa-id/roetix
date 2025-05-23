<?php

namespace App\Http\Controllers;

use App\Enums\TicketOrderStatus;
use App\Models\Event; // Make sure Event model is imported
use App\Models\Ticket;
use App\Models\TicketOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TicketScanController extends Controller
{
    public function show(string $event_slug) // <--- CHANGE: Accept as string
    {
        // Manually find the event by its slug
        $event = Event::where('slug', $event_slug)->firstOrFail(); // Throws 404 if not found

        return Inertia::render('Receptionist/scan/page', [
            'event' => $event,
            'userEndSessionDatetime' => null,
            'props' => \App\Models\EventVariables::getDefaultValue(),
            'client' => request()->route('client'),
        ]);
    }

    public function scan(Request $request, string $event_slug) // <--- CHANGE: Accept as string
    {
        // Manually find the event by its slug
        $event = Event::where('slug', $event_slug)->firstOrFail(); // Throws 404 if not found

        $request->validate([
            'ticket_code' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $ticket = Ticket::where('ticket_code', $request->ticket_code)
                ->where('event_id', $event->id)
                ->lockForUpdate()
                ->first();

            if (!$ticket) {
                throw new \Exception('Tiket tidak ditemukan atau tidak valid.');
            }

            $ticketOrder = TicketOrder::where('ticket_id', $ticket->id)
                ->orderByDesc('created_at')
                ->first();

            if (!$ticketOrder) {
                throw new \Exception('Order tiket tidak ditemukan.');
            }

            if ($ticketOrder->status === TicketOrderStatus::SCANNED->value) {
                throw new \Exception('Tiket sudah discan sebelumnya.');
            }

            $ticketOrder->status = TicketOrderStatus::SCANNED;
            $ticketOrder->save();

            DB::commit();

            return response()->json([
                'message' => "Tiket dengan kode {$request->ticket_code} berhasil discan.",
                'ticket' => $ticket,
                'ticketOrder' => $ticketOrder,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function selectEventForScan(Request $request)
    {
        // Fetch events that the admin can manage/scan for.
        // You might want to filter these based on the admin's permissions
        // For now, let's fetch all events for simplicity.
        $events = Event::all()->map(function ($event) {
            // Only return necessary data to frontend
            return [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug ?? $event->id, // Use slug if available, fallback to id
            ];
        });

        return Inertia::render('Receptionist/scan/SelectEventPage', [
            'events' => $events,
            // Pass minimal props needed for AuthenticatedLayout and common site features
            'props' => \App\Models\EventVariables::getDefaultValue(),
            'client' => request()->route('client'),
            'userEndSessionDatetime' => null, // Or your actual logic
        ]);
    }
}
