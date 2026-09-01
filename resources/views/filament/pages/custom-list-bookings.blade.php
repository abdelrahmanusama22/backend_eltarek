<x-filament-panels::page>
<!-- User's Custom Tailwind Config & Fonts -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script>
    tailwind.config = {
        corePlugins: {
            preflight: false,
        },
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "surface-dim": "#131317",
                    "surface-tint": "#ffb4ab",
                    "secondary-fixed-dim": "#c8c5cc",
                    "border-subtle": "#2A2A30",
                    "on-tertiary": "#303036",
                    "text-secondary": "#94949E",
                    "tertiary-fixed-dim": "#c8c5cd",
                    "primary-fixed-dim": "#ffb4ab",
                    "tertiary": "#c8c5cd",
                    "tertiary-container": "#727178",
                    "on-background": "#e4e1e7",
                    "text-primary": "#FFFFFF",
                    "on-tertiary-fixed-variant": "#46464d",
                    "primary": "#ffb4ab",
                    "surface-bright": "#39393d",
                    "secondary": "#c8c5cc",
                    "primary-fixed": "#ffdad6",
                    "on-secondary-fixed-variant": "#47464c",
                    "error-container": "#93000a",
                    "on-primary-container": "#fff6f5",
                    "inverse-primary": "#c00014",
                    "surface-variant": "#353439",
                    "on-error-container": "#ffdad6",
                    "on-secondary-fixed": "#1b1b20",
                    "on-secondary-container": "#b6b4bb",
                    "on-surface-variant": "#e7bdb8",
                    "surface-container-high": "#2a292e",
                    "secondary-fixed": "#e4e1e8",
                    "warning-amber": "#F59E0B",
                    "surface-container-low": "#1b1b1f",
                    "on-tertiary-fixed": "#1b1b21",
                    "secondary-container": "#47464c",
                    "on-primary": "#690006",
                    "on-surface": "#e4e1e7",
                    "on-secondary": "#303035",
                    "success-green": "#10B981",
                    "surface-container-highest": "#353439",
                    "outline": "#ae8883",
                    "primary-container": "#e01b22",
                    "on-primary-fixed": "#410002",
                    "inverse-on-surface": "#303034",
                    "on-tertiary-container": "#faf7ff",
                    "tertiary-fixed": "#e4e1e9",
                    "error": "#ffb4ab",
                    "surface-container-lowest": "#0e0e12",
                    "on-error": "#690005",
                    "surface-container": "#1f1f23",
                    "on-primary-fixed-variant": "#93000d",
                    "background": "#131317",
                    "surface": "#131317",
                    "inverse-surface": "#e4e1e7",
                    "outline-variant": "#5d3f3c"
                },
                fontFamily: {
                    "display-lg": ["Hanken Grotesk"],
                    "headline-lg-mobile": ["Hanken Grotesk"],
                    "metric-num": ["Hanken Grotesk"],
                    "body-lg": ["Inter"],
                    "body-md": ["Inter"],
                    "label-mono": ["JetBrains Mono"],
                    "headline-sm": ["Hanken Grotesk"],
                    "headline-md": ["Hanken Grotesk"]
                },
                fontSize: {
                    "display-lg": ["48px", { "lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700" }],
                    "headline-lg-mobile": ["28px", { "lineHeight": "36px", "fontWeight": "700" }],
                    "metric-num": ["32px", { "lineHeight": "40px", "letterSpacing": "-0.01em", "fontWeight": "700" }],
                    "body-lg": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                    "body-md": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                    "label-mono": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "500" }],
                    "headline-sm": ["18px", { "lineHeight": "24px", "fontWeight": "600" }],
                    "headline-md": ["24px", { "lineHeight": "32px", "fontWeight": "600" }]
                }
            }
        }
    }
</script>

<div class="font-body-md text-on-surface bg-background -mx-6 -mt-6 p-6 min-h-[calc(100vh-64px)]">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="font-headline-lg-mobile md:font-headline-md text-headline-lg-mobile md:text-headline-md text-on-surface mb-1">Bookings Management</h1>
            <p class="text-text-secondary text-sm">Monitor and manage customer vehicle reservations.</p>
        </div>
        <a href="/admin/bookings/create" class="bg-primary-container text-on-primary-container px-4 py-2 rounded-md font-medium text-sm flex items-center gap-2 hover:bg-primary-container/90 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/50 focus:ring-offset-2 focus:ring-offset-background">
            <span class="material-symbols-outlined text-sm">add</span>
            New Booking
        </a>
    </div>

    <!-- Metric Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-surface-container border border-border-subtle rounded-lg p-5 flex flex-col justify-between h-24">
            <span class="font-label-mono text-text-secondary text-xs uppercase">Total Bookings</span>
            <div class="flex items-end justify-between">
                <span class="font-metric-num text-metric-num text-on-surface">{{ \App\Models\Booking::count() }}</span>
                <span class="text-success-green text-xs font-medium flex items-center"><span class="material-symbols-outlined text-xs">arrow_upward</span> 2 added this month</span>
            </div>
        </div>
        <div class="bg-surface-container border border-border-subtle rounded-lg p-5 flex flex-col justify-between h-24">
            <span class="font-label-mono text-text-secondary text-xs uppercase">Pending Bookings</span>
            <div class="flex items-end justify-between">
                <span class="font-metric-num text-metric-num text-on-surface">{{ \App\Models\Booking::where('status', 'pending')->count() }}</span>
                <span class="text-warning-amber/80 text-xs font-medium flex items-center"><span class="material-symbols-outlined text-xs">remove</span> No change</span>
            </div>
        </div>
        <div class="bg-surface-container border border-border-subtle rounded-lg p-5 flex flex-col justify-between h-24">
            <span class="font-label-mono text-text-secondary text-xs uppercase">Action Required</span>
            <div class="flex items-end justify-between">
                <span class="font-metric-num text-metric-num text-error">{{ \App\Models\Booking::where('status', 'pending')->count() }}</span>
                <span class="text-text-secondary text-xs font-medium">Requires action</span>
            </div>
        </div>
    </div>

    <!-- Data Table Container -->
    <div class="bg-surface-container-low border border-border-subtle rounded-xl overflow-hidden">
        <!-- Table Controls (Tabs & Buttons) -->
        <div class="p-4 border-b border-border-subtle flex flex-col sm:flex-row justify-between items-center gap-4 bg-surface-container-low/50">
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="relative w-full sm:w-64">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary text-sm">search</span>
                    <input class="w-full bg-surface-container-high border border-border-subtle rounded text-sm pl-9 pr-3 py-1.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary/20 outline-none transition-all placeholder:text-text-secondary" placeholder="Search bookings..." type="text"/>
                </div>
            </div>
            
            <div class="flex items-center gap-1 w-full sm:w-auto justify-end bg-surface-container p-1 rounded-lg">
                <button wire:click="setTab('all')" class="px-3 py-1 rounded text-sm font-medium transition-colors {{ $activeTab === 'all' ? 'bg-[#25252B] text-white shadow-sm' : 'text-text-secondary hover:text-on-surface' }}">All</button>
                <button wire:click="setTab('pending')" class="px-3 py-1 rounded text-sm font-medium transition-colors {{ $activeTab === 'pending' ? 'bg-[#25252B] text-white shadow-sm' : 'text-text-secondary hover:text-on-surface' }}">Pending</button>
                <button wire:click="setTab('confirmed')" class="px-3 py-1 rounded text-sm font-medium transition-colors {{ $activeTab === 'confirmed' ? 'bg-[#25252B] text-white shadow-sm' : 'text-text-secondary hover:text-on-surface' }}">Confirmed</button>
                <button wire:click="setTab('completed')" class="px-3 py-1 rounded text-sm font-medium transition-colors {{ $activeTab === 'completed' ? 'bg-[#25252B] text-white shadow-sm' : 'text-text-secondary hover:text-on-surface' }}">Completed</button>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto justify-end hidden md:flex">
                <button class="bg-transparent border border-border-subtle text-text-secondary hover:text-on-surface hover:bg-surface-container-high px-3 py-1.5 rounded text-sm font-medium transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">filter_list</span> Filter
                </button>
                <button class="bg-transparent border border-border-subtle text-text-secondary hover:text-on-surface hover:bg-surface-container-high px-3 py-1.5 rounded text-sm font-medium transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">download</span> Export
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-surface-container border-b border-border-subtle">
                        <th class="py-3 px-4 font-label-mono text-[11px] text-text-secondary uppercase tracking-wider font-medium whitespace-nowrap">ID</th>
                        <th class="py-3 px-4 font-label-mono text-[11px] text-text-secondary uppercase tracking-wider font-medium whitespace-nowrap">CUSTOMER</th>
                        <th class="py-3 px-4 font-label-mono text-[11px] text-text-secondary uppercase tracking-wider font-medium whitespace-nowrap">VEHICLE</th>
                        <th class="py-3 px-4 font-label-mono text-[11px] text-text-secondary uppercase tracking-wider font-medium whitespace-nowrap">BRANCH</th>
                        <th class="py-3 px-4 font-label-mono text-[11px] text-text-secondary uppercase tracking-wider font-medium whitespace-nowrap">DATE &amp; TIME</th>
                        <th class="py-3 px-4 font-label-mono text-[11px] text-text-secondary uppercase tracking-wider font-medium whitespace-nowrap">STATUS</th>
                        <th class="py-3 px-4 font-label-mono text-[11px] text-text-secondary uppercase tracking-wider font-medium text-right whitespace-nowrap">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle bg-surface-container-low">
                    @forelse($this->bookings as $booking)
                    <tr class="hover:bg-surface-container-high/50 transition-colors group">
                        <td class="py-3 px-4 font-label-mono text-text-secondary text-[11px]">#BK-{{ 9000 + $booking->id }}</td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                @php
                                    $name = $booking->user->name ?? 'Unknown';
                                    $initials = collect(explode(' ', $name))->map(fn($n) => substr($n, 0, 1))->take(2)->implode('');
                                    $phone = $booking->user->phone ?? 'No phone';
                                @endphp
                                <div class="w-8 h-8 rounded-full bg-surface-container-highest border border-border-subtle flex items-center justify-center text-[10px] font-bold text-primary">{{ strtoupper($initials) }}</div>
                                <div class="flex flex-col">
                                    <span class="text-on-surface font-medium text-sm">{{ $name }}</span>
                                    <span class="text-text-secondary text-[11px] mt-0.5">{{ $phone }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-sm">directions_car</span>
                                <span class="text-on-surface text-sm font-medium">{{ $booking->trim->name ?? 'Unknown Vehicle' }}</span>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-text-secondary text-sm">{{ $booking->branch->name ?? 'Unknown Branch' }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-text-secondary text-sm">
                                {{ \Carbon\Carbon::parse($booking->date)->format('M d, Y') }} 
                                @if($booking->time)
                                    - {{ \Carbon\Carbon::parse($booking->time)->format('h:i A') }}
                                @endif
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            @if($booking->status === 'pending')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-warning-amber/10 text-warning-amber border border-warning-amber/20 uppercase tracking-wider">Pending</span>
                            @elseif($booking->status === 'confirmed')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-success-green/10 text-success-green border border-success-green/20 uppercase tracking-wider">Confirmed</span>
                            @elseif($booking->status === 'completed')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-primary/10 text-primary border border-primary/20 uppercase tracking-wider">Completed</span>
                            @elseif($booking->status === 'cancelled')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-error/10 text-error border border-error/20 uppercase tracking-wider">Cancelled</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-surface-container-highest text-text-secondary border border-border-subtle uppercase tracking-wider">{{ $booking->status }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                @if($booking->status === 'pending')
                                    <button wire:click="$set('activeTab', 'pending')" class="p-1 text-success-green hover:bg-success-green/10 rounded transition-colors" title="Confirm">
                                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                    </button>
                                @endif
                                @if(in_array($booking->status, ['pending', 'confirmed']))
                                    <button class="p-1 text-error hover:bg-error/10 rounded transition-colors" title="Cancel">
                                        <span class="material-symbols-outlined text-[18px]">cancel</span>
                                    </button>
                                @endif
                                <a href="/admin/bookings/{{ $booking->id }}/edit" class="p-1 text-text-secondary hover:text-on-surface rounded hover:bg-surface-container transition-colors" title="Edit">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 px-4 text-center text-text-secondary">
                            <span class="material-symbols-outlined text-4xl mb-2 text-border-subtle">book_online</span>
                            <p>No bookings found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination (Static Mockup for design match) -->
        <div class="p-4 border-t border-border-subtle flex items-center justify-between text-sm">
            <span class="text-text-secondary">Showing <span class="text-on-surface font-medium">1</span> to <span class="text-on-surface font-medium">{{ count($this->bookings) }}</span> of <span class="text-on-surface font-medium">{{ count($this->bookings) }}</span> bookings</span>
            <div class="flex items-center gap-1">
                <button class="px-2 py-1 text-text-secondary hover:text-on-surface disabled:opacity-50 disabled:cursor-not-allowed" disabled="">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </button>
                <button class="w-7 h-7 rounded flex items-center justify-center bg-surface-container-high text-on-surface border border-border-subtle font-medium">1</button>
                <button class="px-2 py-1 text-text-secondary hover:text-on-surface disabled:opacity-50 disabled:cursor-not-allowed" disabled="">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>

