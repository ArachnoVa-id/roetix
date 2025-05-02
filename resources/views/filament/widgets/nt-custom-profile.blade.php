<x-filament::widget>
  <x-filament::card>
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <!-- Check if the user has a profile photo, otherwise show initials -->
        @if ($user->contactInfo != null && $user->contactInfo->avatar != null)
          <img class="aspect-square rounded-full object-cover" src="{{ $user->contactInfo->avatar }}"
            style="width: 3.25rem" alt="{{ $user->first_name }}'s Profile Picture">
        @else
          <div class="p-3 rounded-full flex items-center justify-center text-white mr-4" style="background: #000;">
            <span
              class="text-xl font-semibold">{{ strtoupper(substr($user->first_name, 0, 1)) }}{{ strtoupper(substr($user->getFullnameLastWord(), 0, 1)) }}</span>
          </div>
        @endif

        <div>
          <h2 class="text-xl font-semibold">
            Welcome Back, {{ $user->getFilamentName() }}!
          </h2>
          <p class="text-sm text-gray-500">
            {{ $user->getRoleLabel() }} | {{ $user->email }}
          </p>
        </div>
      </div>

      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <x-filament::button color="danger" type="submit">
          Logout
        </x-filament::button>
      </form>
    </div>
  </x-filament::card>
</x-filament::widget>
