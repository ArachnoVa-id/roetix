<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Confirmation</title>
  @php
    $eventVars = $event->eventVariables;
    $primaryColor = $eventVars->primary_color ?? '#667eea';
    $secondaryColor = $eventVars->secondary_color ?? '#764ba2';
    $textPrimaryColor = $eventVars->text_primary_color ?? '#333';
    $logo = $eventVars->logo ?? '';
    $texture = $eventVars->texture ?? '';
  @endphp
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      line-height: 1.6;
      color: {{ $textPrimaryColor }};
      background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);
      padding: 20px;
    }

    .email-container {
      max-width: 600px;
      margin: 0 auto;
      background: white;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      border: 3px solid {{ $primaryColor }};
    }

    .header {
      background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);

      @if ($texture)
        background-image: url('{{ $texture }}');
        background-repeat: repeat;
      @endif
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

      @if ($logo)
        background-image: url('{{ asset($logo) }}');
        background-size: 50px;
        background-repeat: no-repeat;
        background-position: center;
      @endif
    }

    .success-icon svg {
      width: 40px;
      height: 40px;
      fill: white;

      @if ($logo)
        display: none;
      @endif
    }

    .header h1 {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 10px;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .header p {
      font-size: 16px;
      opacity: 0.9;
    }

    .content {
      padding: 40px 30px;
    }

    .order-info {
      background: linear-gradient(135deg, {{ $primaryColor }}15, {{ $secondaryColor }}15);
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 30px;
      border-left: 5px solid {{ $primaryColor }};
      position: relative;
      overflow: hidden;
    }

    .order-info::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 100px;
      height: 100px;
      background: radial-gradient(circle, {{ $secondaryColor }}10 0%, transparent 70%);
      border-radius: 50%;
    }

    .order-info h2 {
      color: {{ $primaryColor }};
      font-size: 20px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      position: relative;
      z-index: 1;
    }

    .order-info h2 svg {
      width: 24px;
      height: 24px;
      margin-right: 10px;
      fill: {{ $primaryColor }};
    }

    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      margin-bottom: 20px;
      position: relative;
      z-index: 1;
    }

    .info-item {
      background: rgba(255, 255, 255, 0.9);
      padding: 15px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      border-top: 3px solid {{ $primaryColor }};
      backdrop-filter: blur(5px);
      margin-bottom: 12px;
    }

    .info-label {
      font-size: 12px;
      color: {{ $primaryColor }};
      text-transform: uppercase;
      font-weight: 600;
      letter-spacing: 0.5px;
      margin-bottom: 5px;
    }

    .info-value {
      font-size: 16px;
      font-weight: 600;
      color: {{ $textPrimaryColor }};
    }

    .event-info {
      background: rgba(255, 255, 255, 0.9);
      padding: 15px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      grid-column: 1 / -1;
      border-top: 3px solid {{ $secondaryColor }};
      backdrop-filter: blur(5px);
    }

    .tickets-section {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 30px;
      border: 2px solid {{ $primaryColor }}30;
      position: relative;
      overflow: hidden;
    }

    .tickets-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, {{ $primaryColor }}, {{ $secondaryColor }});
    }

    .tickets-section h3 {
      color: {{ $primaryColor }};
      font-size: 18px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
    }

    .tickets-section h3 svg {
      width: 20px;
      height: 20px;
      margin-right: 10px;
      fill: {{ $primaryColor }};
    }

    .tickets-grid {
      grid-template-columns: 1fr;
    }

    .ticket-details-grid {
      grid-template-columns: 1fr !important;
    }

    .ticket-qr-section {
      order: -1;
      margin-bottom: 15px;
    }

    .ticket-item {
      background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);
      color: white;
      padding: 15px;
      border-radius: 10px;
      text-align: center;
      position: relative;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .ticket-item::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
      transform: rotate(45deg);
    }

    .ticket-item::after {
      content: '';
      position: absolute;
      top: 5px;
      right: 5px;
      width: 20px;
      height: 20px;

      @if ($logo)
        background-image: url('{{ asset($logo) }}');
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
        opacity: 0.3;
      @endif
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
      background: linear-gradient(135deg, {{ $primaryColor }}10, {{ $secondaryColor }}10);
      border-radius: 15px;
      margin-bottom: 30px;
      position: relative;
      overflow: hidden;
    }

    .cta-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;

      @if ($texture)
        background-image: url('{{ $texture }}');
        background-repeat: repeat;
        opacity: 0.1;
      @endif
    }

    .cta-section>* {
      position: relative;
      z-index: 1;
    }

    .cta-button {
      display: inline-block;
      background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);
      color: white;
      padding: 15px 30px;
      text-decoration: none;
      border-radius: 50px;
      font-weight: 600;
      font-size: 16px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      box-shadow: 0 10px 30px {{ $primaryColor }}50;
    }

    .cta-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 35px {{ $primaryColor }}70;
    }

    .event-branding {
      text-align: center;
      margin-bottom: 20px;
      padding: 20px;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      border: 2px solid {{ $primaryColor }}20;
    }

    .event-logo {
      width: 80px;
      height: 80px;

      @if ($logo)
        background-image: url('{{ asset($logo) }}');
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
      @endif
      margin: 0 auto 15px;
      border-radius: 50%;
      border: 3px solid {{ $primaryColor }};
      padding: 10px;
    }

    .event-name {
      font-size: 24px;
      font-weight: bold;
      color: {{ $primaryColor }};
      margin-bottom: 5px;
    }

    .footer {
      background: linear-gradient(135deg, {{ $textPrimaryColor }}, {{ $textPrimaryColor }}dd);
      color: white;
      padding: 30px;
      text-align: center;
      position: relative;
    }

    .footer::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;

      @if ($texture)
        background-image: url('{{ $texture }}');
        background-repeat: repeat;
        opacity: 0.1;
      @endif
    }

    .footer>* {
      position: relative;
      z-index: 1;
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
      padding: 8px 12px;
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 20px;
    }

    .social-links a:hover {
      opacity: 1;
      background: rgba(255, 255, 255, 0.1);
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
        <h1>Payment Successful!</h1>
        <p>Your tickets for {{ $event->name }} have been confirmed</p>
      </div>
    </div>

    <div class="content">
      <!-- Event Branding Section -->
      @if ($logo)
        <div class="event-branding">
          <div class="event-logo"></div>
          <div class="event-name">{{ $event->name }}</div>
          <div style="color: {{ $secondaryColor }}; font-size: 14px;">
            {{ $event->getEventDate() ?? '' }} {{ $event->getEventTime() ? '• ' . $event->getEventTime() : '' }}
          </div>
        </div>
      @endif

      <div class="order-info">
        <h2>
          <svg viewBox="0 0 24 24">
            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z" />
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

          <div class="info-item">
            <div class="info-label">Order Date</div>
            <div class="info-value">{{ $order->created_at->format('d M Y, H:i') }}</div>
          </div>

          <div class="info-item">
            <div class="info-label">Payment Status</div>
            <div class="info-value" style="color: #22c55e;">
              <span
                style="display: inline-block; width: 8px; height: 8px; background: #22c55e; border-radius: 50%; margin-right: 8px;"></span>
              Paid
            </div>
          </div>

          <div class="event-info">
            <div class="info-label">Event Details</div>
            <div class="info-value" style="margin-bottom: 10px;">{{ $event->name }}</div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); margin-top: 15px;">
              <div>
                <div style="font-size: 12px; color: {{ $primaryColor }}; font-weight: 600; margin-bottom: 5px;">📍
                  LOCATION</div>
                <div style="font-size: 14px; margin-bottom: 5px;">{{ $event->location ?? 'TBA' }}</div>
              </div>
              <div>
                <div style="font-size: 12px; color: {{ $primaryColor }}; font-weight: 600; margin-bottom: 5px;">📅 DATE
                </div>
                <div style="font-size: 14px; margin-bottom: 5px;">{{ $event->getEventDate() ?? 'TBA' }}</div>
              </div>
              <div>
                <div style="font-size: 12px; color: {{ $primaryColor }}; font-weight: 600; margin-bottom: 5px;">🕒 TIME
                </div>
                <div style="font-size: 14px; margin-bottom: 5px;">{{ $event->getEventTime() ?? 'TBA' }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tickets Section -->
      <div class="tickets-section">
        <h3>
          <svg viewBox="0 0 24 24">
            <path d="M15.41,16.58L10.83,12L15.41,7.41L14,6L8,12L14,18L15.41,16.58Z" />
          </svg>
          Your E-Tickets ({{ count($tickets) }})
        </h3>

        @foreach ($tickets as $ticket)
          @php
            $qrUrl =
                $ticket->getQRCodeUrl() ?? ($ticket->getQRCodeUrlQuickChart() ?? ($ticket->getQRCodeUrlMonkey() ?? ''));
          @endphp

          <div
            style="
                border: 2px solid {{ $ticket->getColor() ?? $primaryColor }};
                border-radius: 15px;
                margin-bottom: 20px;
                overflow: hidden;
                background: linear-gradient(135deg, {{ $primaryColor }}05, {{ $secondaryColor }}05);
                position: relative;
            ">
            <!-- Ticket Header -->
            <div
              style="
            background: linear-gradient(135deg, {{ $ticket->getColor() ?? $primaryColor }}, {{ $ticket->getColor() ?? $primaryColor }}CC);
            color: white;
            padding: 15px 20px;
            font-weight: bold;
            display: grid;
            grid-template-columns: 1fr 1fr;
            text-align: center;
        ">
              <div style="font-size: 16px;">{{ $ticket->ticket_type }}</div>
              <div style="font-size: 12px; opacity: 0.8;">Ticket #{{ $ticket->id }}</div>
              <div
                style="
                background: rgba(255,255,255,0.2);
                padding: 8px 15px;
                border-radius: 20px;
                font-size: 14px;
                margin-top: 10px;
            ">
                {{ $ticket->seat ? 'Seat ' . $ticket->seat->seat_number : 'General Admission' }}
              </div>
            </div>

            <!-- Ticket Content -->
            <div style="padding: 20px;">
              <div style="display: grid; grid-template-columns: 2fr 1fr; align-items: start;">

                <!-- Ticket Details -->
                <div>
                  <h4
                    style="color: {{ $primaryColor }}; font-size: 14px; margin-bottom: 15px; display: flex; align-items: center;">
                    <div
                      style="
                            width: 16px;
                            height: 16px;
                            background: {{ $primaryColor }};
                            border-radius: 50%;
                            margin-right: 8px;
                        ">
                    </div>
                    Ticket Details
                  </h4>

                  <div style="font-size: 12px; line-height: 1.6; color: {{ $textPrimaryColor }};">
                    <div style="margin-bottom: 6px;">
                      <strong style="color: {{ $primaryColor }};">Name:</strong>
                      {{ $user->first_name . ' ' . $user->last_name }}
                    </div>
                    <div style="margin-bottom: 6px;">
                      <strong style="color: {{ $primaryColor }};">Email:</strong>
                      {{ $user->email }}
                    </div>
                    <div style="margin-bottom: 6px;">
                      <strong style="color: {{ $primaryColor }};">Event:</strong>
                      {{ $event->name }}
                    </div>
                    <div style="margin-bottom: 6px;">
                      <strong style="color: {{ $primaryColor }};">Location:</strong>
                      {{ $event->location ?? 'TBA' }}
                    </div>
                    <div style="margin-bottom: 6px;">
                      <strong style="color: {{ $primaryColor }};">Date:</strong>
                      {{ $event->getEventDate() ?? 'TBA' }}
                    </div>
                    <div style="margin-bottom: 6px;">
                      <strong style="color: {{ $primaryColor }};">Time:</strong>
                      {{ $event->getEventTime() ?? 'TBA' }}
                    </div>
                    @if ($ticket->ticket_code)
                      <div style="margin-bottom: 12px;">
                        <strong style="color: {{ $primaryColor }};">Ticket Code:</strong>
                        <span
                          style="
                                background: {{ $secondaryColor }}20;
                                padding: 2px 8px;
                                border-radius: 5px;
                                font-family: monospace;
                                font-size: 11px;
                            ">{{ $ticket->ticket_code }}</span>
                      </div>
                    @endif
                  </div>
                </div>

                <!-- QR Code Section -->
                <div style="text-align: center;">
                  <div
                    style="
                        color: {{ $primaryColor }};
                        font-size: 11px;
                        font-weight: bold;
                        margin-bottom: 8px;
                    ">
                    SCAN FOR ENTRY
                  </div>
                  @if ($qrUrl)
                    <img src="{{ $qrUrl }}" alt="QR Code"
                      style="
                        display: block; /* Ensures the image is treated as a block element */
                        margin: 0 auto; /* Centers the image horizontally */
                        width: 100%;
                        height: auto; /* Maintains aspect ratio */
                        max-width: 300px; /* Optional: Set a max width for the image */
                        border: 5px solid {{ $ticket->getColor() }};
                        border-radius: 12px;
                        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                    ">
                  @else
                    <p>QR Code unavailable</p>
                  @endif
                  <div
                    style="
                        color: {{ $textPrimaryColor }};
                        font-size: 10px;
                        margin-top: 6px;
                        opacity: 0.7;
                    ">
                    Present at venue entrance
                  </div>
                </div>

              </div>
            </div>

            <!-- Ticket Footer -->
            <div
              style="
            background: rgba(255,255,255,0.7);
            padding: 10px 20px;
            font-size: 10px;
            color: {{ $textPrimaryColor }};
            opacity: 0.8;
            border-top: 1px solid {{ $primaryColor }}20;
        ">
              <div style="display: grid; grid-template-columns: 1fr; text-align: center;">
                <div>
                  <strong>Order Date:</strong> {{ $ticket->order_date ?? $order->created_at->format('M d, Y') }}
                </div>
                <div>
                  Valid for single entry only
                </div>
              </div>
            </div>
          </div>
        @endforeach

        <!-- Important Notice -->
        <div
          style="margin-top: 20px; padding: 15px; background: linear-gradient(135deg, {{ $primaryColor }}10, {{ $secondaryColor }}10); border-radius: 10px; border: 1px solid {{ $primaryColor }}30;">
          <div style="font-size: 12px; color: {{ $primaryColor }}; font-weight: 600; margin-bottom: 5px;">📱 IMPORTANT
            NOTES</div>
          <div style="font-size: 11px; line-height: 1.5; color: {{ $textPrimaryColor }};">
            <div style="margin-bottom: 4px;">• These e-tickets are valid proof of purchase for {{ $event->name }}
            </div>
            <div style="margin-bottom: 4px;">• Each ticket is valid for one person and single entry only</div>
            <div style="margin-bottom: 4px;">• Please arrive early and have your QR code ready for scanning</div>
            <div>• Keep your ticket information secure and confidential</div>
          </div>
        </div>
      </div>

      <!-- Call to Action Section -->
      {{-- <div class="cta-section">
        <h3 style="color: {{ $primaryColor }}; margin-bottom: 15px; font-size: 18px;">What's Next?</h3>
        <p style="margin-bottom: 20px; color: {{ $textPrimaryColor }}; opacity: 0.8;">
          Check your email for detailed e-tickets and event information
        </p>

        <div style="margin-top: 25px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
          <div style="text-align: center; padding: 15px;">
            <div
              style="width: 40px; height: 40px; background: {{ $primaryColor }}20; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
              <svg style="width: 20px; height: 20px; fill: {{ $primaryColor }};" viewBox="0 0 24 24">
                <path
                  d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8Z" />
              </svg>
            </div>
            <div style="font-size: 12px; color: {{ $primaryColor }}; font-weight: 600;">E-TICKETS</div>
            <div style="font-size: 11px; opacity: 0.7;">Check your email</div>
          </div>

          <div style="text-align: center; padding: 15px;">
            <div
              style="width: 40px; height: 40px; background: {{ $secondaryColor }}20; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
              <svg style="width: 20px; height: 20px; fill: {{ $secondaryColor }};" viewBox="0 0 24 24">
                <path
                  d="M12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5M12,2A7,7 0 0,0 5,9C5,14.25 12,22 12,22S19,14.25 19,9A7,7 0 0,0 12,2Z" />
              </svg>
            </div>
            <div style="font-size: 12px; color: {{ $secondaryColor }}; font-weight: 600;">VENUE</div>
            <div style="font-size: 11px; opacity: 0.7;">Save the location</div>
          </div>

          <div style="text-align: center; padding: 15px;">
            <div
              style="width: 40px; height: 40px; background: {{ $primaryColor }}20; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
              <svg style="width: 20px; height: 20px; fill: {{ $primaryColor }};" viewBox="0 0 24 24">
                <path
                  d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z" />
              </svg>
            </div>
            <div style="font-size: 12px; color: {{ $primaryColor }}; font-weight: 600;">CALENDAR</div>
            <div style="font-size: 11px; opacity: 0.7;">Add to calendar</div>
          </div>
        </div>
      </div> --}}

      <!-- Customer Support -->
      <div
        style="background: rgba(255, 255, 255, 0.95); border-radius: 15px; padding: 25px; margin-bottom: 30px; border: 2px solid {{ $primaryColor }}30;">
        <h3
          style="color: {{ $primaryColor }}; font-size: 16px; margin-bottom: 15px; display: flex; align-items: center;">
          <svg style="width: 18px; height: 18px; margin-right: 8px; fill: {{ $primaryColor }};" viewBox="0 0 24 24">
            <path
              d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M7.07,18.28C7.5,17.38 10.12,16.5 12,16.5C13.88,16.5 16.5,17.38 16.93,18.28C15.57,19.36 13.86,20 12,20C10.14,20 8.43,19.36 7.07,18.28M18.36,16.83C16.93,15.09 13.46,14.5 12,14.5C10.54,14.5 7.07,15.09 5.64,16.83C4.62,15.5 4,13.82 4,12C4,7.59 7.59,4 12,4C16.41,4 20,7.59 20,12C20,13.82 19.38,15.5 18.36,16.83M12,6C10.06,6 8.5,7.56 8.5,9.5C8.5,11.44 10.06,13 12,13C13.94,13 15.5,11.44 15.5,9.5C15.5,7.56 13.94,6 12,6Z" />
          </svg>
          Need Help?
        </h3>
        <div style="font-size: 14px; color: {{ $textPrimaryColor }}; opacity: 0.8; margin-bottom: 15px;">
          If you have any questions about your order or need assistance, our support team is here to help.
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));">
          <a href={{ 'mailto:' . config('app.email', '') }}
            style="display: flex; align-items: center; padding: 8px 12px; background: {{ $primaryColor }}10; color: {{ $primaryColor }}; text-decoration: none; border-radius: 8px; font-size: 12px; font-weight: 600; margin-bottom: 5px;">
            <svg style="width: 14px; height: 14px; margin-right: 6px; fill: {{ $primaryColor }};"
              viewBox="0 0 24 24">
              <path
                d="M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z" />
            </svg>
            Email Support
          </a>
          <a href={{ $eventVars->contact_person }}
            style="display: flex; align-items: center; padding: 8px 12px; background: {{ $primaryColor }}10; color: {{ $primaryColor }}; text-decoration: none; border-radius: 8px; font-size: 12px; font-weight: 600;">
            <svg style="width: 14px; height: 14px; margin-right: 6px; fill: {{ $primaryColor }};"
              viewBox="0 0 24 24">
              <path
                d="M6.62,10.79C8.06,13.62 10.38,15.94 13.21,17.38L15.41,15.18C15.69,14.9 16.08,14.82 16.43,14.93C17.55,15.3 18.75,15.5 20,15.5A1,1 0 0,1 21,16.5V20A1,1 0 0,1 20,21A17,17 0 0,1 3,4A1,1 0 0,1 4,3H7.5A1,1 0 0,1 8.5,4C8.5,5.25 8.7,6.45 9.07,7.57C9.18,7.92 9.1,8.31 8.82,8.59L6.62,10.79Z" />
            </svg>
            Call Us
          </a>
        </div>
      </div>
    </div>

    <div class="footer">
      <p style="font-size: 16px; font-weight: 600; margin-bottom: 5px;">Thank you for choosing us!</p>
      <p style="font-size: 14px;">We can't wait to see you at {{ $event->name }}</p>

      <div
        style="margin: 20px 0; padding: 20px 0; border-top: 1px solid rgba(255,255,255,0.2); border-bottom: 1px solid rgba(255,255,255,0.2);">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); text-align: center;">
          <div>
            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 10px; color: white;">Event Information</h4>
            <p style="font-size: 12px; margin-bottom: 5px;">{{ $event->name }}</p>
            <p style="font-size: 12px; margin-bottom: 5px;">{{ $event->location ?? 'Location TBA' }}</p>
            <p style="font-size: 12px;">{{ $event->getEventDate() ?? 'Date TBA' }}</p>
          </div>
          <div>
            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 10px; color: white;">Contact Us</h4>
            {{-- <p style="font-size: 12px; margin-bottom: 5px;">📧 {{ config('app.email', '') }}</p> --}}
            <p style="font-size: 12px; margin-bottom: 5px;">📞 {{ $eventVars->contact_person }}</p>
            <p style="font-size: 12px;">🌐 {{ 'https://' . $event->slug . '.' . config('app.domain', '') }}</p>
          </div>
        </div>
      </div>

      {{-- <div class="social-links">
        <a href="#" title="Facebook">
          <svg style="width: 16px; height: 16px; fill: currentColor; vertical-align: middle;" viewBox="0 0 24 24">
            <path
              d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
          </svg>
          Facebook
        </a>
        <a href="#" title="Twitter">
          <svg style="width: 16px; height: 16px; fill: currentColor; vertical-align: middle;" viewBox="0 0 24 24">
            <path
              d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
          </svg>
          Twitter
        </a>
        <a href="#" title="Instagram">
          <svg style="width: 16px; height: 16px; fill: currentColor; vertical-align: middle;" viewBox="0 0 24 24">
            <path
              d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
          </svg>
          Instagram
        </a>
        <a href="#" title="LinkedIn">
          <svg style="width: 16px; height: 16px; fill: currentColor; vertical-align: middle;" viewBox="0 0 24 24">
            <path
              d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
          </svg>
          LinkedIn
        </a>
      </div> --}}

      <div>
        <p style="font-size: 12px; opacity: 0.6; margin-bottom: 8px;">
          This email was sent to you because you purchased tickets for {{ $event->name }}.
        </p>
        <p style="font-size: 11px; opacity: 0.5; margin-bottom: 5px;">
          Order #{{ $order->order_code }} • {{ $order->created_at->format('M d, Y') }}
        </p>
        <p style="font-size: 11px; opacity: 0.5;">
          © {{ date('Y') }} {{ config('app.name', 'Event Platform') }}. All rights reserved.
        </p>
      </div>
    </div>
  </div>
</body>

</html>
