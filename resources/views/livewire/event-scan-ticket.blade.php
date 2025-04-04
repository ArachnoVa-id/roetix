<div class="flex flex-col gap-4">
  {{-- Kamera Stream --}}
  <div class="w-full flex justify-center items-center">
    <div class="w-1/2 rounded-md">
      <video id="videoElement" width="100%" height="auto" autoplay class="rounded-md"></video>
      <canvas id="canvas" style="display: none;"></canvas>
      <div class="flex justify-center mt-2 gap-x-5">
        <!-- Button to activate camera -->
        <x-filament::button color="primary" onclick="startCamera()">Activate Camera</x-filament::button>
        <!-- Button to toggle camera -->
        <x-filament::button color="success" onclick="toggleCamera()">Flip Camera</x-filament::button>
      </div>
    </div>
  </div>

  <div class="m-[1vw]">
    {{ $this->form }}
  </div>

  <div class="m-[1vw]">
    {{ $this->table }}
  </div>

  {{-- JavaScript untuk mengakses kamera --}}
  <script src="https://unpkg.com/jsqr/dist/jsQR.js"></script>
  <script>
    let useFrontCamera = true;
    let videoElement = document.getElementById('videoElement');
    let canvasElement = document.getElementById('canvas');
    let canvasContext = canvasElement.getContext('2d');
    let currentStream;
    let scanInterval;

    async function startCamera() {
      if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
      }

      const constraints = {
        video: {
          facingMode: useFrontCamera ? "user" : "environment"
        }
      };

      try {
        currentStream = await navigator.mediaDevices.getUserMedia(constraints);
        videoElement.srcObject = currentStream;
        console.log('Kamera berhasil diakses.');

        // mulai pindai qr code
        scanQRCode();
      } catch (error) {
        console.log('Terjadi kesalahan dalam mengakses kamera: ', error.message);
      }
    }

    function toggleCamera() {
      useFrontCamera = !useFrontCamera;
      startCamera();
    }

    function scanQRCode() {
      scanInterval = setInterval(() => {
        if (videoElement.readyState === videoElement.HAVE_ENOUGH_DATA) {
          canvasElement.height = videoElement.videoHeight;
          canvasElement.width = videoElement.videoWidth;

          // Gambar video ke dalam canvas
          canvasContext.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);

          // Ambil data image dari canvas
          const imageData = canvasContext.getImageData(0, 0, canvasElement.width, canvasElement.height);
          const code = jsQR(imageData.data, canvasElement.width, canvasElement.height, {
            inversionAttempts: "dontInvert",
          });

          if (code) {
            // Jika QR code ditemukan, tampilkan hasilnya
            console.log("QR Code ditemukan:", code.data);
            alert('QR Code: ' + code.data); // Atau lakukan sesuatu dengan data QR
            clearInterval(scanInterval); // Berhenti setelah QR Code ditemukan

            @this.set('ticket_code', code.data)
            @this.call('submit')
          }
        }
      }, 300); // Set interval untuk memindai setiap 300ms
    }
  </script>
</div>
