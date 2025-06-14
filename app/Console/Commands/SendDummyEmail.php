<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\User;
use App\Models\UserContact;

class SendDummyEmail extends Command
{
    protected $signature = 'send-dummy:resend-email {--order-code= : Order code to test with} {--email= : Email address to send to}';
    protected $description = 'Test Resend email functionality';

    public function handle()
    {
        try {
            $orderCode = $this->option('order-code');
            $testEmail = $this->option('email');

            if (!$orderCode) {
                $this->error('You must provide an order code using --order-code option');
                return 1;
            }

            // Find the order
            $order = Order::where('order_code', $orderCode)->first();
            if (!$order) {
                $this->error("Order with code {$orderCode} not found");
                return 1;
            }

            $this->info('Preparing email...');
            $this->info('Sending email via ResendMailer...');

            // Send email using the modular method
            $result = $order->sendConfirmationEmail($testEmail, true);

            // Get recipient email the same way as in your original working code
            $user = User::find($order->user_id);
            $userContact = UserContact::find($user->contact_info);
            $recipientEmail = $testEmail ?: $userContact->email;

            $this->info("✅ Test email sent successfully to: {$recipientEmail}");
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
}
