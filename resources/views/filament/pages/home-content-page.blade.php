<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" size="lg" icon="heroicon-o-check-circle">
                Save Home Content
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
