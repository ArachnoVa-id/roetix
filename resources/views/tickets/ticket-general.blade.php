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
    background: linear-gradient(135deg, {{ $eventVars->primary_color }}15, {{ $eventVars->secondary_color }}15);
    width: 100%;
    min-height: 350px;
    overflow: hidden;
    position: relative;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    margin: 20px 0;
  ">
      <!-- Decorative Background Pattern -->
      <div
        style="
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: 
          radial-gradient(circle at 20% 80%, {{ $eventVars->primary_color }}10 0%, transparent 50%),
          radial-gradient(circle at 80% 20%, {{ $eventVars->secondary_color }}10 0%, transparent 50%);
        z-index: 1;
      ">
      </div>

      <!-- Background Logo -->
      <div
        style="
        position: absolute;
        opacity: 0.08;
        width: 100%;
        height: 100%;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: center;
      ">
        <div
          style="
          width: 200px;
          height: 200px;
          background-image: url('{{ asset($eventVars->logo) }}');
          background-position: center;
          background-repeat: no-repeat;
          background-size: contain;
          filter: grayscale(100%);
        ">
        </div>
      </div>

      <!-- Main Content Container -->
      <div style="position: relative; z-index: 3;">
        
        <!-- Header with Title -->
        <div
          style="
          text-align: center;
          padding: 20px;
          background: linear-gradient(135deg, {{ $eventVars->primary_color }}, {{ $eventVars->secondary_color }});
          background-image: url('{{ $eventVars->texture }}');
          background-repeat: repeat;
          color: white;
          border-radius: 17px 17px 0 0;
          position: relative;
          overflow: hidden;
        ">
          <!-- Header decoration -->
          <div style="
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(45deg);
          "></div>
          
          <div style="position: relative; z-index: 1;">
            <div style="
              width: 60px;
              height: 60px;
              background-image: url('{{ asset($eventVars->logo) }}');
              background-position: center;
              background-repeat: no-repeat;
              background-size: contain;
              margin: 0 auto 15px;
              background-color: rgba(255,255,255,0.2);
              border-radius: 50%;
              padding: 10px;
              backdrop-filter: blur(10px);
            "></div>
            <h1 style="font-size: 1.4rem; font-weight: bold; margin: 0; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
              E-Ticket for {{ $event->name }}
            </h1>
          </div>
        </div>

        <!-- Transaction Details Bar -->
        <div style="
          padding: 15px 20px;
          background: rgba(255,255,255,0.9);
          backdrop-filter: blur(10px);
          border-bottom: 1px solid {{ $eventVars->primary_color }}30;
        ">
          <table style="width: 100%; color: {{ $eventVars->text_primary_color }}; font-size: 0.8rem;">
            <tr>
              <td>
                <div style="display: flex; align-items: center;">
                  <div style="
                    width: 8px;
                    height: 8px;
                    background: {{ $eventVars->primary_color }};
                    border-radius: 50%;
                    margin-right: 8px;
                  "></div>
                  <strong>Tanggal Transaksi:</strong> {{ $ticket->order_date }}
                </div>
              </td>
              <td style="text-align: right;">
                <div style="
                  background: {{ $eventVars->primary_color }};
                  color: white;
                  padding: 4px 12px;
                  border-radius: 15px;
                  font-weight: bold;
                  font-size: 0.7rem;
                  display: inline-block;
                ">
                  ID: #{{ $ticket->id }}
                </div>
              </td>
            </tr>
          </table>
        </div>

        <!-- Ticket Type and Seat Banner -->
        <div
          style="
          background: linear-gradient(135deg, {{ $ticket->getColor() }}, {{ $ticket->getColor() }}CC);
          color: white;
          padding: 15px 20px;
          margin: 0;
          font-weight: bold;
          font-size: 0.9rem;
          position: relative;
          overflow: hidden;
        ">
          <!-- Banner decoration -->
          <div style="
            position: absolute;
            top: 0;
            right: -20px;
            width: 40px;
            height: 100%;
            background: repeating-linear-gradient(
              45deg,
              rgba(255,255,255,0.1),
              rgba(255,255,255,0.1) 5px,
              transparent 5px,
              transparent 10px
            );
          "></div>
          
          <table style="width: 100%; position: relative; z-index: 1;">
            <tr>
              <td>
                <div style="display: flex; align-items: center;">
                  <div style="
                    width: 12px;
                    height: 12px;
                    background: rgba(255,255,255,0.3);
                    border-radius: 50%;
                    margin-right: 10px;
                  "></div>
                  {{ $ticket->ticket_type }}
                </div>
              </td>
              <td style="text-align: right;">
                <div style="
                  background: rgba(255,255,255,0.2);
                  padding: 5px 15px;
                  border-radius: 20px;
                  backdrop-filter: blur(5px);
                ">
                  Kursi {{ $ticket->seat ? $ticket->seat->seat_number : 'N/A' }}
                </div>
              </td>
            </tr>
          </table>
        </div>

        <!-- Main Content Area -->
        <div style="padding: 25px 20px; background: rgba(255,255,255,0.95);">
          <table style="width: 100%;">
            <tr>
              <!-- Ticket Details -->
              <td style="vertical-align: top; width: 60%;">
                <div style="
                  background: white;
                  padding: 20px;
                  border-radius: 15px;
                  box-shadow: 0 5px 15px rgba(0,0,0,0.08);
                  border-left: 4px solid {{ $eventVars->primary_color }};
                ">
                  <h3 style="
                    color: {{ $eventVars->primary_color }};
                    font-size: 1rem;
                    margin: 0 0 15px 0;
                    display: flex;
                    align-items: center;
                  ">
                    <div style="
                      width: 20px;
                      height: 20px;
                      background: {{ $eventVars->primary_color }};
                      border-radius: 50%;
                      margin-right: 8px;
                    "></div>
                    Detail Tiket
                  </h3>
                  
                  <div style="color: {{ $eventVars->text_primary_color }}; font-size: 0.75rem; line-height: 1.6;">
                    <div style="margin-bottom: 8px;">
                      <strong style="color: {{ $eventVars->primary_color }};">Nama:</strong> 
                      {{ $user->first_name . ' ' . $user->last_name }}
                    </div>
                    <div style="margin-bottom: 8px;">
                      <strong style="color: {{ $eventVars->primary_color }};">Email:</strong> 
                      {{ $user->email }}
                    </div>
                    <div style="margin-bottom: 8px;">
                      <strong style="color: {{ $eventVars->primary_color }};">Tempat:</strong> 
                      {{ $event->location ?? 'N/A' }}
                    </div>
                    <div style="margin-bottom: 8px;">
                      <strong style="color: {{ $eventVars->primary_color }};">Tanggal:</strong> 
                      {{ $event->getEventDate() ?? 'N/A' }}
                    </div>
                    <div style="margin-bottom: 8px;">
                      <strong style="color: {{ $eventVars->primary_color }};">Waktu:</strong> 
                      {{ $event->getEventTime() ?? 'N/A' }}
                    </div>
                    <div style="margin-bottom: 15px;">
                      <strong style="color: {{ $eventVars->primary_color }};">Ticket Code:</strong> 
                      <span style="
                        background: {{ $eventVars->secondary_color }}20;
                        padding: 2px 8px;
                        border-radius: 5px;
                        font-family: monospace;
                      ">{{ $ticket->ticket_code ?? 'N/A' }}</span>
                    </div>
                  </div>
                  
                  <!-- Terms and Conditions -->
                  <div style="
                    margin-top: 20px;
                    padding-top: 15px;
                    border-top: 1px solid {{ $eventVars->primary_color }}20;
                  ">
                    <div style="
                      color: {{ $eventVars->primary_color }};
                      font-weight: bold;
                      font-size: 0.7rem;
                      margin-bottom: 8px;
                    ">
                      Syarat dan Ketentuan:
                    </div>
                    <div style="font-size: 0.6rem; color: {{ $eventVars->text_primary_color }}; line-height: 1.4;">
                      <div style="margin-bottom: 4px;">• E-tiket ini adalah bukti sah kepemilikan tiket untuk menghadiri acara {{ $event->name }}</div>
                      <div style="margin-bottom: 4px;">• E-tiket ini hanya berlaku untuk satu orang dan satu kali masuk</div>
                      <div style="margin-bottom: 4px;">• E-tiket yang sudah dibeli tidak dapat dibatalkan atau ditukar</div>
                      <div>• Jaga keamanan data tiket anda. Pencurian data diluar tanggung jawab kami</div>
                    </div>
                  </div>
                </div>
              </td>

              <!-- QR Code Section -->
              <td style="vertical-align: top; text-align: right; padding-left: 20px;">
                <div style="
                  background: white;
                  padding: 20px;
                  border-radius: 15px;
                  box-shadow: 0 5px 15px rgba(0,0,0,0.08);
                  text-align: center;
                ">
                  <div style="
                    color: {{ $eventVars->primary_color }};
                    font-size: 0.7rem;
                    font-weight: bold;
                    margin-bottom: 10px;
                  ">
                    SCAN FOR ENTRY
                  </div>
                  <div style="
                    padding: 10px;
                    background: linear-gradient(135deg, {{ $eventVars->primary_color }}10, {{ $eventVars->secondary_color }}10);
                    border-radius: 10px;
                    border: 3px solid {{ $ticket->getColor() }};
                  ">
                    <img src="data:image/svg+xml;base64,{{ $ticket->getQRCode() }}" alt="QR Code"
                      style="width: 130px; height: 130px; display: block;">
                  </div>
                  <div style="
                    color: {{ $eventVars->text_primary_color }};
                    font-size: 0.6rem;
                    margin-top: 8px;
                    opacity: 0.7;
                  ">
                    Tunjukkan kode ini di pintu masuk
                  </div>
                </div>
              </td>
            </tr>
          </table>
        </div>
      </div>
    </div>
  </body>
@endforeach

</html>