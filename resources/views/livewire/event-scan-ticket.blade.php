<div>
  {{-- The best athlete wants his opponent at his best. --}}

  {{-- Kamera Stream --}}
  <div class="w-full flex justify-center items-center">
    <div class="w-1/2 rounded-md">
      <video id="videoElement" width="100%" height="auto" autoplay class="rounded-md"></video>
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
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
      navigator.mediaDevices.getUserMedia({
          video: true
        })
        .then(function(stream) {
          var videoElement = document.getElementById('videoElement');
          videoElement.srcObject = stream;
        })
        .catch(function(error) {
          // console.log('Terjadi kesalahan dalam mengakses kamera: ', error);
        });
    } else {
      // console.log('Browser tidak mendukung akses ke kamera.');
    }
  </script>
</div>
