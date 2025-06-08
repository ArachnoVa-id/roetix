<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="3" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="80" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            backdrop-filter: blur(10px);
        }
        
        .success-icon svg {
            width: 40px;
            height: 40px;
            fill: white;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .order-info {
            background: #f8f9ff;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 5px solid #667eea;
        }
        
        .order-info h2 {
            color: #667eea;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .order-info h2 svg {
            width: 24px;
            height: 24px;
            margin-right: 10px;
            fill: #667eea;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .event-info {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            grid-column: 1 / -1;
        }
        
        .tickets-section {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: 2px solid #f0f0f0;
        }
        
        .tickets-section h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .tickets-section h3 svg {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            fill: #667eea;
        }
        
        .tickets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }
        
        .ticket-item {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .ticket-item::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(45deg);
        }
        
        .ticket-number {
            font-size: 18px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }
        
        .ticket-type {
            font-size: 12px;
            opacity: 0.8;
            position: relative;
            z-index: 1;
        }
        
        .cta-section {
            text-align: center;
            padding: 30px;
            background: #f8f9ff;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        
        .footer {
            background: #333;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .footer p {
            margin-bottom: 10px;
            opacity: 0.7;
        }
        
        .social-links {
            margin-top: 20px;
        }
        
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: white;
            text-decoration: none;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        
        .social-links a:hover {
            opacity: 1;
        }
        
        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .tickets-grid {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="header-content">
                <div class="success-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                    </svg>
                </div>
                <h1>Payment Successful!</h1>
                <p>Your tickets have been confirmed and are ready</p>
            </div>
        </div>
        
        <div class="content">
            <div class="order-info">
                <h2>
                    <svg viewBox="0 0 24 24">
                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                    </svg>
                    Order Details
                </h2>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Order Code</div>
                        <div class="info-value">{{ $order->order_code }}</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Total Amount</div>
                        <div class="info-value">Rp {{ number_format($order->total_price, 0, ',', '.') }}</div>
                    </div>
                    
                    <div class="event-info">
                        <div class="info-label">Event</div>
                        <div class="info-value">{{ $event->name }}</div>
                        @if($event->event_date)
                        <div style="margin-top: 10px;">
                            <div class="info-label">Date & Time</div>
                            <div class="info-value">{{ \Carbon\Carbon::parse($event->event_date)->format('F j, Y - g:i A') }}</div>
                        </div>
                        @endif
                        @if($event->venue)
                        <div style="margin-top: 10px;">
                            <div class="info-label">Venue</div>
                            <div class="info-value">{{ $event->venue }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="tickets-section">
                <h3>
                    <svg viewBox="0 0 24 24">
                        <path d="M15.58,16.8L12,14.5L8.42,16.8L9.5,12.68L6.21,10L10.46,9.74L12,5.8L13.54,9.74L17.79,10L14.5,12.68M20,12C20,10.89 20.9,10 22,10V6C22,4.89 21.1,4 20,4H4C2.89,4 2,4.89 2,6V10C3.11,10 4,10.89 4,12C4,13.11 3.11,14 2,14V18C2,19.11 2.89,20 4,20H20C21.11,20 22,19.11 22,18V14C20.9,14 20,13.11 20,12Z"/>
                    </svg>
                    Your Tickets
                </h3>
                
                <div class="tickets-grid">
                    @foreach($tickets as $ticket)
                    <div class="ticket-item">
                        <div class="ticket-number">{{ $ticket['seat_number'] }}</div>
                        <div class="ticket-type">{{ $ticket['ticket_type'] ?? 'General' }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            
            <div class="cta-section">
                <h3 style="margin-bottom: 15px; color: #333;">Ready for the Event?</h3>
                <p style="margin-bottom: 25px; color: #666;">Show this email or your order code at the venue entrance</p>
                <a href="#" class="cta-button">View Full Details</a>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>{{ config('app.name', 'EventApp') }}</strong></p>
            <p>Thank you for choosing us for your event experience!</p>
            <p>Need help? Contact us at support@eventapp.com</p>
            
            <div class="social-links">
                <a href="#">Facebook</a>
                <a href="#">Twitter</a>
                <a href="#">Instagram</a>
            </div>
        </div>
    </div>
</body>
</html>