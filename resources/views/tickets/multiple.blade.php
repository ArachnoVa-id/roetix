<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>E-Tickets</title>
  <style>
    body {
      font-family: 'Helvetica', 'Arial', sans-serif;
      margin: 0;
      padding: 0;
      color: #333;
    }

    .ticket-container {
      width: 100%;
      border: 2px solid #114B2C;
      border-radius: 8px;
      overflow: hidden;
      margin-bottom: 20px;
      page-break-inside: avoid;
      /* Prevent page breaks inside a ticket */
    }

    .ticket-header {
      text-align: center;
      padding: 15px;
      border-bottom: 1px solid #ccc;
    }

    .ticket-title {
      font-size: 18px;
      font-weight: bold;
      margin: 10px 0;
      text-align: center;
    }

    .ticket-info-row {
      width: 100%;
      border-bottom: 1px solid #ececec;
      padding: 8px 20px;
      display: block;
      box-sizing: border-box;
    }

    .section-title {
      font-size: 16px;
      font-weight: bold;
      margin: 0;
      padding: 10px 20px;
      background-color: #f5f5f5;
      border-bottom: 1px solid #ccc;
      border-top: 1px solid #ccc;
    }

    .ticket-label {
      font-weight: bold;
      display: block;
    }

    .ticket-value {
      display: block;
    }

    .ticket-footer {
      padding: 15px;
      font-size: 12px;
      text-align: left;
      background-color: #f8f8f8;
    }

    .terms-title {
      font-weight: bold;
      margin-bottom: 5px;
    }

    .terms-item {
      margin: 5px 0;
    }

    .text-center {
      text-align: center;
    }

    .header-row {
      width: 100%;
      display: block;
      padding: 10px 20px;
      box-sizing: border-box;
      border-bottom: 1px solid #ccc;
    }

    .transaction-date {
      width: 60%;
      float: left;
    }

    .ticket-id {
      width: 40%;
      float: right;
      text-align: right;
      font-weight: bold;
    }

    .clearfix {
      clear: both;
    }

    .verification-box {
      width: 80%;
      margin: 20px auto;
      padding: 15px;
      border: 1px dashed #ccc;
      text-align: center;
    }

    /* Completely remove page break styling */
    .page-break {
      page-break-before: always;
      height: 1px;
      background: transparent;
    }
  </style>
</head>

<body>
  @foreach ($tickets as $key => $ticket)
  @if ($key > 0)
  <div class="page-break"></div>
  @endif

  <div class="ticket-container">
    <!-- Header with Title -->
    <div class="ticket-header">
      <div class="ticket-title">E-Ticket for {{ $event->name }}</div>
    </div>

    <!-- Transaction Details -->
    <div class="header-row">
      <div class="transaction-date">Tanggal Transaksi: {{ $generatedAt }}</div>
      <div class="ticket-id">ID Tiket: #{{ strtoupper(substr($ticket->ticket_id, 0, 8)) }}</div>
      <div class="clearfix"></div>
    </div>

    <!-- Ticket Type and Seat -->
    <div class="section-title">{{ $ticket->ticket_type }} | Kursi
      {{ $ticket->seat ? $ticket->seat->seat_number : 'N/A' }}
    </div>

    <!-- Ticket Details -->
    <div class="ticket-info-row">
      <div class="ticket-label">Nama</div>
      <div class="ticket-value">{{ $user->first_name . ' ' . $user->last_name }}</div>
    </div>
    <div class="ticket-info-row">
      <div class="ticket-label">Email</div>
      <div class="ticket-value">{{ $user->email }}</div>
    </div>
    <div class="ticket-info-row">
      <div class="ticket-label">Tipe</div>
      <div class="ticket-value">{{ $ticket->ticket_type }}</div>
    </div>
    <div class="ticket-info-row">
      <div class="ticket-label">Kursi</div>
      <div class="ticket-value">{{ $ticket->seat ? $ticket->seat->seat_number : 'N/A' }}</div>
    </div>
    <div class="ticket-info-row">
      <div class="ticket-label">Harga</div>
      <div class="ticket-value">Rp{{ number_format($ticket->price, 0, ',', '.') }}</div>
    </div>
    <div class="ticket-info-row">
      <div class="ticket-label">Tempat</div>
      <div class="ticket-value">{{ $event->location ?? 'NovaTix' }}</div>
    </div>
    <div class="ticket-info-row">
      <div class="ticket-label">Tanggal</div>
      <div class="ticket-value">{{ $event->event_date ?? '2025-03-29 13:00:00' }}</div>
    </div>

    <!-- Verification Code (without QR placeholder) -->
    <!-- Verification Code (without QR placeholder) -->
    <div class="verification-box">
      <div style="margin-top: 10px;">Ticket Verification Code: {{ $ticket->ticket_id }}</div>
    </div>
    <div class="text-center">Scan untuk verifikasi</div>

    <!-- Terms and Conditions -->
    <div class="ticket-footer">
      <div class="terms-title">Syarat dan Ketentuan</div>
      <ul>
        <li class="terms-item">E-tiket ini adalah bukti sah kepemilikan tiket untuk menghadiri {{ $event->name }}</li>
        <li class="terms-item">E-tiket ini hanya berlaku untuk satu orang dan satu kali masuk</li>
        <li class="terms-item">E-tiket yang sudah dibeli tidak dapat dibatalkan atau ditukar</li>
        <li class="terms-item">Jaga keamanan data tiket anda. Pencurian data diluar hal teknis, diluar tanggung jawab
          kami</li>
      </ul>
    </div>
    @endforeach
</body>

</html>