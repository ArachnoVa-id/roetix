<x-filament::page>

    {{-- Kamera Stream --}}
    <div class="w-full flex justify-center items-center">
        <div class="w-1/2 rounded-md">
            <video id="videoElement" width="100%" height="auto" autoplay class="rounded-md"></video>
        </div>
    </div>

    {{-- form nya --}}
    <form wire:submit.prevent="submit">
        {{ $this->form }}
    </form>

    {{-- table --}}
    <div class="mt-6">
        <h2 class="text-xl font-semibold mb-2">Ticket List</h2>
        {{ $this->table }}
    </div>

    {{-- JavaScript untuk mengakses kamera --}}
    <script>
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(function(stream) {
                    var videoElement = document.getElementById('videoElement');
                    videoElement.srcObject = stream;
                })
                .catch(function(error) {
                    console.log('Terjadi kesalahan dalam mengakses kamera: ', error);
                });
        } else {
            console.log('Browser tidak mendukung akses ke kamera.');
        }
    </script>

</x-filament::page>
