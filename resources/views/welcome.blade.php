<x-layouts::app :title="__('Welcome')">
    <div class="flex min-h-screen items-center justify-center p-6">
        <flux:card class="w-full max-w-md space-y-6">
            <div>
                <flux:heading size="xl" level="1">{{ config('app.name') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Laravel, Livewire and Flux are ready. Start building.') }}</flux:text>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button variant="primary" href="https://laravel.com/docs" target="_blank" icon:trailing="arrow-up-right">Laravel</flux:button>
                <flux:button href="https://livewire.laravel.com/docs" target="_blank" icon:trailing="arrow-up-right">Livewire</flux:button>
                <flux:button href="https://fluxui.dev/docs" target="_blank" icon:trailing="arrow-up-right">Flux</flux:button>
            </div>

            <flux:separator />

            <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" size="sm">
                <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
            </flux:radio.group>
        </flux:card>
    </div>
</x-layouts::app>
