<x-filament-panels::page>
    <form wire:submit.prevent="import">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" size="lg" icon="heroicon-o-arrow-up-tray">
                Start Import
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
