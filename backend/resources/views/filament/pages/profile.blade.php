<x-filament-panels::page>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Left: Avatar preview --}}
        <div class="lg:col-span-1">
            <x-filament::section>
                <div class="flex flex-col items-center gap-4 py-4">
                    @php $admin = auth()->user(); @endphp
                    <div class="max-w-28 max-h-28" style="max-height: 150px; max-width: 150px">
                        <img
                            src="{{ $admin->avatar ? Storage::url($admin->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($admin->name) . '&background=006A4E&color=fff&size=128' }}"
                            alt="{{ $admin->name }}"
                            style="width: 100%; height: 100%"
                            class=" w-full h-full rounded-full object-cover ring-4 ring-primary-500"
                        />
                    </div>
                    <div class="text-center">
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $admin->name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $admin->email }}</p>
                        @if($admin->phone)
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $admin->phone }}</p>
                        @endif
                    </div>
                    <div class="flex flex-col gap-1 text-center text-xs text-gray-400">
                        @if($admin->last_login_at)
                            <span>Last login: {{ $admin->last_login_at->diffForHumans() }}</span>
                        @endif
                        @if($admin->last_login_ip)
                            <span>IP: {{ $admin->last_login_ip }}</span>
                        @endif
                        <span class="inline-flex items-center justify-center gap-1 mt-1">
                            <span class="w-2 h-2 rounded-full bg-success-500"></span>
                            Active
                        </span>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Right: Forms --}}
        <div class="lg:col-span-2 flex flex-col gap-6">

            {{-- Profile form --}}
            <x-filament::section>
                <form wire:submit="saveProfile">
                    {{ $this->profileForm }}
                    <div class="mt-4 flex justify-end">
                        <x-filament::button type="submit" color="success" icon="heroicon-o-check">
                            Save Profile
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>

            {{-- Password form --}}
            <x-filament::section>
                <form wire:submit="changePassword">
                    {{ $this->passwordForm }}
                    <div class="mt-4 flex justify-end">
                        <x-filament::button type="submit" color="warning" icon="heroicon-o-lock-closed">
                            Change Password
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>

        </div>
    </div>

</x-filament-panels::page>
