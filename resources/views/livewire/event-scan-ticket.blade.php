<div>
  {{-- The best athlete wants his opponent at his best. --}}

  {{-- Kamera Stream --}}
  <div class="w-full flex justify-center items-center">
    <div class="w-1/2 rounded-md">
      <video id="videoElement" width="100%" height="auto" autoplay class="rounded-md"></video>
      <div class="flex justify-center mt-2 gap-x-5">
        <x-filament::button color="primary" wire:click="javascript:startCamera()">Activate Camera</x-filament::button>
        <x-filament::button color="success" wire:click="javascript:toggleCamera()">Flip Camera</x-filament::button>
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
  <script>
    let useFrontCamera = true;
    let videoElement = document.getElementById('videoElement');
    let currentStream;

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
      } catch (error) {
        console.log('Terjadi kesalahan dalam mengakses kamera: ', error.message);
      }
    }

    function toggleCamera() {
      useFrontCamera = !useFrontCamera;
      startCamera();
    }
  </script>
</div>
