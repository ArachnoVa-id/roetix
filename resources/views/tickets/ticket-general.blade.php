<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>E-Ticket</title>
</head>

@php
  $eventVars = $event->eventVariables;
@endphp
@foreach ($tickets as $ticket)

  <body class="font-sans">
    <div
      style="
    border: 3px solid {{ $eventVars->primary_color }};
    color: {{ $eventVars->text_primary_color }};
    background-color: white;
    width: 100%;
    height: 300px;
    overflow: hidden;
    position: relative;
  ">
      <!-- Background Image -->
      <div
        style="
        position: absolute;
        opacity: .5;
        width: 100%;
        height: 100%;
        z-index: 1;
    ">
        <div style="
        position: relative
        width: 100%;
        height: 100%;
      ">
          <div
            style="
          position: absolute;
          top: 55%;
          left: 50%;
          transform: translate(-50%, -50%);
          width: 80px;
          height: 80px;
          background-image: url('{{ asset($eventVars->logo) }}');
          background-position: center;
          background-repeat: no-repeat;
          background-size: contain;
          border-radius: 10%;
        ">
          </div>
        </div>
      </div>
      <div style="
      position: relative;
      z-index: 2;">
        <!-- Header with Title -->
        <div
          style="
      text-align: center;
      padding: 8px;
      border-bottom: 1px solid {{ $eventVars->text_primary_color }};
      background-color: {{ $eventVars->secondary_color }};
      background-image: url('{{ $eventVars->texture }}');
      background-repeat: repeat;
      font-size: 0.75rem;
    ">
          <h1 style="font-size: 1rem; font-weight: bold;">E-Ticket for {{ $event->name }}</h1>
        </div>

        <!-- Transaction Details -->
        <div style="padding: 4px 12px;">
          <table style="width: 100%; color: black; font-size: 0.7rem;">
            <tr>
              <td><strong>Tanggal Transaksi:</strong> {{ $ticket->order_date }}</td>
              <td style="text-align: right;"><strong>ID Tiket:</strong> #{{ $ticket->id }}</td>
            </tr>
          </table>
        </div>

        <!-- Ticket Type and Seat -->
        <div
          style="
        background-color: {{ $ticket->getColor() }};
        color: {{ $eventVars->text_primary_color }};
        padding: 4px 12px;
        margin-bottom: 8px;
        font-weight: bold;
        font-size: 0.75rem;
      ">
          <table style="width: 100%;">
            <tr>
              <td>{{ $ticket->ticket_type }}</td>
              <td style="text-align: right;">Kursi {{ $ticket->seat ? $ticket->seat->seat_number : 'N/A' }}</td>
            </tr>
          </table>
        </div>

        <!-- Main Content (Ticket Details + Barcode) -->
        <div style="padding: 0 12px; color: black; font-size: 0.7rem; position: relative;">
          <!-- Ticket Details and Barcode Table -->
          <table style="width: 100%;">
            <tr>
              <!-- Ticket Details -->
              <td style="vertical-align: top;">
                <div><strong>Nama:</strong> {{ $user->first_name . ' ' . $user->last_name }}</div>
                <div><strong>Email:</strong> {{ $user->email }}</div>
                <div><strong>Tempat:</strong> {{ $event->location ?? 'N/A' }}</div>
                <div><strong>Tanggal:</strong> {{ $event->getEventDate() ?? 'N/A' }}</div>
                <div><strong>Waktu:</strong> {{ $event->getEventTime() ?? 'N/A' }}</div>
                <div><strong>Ticket Code:</strong> {{ $ticket->ticket_code ?? 'N\A' }}</div>
                <div style="padding: 16px 0px 0px 0px;"><strong>Syarat dan Ketentuan:</strong></div>
                <!-- Terms and Conditions -->
                <div style="font-size: 0.65rem;">1. E-tiket ini adalah bukti sah kepemilikan tiket untuk menghadiri
                  acara
                  {{ $event->name }}</div>
                <div style="font-size: 0.65rem;">2. E-tiket ini hanya berlaku untuk satu orang dan satu kali masuk</div>
                <div style="font-size: 0.65rem;">3. E-tiket yang sudah dibeli tidak dapat dibatalkan atau ditukar</div>
                <div style="font-size: 0.65rem;">4. Jaga keamanan data tiket anda. Pencurian data diluar tanggung jawab
                  kami</div>
              </td>

              <!-- QR Code Section -->
              <td style="vertical-align: top; text-align: right;">
                <img src="data:image/svg+xml;base64,{{ $ticket->getQRCode() }}" alt="QR Code"
                  style="width: 155px; border: 5px solid {{ $ticket->getColor() }};">
              </td>
            </tr>
          </table>
        </div>
      </div>
  </body>
@endforeach

</html>
