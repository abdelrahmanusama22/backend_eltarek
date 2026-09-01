<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-4">
    <div>
        <h1 class="font-hanken text-2xl font-semibold text-white mb-1">Dashboard</h1>
        <p class="text-[#94949E] font-inter text-sm">Here's what's happening across ELTAREK today.</p>
    </div>
    <div class="flex items-center gap-3">
        <div class="flex items-center gap-2 px-3 py-1.5 border border-[#2A2A30] rounded text-[#94949E] font-inter text-sm bg-[#1A1A20]">
            <span class="material-symbols-outlined text-[18px]">calendar_today</span>
            Today &mdash; {{ date('M d, Y') }}
        </div>
        <button class="p-2 border border-[#2A2A30] rounded text-[#94949E] bg-[#1A1A20] hover:bg-[#2A2A30] transition-colors" title="Refresh" onclick="window.location.reload()">
            <span class="material-symbols-outlined text-[18px]">refresh</span>
        </button>
        <a href="/admin/bookings/create" class="px-4 py-2 bg-[#1f1f23] border border-[#2A2A30] text-white rounded hover:bg-[#2A2A30] transition-colors font-inter text-sm font-medium flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">add</span>
            Create Booking
        </a>
        <a href="/admin/vehicles/create" class="px-4 py-2 bg-[#e01b22] text-white rounded hover:bg-[#b91c1c] transition-colors font-inter text-sm font-medium flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">directions_car</span>
            Add Vehicle
        </a>
    </div>
</div>
