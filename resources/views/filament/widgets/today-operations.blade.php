<x-filament-widgets::widget>
    <x-filament::section class="fi-section">
        <x-slot name="heading">
            <h2 class="font-hanken text-lg font-semibold text-white mb-4">Today's Operations</h2>
        </x-slot>
        <div class="flex flex-col gap-4">
    <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded bg-[#e01b22]/10 flex items-center justify-center text-[#e01b22]">
            <span class="material-symbols-outlined text-[20px]">pending_actions</span>
        </div>
        <div class="flex-1">
            <div class="text-white font-medium">Pending Bookings</div>
            <div class="text-[#94949E] text-sm">Requires immediate review</div>
        </div>
        <div class="font-hanken font-bold text-lg text-white">18</div>
    </div>
    <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded bg-[#47464c]/30 flex items-center justify-center text-white">
            <span class="material-symbols-outlined text-[20px]">local_taxi</span>
        </div>
        <div class="flex-1">
            <div class="text-white font-medium">Upcoming Test Drives</div>
            <div class="text-[#94949E] text-sm">Scheduled for today</div>
        </div>
        <div class="font-hanken font-bold text-lg text-white">12</div>
    </div>
    <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded bg-[#F59E0B]/10 flex items-center justify-center text-[#F59E0B]">
            <span class="material-symbols-outlined text-[20px]">build</span>
        </div>
        <div class="flex-1">
            <div class="text-white font-medium">Maintenance Due</div>
            <div class="text-[#94949E] text-sm">Vehicles requiring service</div>
        </div>
        <div class="font-hanken font-bold text-lg text-white">7</div>
    </div>
    <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded bg-[#F59E0B]/10 flex items-center justify-center text-[#F59E0B]">
            <span class="material-symbols-outlined text-[20px]">verified</span>
        </div>
        <div class="flex-1">
            <div class="text-white font-medium">Expiring Warranty</div>
            <div class="text-[#94949E] text-sm">Needs extension check</div>
        </div>
        <div class="font-hanken font-bold text-lg text-white">4</div>
    </div>
</div>
    </x-filament::section>
</x-filament-widgets::widget>
