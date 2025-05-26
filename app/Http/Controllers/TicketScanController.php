<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Models\User;
use App\Enums\TicketOrderStatus;
use App\Enums\UserRole; // Pastikan ini diimpor jika digunakan
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
                    'location' => $event->location, // Tambahkan lokasi jika ada
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
                // Temukan tiket berdasarkan kode dan event_id
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

                // Dapatkan order tiket yang paling relevan (misalnya yang belum discan)
                $ticketOrder = $ticket->ticketOrders()
                    ->where('status', '!=', TicketOrderStatus::SCANNED->value) // Ambil yang belum discan
                    ->orderByDesc('created_at') // Ambil yang terbaru jika ada beberapa
                    ->first();

                if (!$ticketOrder) {
                    // Jika tidak ada order yang belum discan, periksa apakah sudah discan
                    $alreadyScannedOrder = $ticket->ticketOrders()
                        ->where('status', TicketOrderStatus::SCANNED->value)
                        ->orderByDesc('created_at')
                        ->first();

                    if ($alreadyScannedOrder) {
                        DB::rollBack();
                        Log::info('Attempt to scan already scanned ticket.', [
                            'ticket_code' => $ticketCode,
                            'ticket_order_id' => $alreadyScannedOrder->id,
                            'user_id' => $user->id
                        ]);
                        return response()->json([
                            'message' => "Ticket {$ticketCode} has already been scanned.",
                            'error' => 'ALREADY_SCANNED',
                            'data' => [ // Kirim data tiket yang sudah discan
                                'id' => $alreadyScannedOrder->id,
                                'ticket_code' => $ticket->ticket_code,
                                'scanned_at' => $alreadyScannedOrder->updated_at->toIso8601String(), // Atau kolom yang sesuai
                                'status' => 'error', // Status untuk frontend
                                'message' => "Ticket {$ticketCode} has already been scanned.",
                                'attendee_name' => $ticket->attendee_name, // Asumsi ada kolom ini di model Ticket
                                'ticket_type' => $ticket->type->name, // Asumsi ada relasi type
                            ]
                        ], 409);
                    }

                    // Jika tidak ada order sama sekali, atau semua dalam status non-scannable (dan bukan 'SCANNED')
                    DB::rollBack();
                    Log::error('Ticket order not found or not in scannable state for ticket.', [
                        'ticket_id' => $ticket->id,
                        'ticket_code' => $ticketCode,
                        'event_id' => $event->id
                    ]);
                    return response()->json([
                        'message' => 'Ticket order not found or not in a scannable state for this ticket.',
                        'error' => 'ORDER_NOT_FOUND_OR_INVALID_STATE'
                    ], 404);
                }

                // Periksa apakah tiket dalam status yang dapat dipindai (hanya jika Anda tidak menggunakan `where('status', '!=', TicketOrderStatus::SCANNED->value)` di atas)
                // $scannableStatuses = [
                //     TicketOrderStatus::ENABLED->value,
                //     // TicketOrderStatus::DEACTIVATED->value, // Hati-hati dengan ini, jika DEACTIVATED berarti tidak boleh discan
                // ];
                // if (!in_array($ticketOrder->status, $scannableStatuses)) {
                //     DB::rollBack();
                //     Log::info('Attempt to scan ticket with invalid status.', [
                //         'ticket_code' => $ticketCode,
                //         'current_status' => $ticketOrder->status,
                //         'ticket_order_id' => $ticketOrder->id,
                //         'user_id' => $user->id
                //     ]);
                //     return response()->json([
                //         'message' => "Ticket {$ticketCode} is not in a scannable status. Current status: {$ticketOrder->status}",
                //         'error' => 'INVALID_STATUS'
                //     ], 400);
                // }

                // Update ticket status to scanned
                $ticketOrder->status = TicketOrderStatus::SCANNED;
                $ticketOrder->save();

                DB::commit();

                Log::info('Ticket successfully scanned.', [
                    'ticket_code' => $ticketCode,
                    'ticket_order_id' => $ticketOrder->id,
                    'event_id' => $event->id,
                ]);

                // Mengembalikan data tiket yang dipindai secara lengkap
                return response()->json([
                    'message' => "Ticket {$ticketCode} successfully scanned.",
                    'success' => true,
                    'data' => [
                        'id' => $ticketOrder->id, // Gunakan ID order tiket sebagai ID unik
                        'ticket_code' => $ticket->ticket_code,
                        'scanned_at' => $ticketOrder->updated_at->toIso8601String(), // Waktu scan
                        'status' => 'success', // Status untuk frontend
                        'message' => "Ticket {$ticketCode} successfully scanned.",
                        // Ambil data tambahan dari model Ticket atau TicketOrder
                        'attendee_name' => $ticket->attendee_name, // Asumsi ada kolom ini di model Ticket
                        'ticket_type' => $ticket->ticketType->name, // Asumsi ada relasi ticketType di Ticket model
                    ]
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Database transaction failed for scan: " . $e->getMessage(), [
                    'ticket_code' => $ticketCode,
                    'event_slug' => $event_slug,
                    'user_id' => $user->id,
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'message' => 'An error occurred during ticket processing.',
                    'error' => 'TRANSACTION_ERROR'
                ], 500);
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
                'message' => 'An unexpected error occurred while scanning the ticket.',
                'error' => 'SERVER_ERROR'
            ], 500);
        }
    }

    /**
     * Get the history of scanned tickets for an event.
     */
    public function getScannedHistory(Request $request, string $client, string $event_slug): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user || !User::find($user->id)?->isReceptionist()) {
                return response()->json([
                    'message' => 'Unauthorized access.',
                    'error' => 'UNAUTHORIZED'
                ], 403);
            }

            $event = Event::where('slug', $event_slug)->first();
            if (!$event) {
                return response()->json([
                    'message' => 'Event not found.',
                    'error' => 'EVENT_NOT_FOUND'
                ], 404);
            }

            // Ambil semua TicketOrder yang berstatus 'SCANNED' untuk event ini
            // Sertakan relasi 'ticket' dan 'ticket.ticketType' jika ingin menampilkan detail
            $scannedOrders = TicketOrder::whereHas('ticket', function ($query) use ($event) {
                $query->where('event_id', $event->id);
            })
                ->with(['ticket.ticketType']) // Load relasi ticket dan ticketType
                ->where('status', TicketOrderStatus::SCANNED->value)
                ->orderByDesc('updated_at') // Urutkan berdasarkan waktu scan terbaru
                ->get();

            // Format data sesuai dengan yang diharapkan oleh frontend
            $formattedHistory = $scannedOrders->map(function ($order) {
                return [
                    'id' => $order->id, // ID unik dari order tiket
                    'ticket_code' => $order->ticket->ticket_code,
                    'scanned_at' => $order->updated_at->toIso8601String(), // Waktu scan
                    'status' => 'success', // Selalu 'success' untuk riwayat yang sudah discan
                    'message' => "Ticket {$order->ticket->ticket_code} was scanned.",
                    'attendee_name' => $order->ticket->attendee_name, // Sesuaikan dengan nama kolom di model Ticket
                    'ticket_type' => $order->ticket->ticketType->name ?? 'N/A', // Nama tipe tiket
                ];
            });

            return response()->json([
                'message' => 'Scanned tickets history fetched successfully.',
                'data' => $formattedHistory
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error fetching scanned history for event {$event_slug}: " . $e->getMessage(), [
                'event_slug' => $event_slug,
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
