<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\User;
use App\Models\UserContact;
use App\Models\Ticket;
use App\Services\ResendMailer;

class SendDummyEmail extends Command
{
    protected $signature = 'send-dummy:resend-email {--order-code= : Order code to test with} {--email= : Email address to send to}';
    protected $description = 'Test Resend email functionality';

    public function handle()
    {
        try {
            $orderCode = $this->option('order-code');
            $testEmail = $this->option('email');

            if ($orderCode) {
                // Use existing order
                $order = Order::where('order_code', $orderCode)->first();
                if (!$order) {
                    $this->error("Order with code {$orderCode} not found");
                    return 1;
                }

                $user = User::find($order->user_id);
                $userContact = UserContact::find($user->contact_info);
                $event = $order->getSingleEvent();

                // Get real tickets
                $ticketOrders = \App\Models\TicketOrder::where('order_id', $order->id)->get();
                $ticketIds = $ticketOrders->pluck('ticket_id');
                $tickets = \App\Models\Ticket::whereIn('id', $ticketIds)->get();
            } else {
                $this->error('You must provide an order code using --order-code option');
                return 1;
            }

            $this->info('Preparing email...');

            // Render email template
            $emailHtml = view('emails.order-confirmation', [
                'order' => $order,
                'event' => $event,
                'tickets' => $tickets,
                'user' => $user,
                'userContact' => $userContact
            ])->render();

            $this->info('Sending email via ResendMailer...');

            // Send email
            $resendMailer = new ResendMailer();
            $result = $resendMailer->send(
                to: $testEmail ?: $userContact->email,
                subject: "🎫 TEST - Your Tickets are Confirmed - Order #{$order->order_code}",
                html: $emailHtml
            );

            $this->info("✅ Test email sent successfully to: {$userContact->email}");
            $this->info("📧 Order Code: {$order->order_code}");

            if (is_array($result) || is_object($result)) {
                $this->info("📋 Response: " . json_encode($result, JSON_PRETTY_PRINT));
            } else {
                $this->info("📋 Response: " . $result);
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to send test email: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    private function createMockTicket($id, $seatNumber, $ticketCategory, $price)
    {
        $ticket = new Ticket();
        $ticket->id = $id;
        $ticket->ticket_code = 'TICK-' . str_pad($id, 6, '0', STR_PAD_LEFT);
        $ticket->ticket_type = $ticketCategory->name;
        $ticket->price = $price;
        $ticket->order_date = now()->format('M d, Y');

        // Mock seat relationship
        $seat = new \stdClass();
        $seat->seat_number = $seatNumber;
        $ticket->setRelation('seat', $seat);

        // Mock ticketCategory relationship
        $ticket->setRelation('ticketCategory', $ticketCategory);

        // Add methods that the blade template expects
        $ticket->getColor = function () {
            return '#667eea'; // Return primary color
        };

        $ticket->getQRCode = function () {
            // Return a simple base64 encoded SVG QR code placeholder
            $qrSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
                <rect width="100" height="100" fill="white"/>
                <rect x="10" y="10" width="10" height="10" fill="black"/>
                <rect x="30" y="10" width="10" height="10" fill="black"/>
                <rect x="50" y="10" width="10" height="10" fill="black"/>
                <rect x="70" y="10" width="10" height="10" fill="black"/>
                <rect x="10" y="30" width="10" height="10" fill="black"/>
                <rect x="50" y="30" width="10" height="10" fill="black"/>
                <rect x="10" y="50" width="10" height="10" fill="black"/>
                <rect x="30" y="50" width="10" height="10" fill="black"/>
                <rect x="70" y="50" width="10" height="10" fill="black"/>
                <rect x="10" y="70" width="10" height="10" fill="black"/>
                <rect x="30" y="70" width="10" height="10" fill="black"/>
                <rect x="50" y="70" width="10" height="10" fill="black"/>
                <rect x="70" y="70" width="10" height="10" fill="black"/>
                <text x="50" y="95" text-anchor="middle" font-size="8" fill="black">TEST QR</text>
            </svg>';
            return base64_encode($qrSvg);
        };

        return $ticket;
    }
}
