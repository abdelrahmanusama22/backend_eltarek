<x-filament-panels::page>
{{-- ELTAREK ADMIN — PREMIUM DASHBOARD v2 --}}

<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script id="tailwind-config">
tailwind.config = {
    corePlugins: { preflight: false },
    darkMode: "class",
    theme: { extend: {} }
};
</script>

@php

$todayBookings   = \App\Models\Booking::whereDate('created_at', today())->count();
$totalCustomers  = \App\Models\User::where('is_admin', false)->count();
$activeVehicles  = \App\Models\Vehicle::where('active', true)->count();
$pendingBookings = \App\Models\Booking::where('status', 'pending')->count();
$totalBrands     = \App\Models\Brand::count();
$confirmedCount  = \App\Models\Booking::where('status','confirmed')->count();
$vipCount        = \App\Models\User::whereIn('vip_tier',['platinum','gold'])->count();

$thisM = \App\Models\Booking::whereMonth('created_at', now()->month)->count();
$lastM = \App\Models\Booking::whereMonth('created_at', now()->subMonth()->month)->count();
$growth = $lastM > 0 ? round((($thisM - $lastM) / $lastM) * 100, 1) : 0;

$chartData = collect(range(13, 0))->map(function($d) {
    $date = now()->subDays($d);
    return [
        'label'     => $date->format('M d'),
        'confirmed' => \App\Models\Booking::whereDate('created_at', $date)->where('status','confirmed')->count(),
        'pending'   => \App\Models\Booking::whereDate('created_at', $date)->where('status','pending')->count(),
    ];
});

$recentBookings = \App\Models\Booking::with(['user','trim.vehicle','branch'])->latest()->limit(8)->get();

$fleetTrims = \App\Models\Trim::where('in_test_drive_fleet', true)
    ->where('active', true)
    ->with(['vehicle.brand'])
    ->orderBy('fleet_sort')
    ->limit(6)
    ->get();
@endphp

<style>
#eld * { box-sizing: border-box; }
#eld {
    font-family: 'Inter', sans-serif;
    background: #0C0C10;
    color: #F0EFF6;
    margin: -8px -16px 0;
    min-height: 100vh;
    padding-bottom: 60px;
}

/* ── HEADER ── */
.dh {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 32px;
    background: #111118;
    border-bottom: 1px solid #1E1E2A;
    position: sticky; top: 0; z-index: 50;
    backdrop-filter: blur(16px);
}
.dh-title { font-family: 'Hanken Grotesk', sans-serif; font-size: 19px; font-weight: 700; color: #F0EFF6; margin: 0 0 2px; }
.dh-sub { font-size: 12.5px; color: #9B9BAD; margin: 0; }
.dh-right { display: flex; align-items: center; gap: 9px; }
.chip {
    display: flex; align-items: center; gap: 6px;
    padding: 7px 13px;
    background: #16161E; border: 1px solid #1E1E2A; border-radius: 8px;
    font-size: 12px; color: #9B9BAD;
    font-family: 'JetBrains Mono', monospace;
}
.chip .ms { font-size: 14px; }
.btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 15px; border-radius: 8px;
    font-size: 13px; font-weight: 500; cursor: pointer;
    border: none; text-decoration: none;
    font-family: 'Inter', sans-serif;
    transition: all 0.15s ease;
}
.btn-ghost { background: #16161E; border: 1px solid #1E1E2A; color: #9B9BAD; }
.btn-ghost:hover { background: #1C1C26; color: #F0EFF6; border-color: #252535; }
.btn-red { background: #E01B22; color: #fff; }
.btn-red:hover { background: #C41720; box-shadow: 0 0 22px rgba(224,27,34,0.35); transform: translateY(-1px); }
.btn .ms { font-size: 15px; }

/* ── BODY ── */
.db { padding: 26px 32px 0; }

/* ── KPI GRID ── */
.kpi-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 20px; }
.kpi {
    background: #16161E; border: 1px solid #1E1E2A; border-radius: 14px;
    padding: 20px 22px; position: relative; overflow: hidden;
    transition: all 0.2s ease; cursor: default;
}
.kpi::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05) 50%, transparent);
}
.kpi:hover { border-color: #252535; background: #1B1B24; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
.kpi.k-red:hover { border-color: rgba(224,27,34,0.3); box-shadow: 0 10px 30px rgba(224,27,34,0.1); }

.kpi-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; }
.kpi-icon {
    width: 36px; height: 36px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
}
.kpi-icon .ms { font-size: 18px; }
.i-red   { background: rgba(224,27,34,0.12); color: #FF5F65; }
.i-green { background: rgba(16,185,129,0.12); color: #10B981; }
.i-blue  { background: rgba(59,130,246,0.12);  color: #60A5FA; }
.i-amber { background: rgba(245,158,11,0.12);  color: #FCD34D; }

.kpi-badge {
    display: flex; align-items: center; gap: 3px;
    font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px;
    font-family: 'JetBrains Mono', monospace;
}
.kpi-badge .ms { font-size: 12px; }
.b-up   { background: rgba(16,185,129,0.12); color: #10B981; }
.b-down { background: rgba(224,27,34,0.12);  color: #FF5F65; }
.b-flat { background: rgba(155,155,173,0.1); color: #9B9BAD; }
.b-warn { background: rgba(245,158,11,0.12); color: #FCD34D; }

.kpi-num {
    font-family: 'Hanken Grotesk', sans-serif;
    font-size: 34px; font-weight: 700; color: #F0EFF6;
    line-height: 1; letter-spacing: -1.5px; margin-bottom: 4px;
}
.kpi-lbl { font-size: 12.5px; color: #9B9BAD; }
.kpi-spark { margin-top: 14px; height: 36px; }

/* ── MID ROW ── */
.mid-row { display: grid; grid-template-columns: 1fr 380px; gap: 14px; margin-bottom: 20px; }

/* ── PANEL ── */
.panel { background: #16161E; border: 1px solid #1E1E2A; border-radius: 14px; overflow: hidden; }
.panel-hdr {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 22px 14px; border-bottom: 1px solid #1E1E2A;
}
.panel-title {
    font-family: 'Hanken Grotesk', sans-serif; font-size: 13.5px; font-weight: 600; color: #F0EFF6;
    display: flex; align-items: center; gap: 8px;
}
.panel-title .ms { font-size: 15px; color: #9B9BAD; }
.panel-link { font-size: 12px; color: #E01B22; text-decoration: none; font-weight: 500; transition: opacity 0.15s; }
.panel-link:hover { opacity: 0.75; }

/* Chart */
.chart-wrap { padding: 18px 22px; height: 238px; position: relative; }

/* Fleet list */
.fleet-list { padding: 6px 0; }
.fleet-item {
    display: flex; align-items: center; gap: 13px;
    padding: 10px 22px; transition: background 0.12s; cursor: pointer;
}
.fleet-item:hover { background: #1C1C26; }
.fleet-thumb {
    width: 50px; height: 36px; border-radius: 7px; overflow: hidden;
    background: #1A1A24; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.fleet-thumb img { width: 100%; height: 100%; object-fit: cover; opacity: 0.85; }
.fleet-thumb .ms { font-size: 18px; color: #55556A; }
.fleet-info { flex: 1; min-width: 0; }
.fleet-name {
    font-size: 13px; font-weight: 500; color: #F0EFF6;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px;
}
.fleet-meta { font-size: 11.5px; color: #9B9BAD; }
.fleet-badge {
    font-size: 10.5px; font-weight: 600; padding: 3px 9px; border-radius: 20px;
    font-family: 'JetBrains Mono', monospace; flex-shrink: 0;
}
.fb-fleet { background: rgba(16,185,129,0.12); color: #10B981; }

/* ── QUICK ROW ── */
.quick-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 20px; }
.qcard {
    background: #16161E; border: 1px solid #1E1E2A; border-radius: 14px;
    padding: 18px 22px; display: flex; align-items: center; gap: 16px;
    transition: all 0.2s;
}
.qcard:hover { background: #1C1C26; border-color: #252535; }
.qcard-icon { width: 44px; height: 44px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.qcard-icon .ms { font-size: 20px; }
.qcard-num { font-family: 'Hanken Grotesk', sans-serif; font-size: 24px; font-weight: 700; color: #F0EFF6; margin: 0 0 2px; line-height: 1; letter-spacing: -0.5px; }
.qcard-lbl { font-size: 12.5px; color: #9B9BAD; margin: 0; }

/* ── TABLE ── */
.data-table { width: 100%; border-collapse: collapse; }
.data-table thead tr th {
    padding: 11px 20px; font-size: 10.5px; font-weight: 600;
    color: #55556A; text-transform: uppercase; letter-spacing: 0.8px;
    border-bottom: 1px solid #1E1E2A; text-align: left; white-space: nowrap;
}
.data-table tbody tr { border-bottom: 1px solid #1A1A24; transition: background 0.1s; }
.data-table tbody tr:last-child { border-bottom: none; }
.data-table tbody tr:hover { background: #1B1B24; }
.data-table tbody td { padding: 12px 20px; font-size: 13px; color: #F0EFF6; vertical-align: middle; }

.td-ref { font-family: 'JetBrains Mono', monospace; font-size: 11.5px; color: #55556A; }
.td-cust { display: flex; align-items: center; gap: 10px; }
.av {
    width: 30px; height: 30px; border-radius: 50%;
    background: linear-gradient(135deg, #E01B22, #8B000A);
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0;
    font-family: 'Hanken Grotesk', sans-serif;
}
.td-sec { color: #9B9BAD; font-size: 12.5px; }
.td-dt { font-family: 'JetBrains Mono', monospace; font-size: 11.5px; color: #55556A; }

.sbadge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600; letter-spacing: 0.2px;
    font-family: 'JetBrains Mono', monospace; white-space: nowrap;
}
.sbadge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; display: inline-block; }
.s-confirmed { background: rgba(16,185,129,0.12); color: #10B981; border: 1px solid rgba(16,185,129,0.2); }
.s-confirmed::before { background: #10B981; }
.s-pending   { background: rgba(245,158,11,0.12);  color: #FCD34D; border: 1px solid rgba(245,158,11,0.2); }
.s-pending::before   { background: #FCD34D; }
.s-cancelled { background: rgba(155,155,173,0.08); color: #55556A; border: 1px solid rgba(155,155,173,0.12); }
.s-cancelled::before { background: #55556A; }
.s-completed { background: rgba(59,130,246,0.12);  color: #60A5FA; border: 1px solid rgba(59,130,246,0.2); }
.s-completed::before { background: #60A5FA; }

.row-btn {
    color: #55556A; background: none; border: none; cursor: pointer;
    padding: 4px 6px; border-radius: 6px;
    transition: all 0.12s; display: inline-flex; align-items: center; text-decoration: none;
}
.row-btn:hover { background: #252535; color: #F0EFF6; }
.row-btn .ms { font-size: 15px; }

.empty-p { padding: 40px 22px; text-align: center; }
.empty-p .ms { font-size: 36px; color: #252535; display: block; margin-bottom: 10px; }
.empty-p p { font-size: 13px; color: #55556A; margin: 0; }

@media (max-width: 1280px) {
    .kpi-grid { grid-template-columns: repeat(2,1fr); }
    .mid-row  { grid-template-columns: 1fr; }
    .quick-row{ grid-template-columns: repeat(2,1fr); }
}
@media (max-width: 768px) {
    .db { padding: 14px; }
    .kpi-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
    .quick-row{ grid-template-columns: 1fr; }
    .chip, .btn-ghost { display: none; }
    .dh { padding: 14px 16px; }
}
.ms { font-family: 'Material Symbols Outlined'; font-weight: 300; font-style: normal; font-size: 20px; line-height: 1; letter-spacing: normal; text-transform: none; white-space: nowrap; word-wrap: normal; direction: ltr; }
</style>

<div id="eld">

{{-- HEADER --}}
<div class="dh">
    <div>
        <p class="dh-title">Control Center</p>
        <p class="dh-sub">Here&apos;s what&apos;s happening across ELTAREK today</p>
    </div>
    <div class="dh-right">
        <div class="chip">
            <span class="ms">calendar_today</span>
            {{ now()->format('D, M d Y') }}
        </div>
        <button class="btn btn-ghost" onclick="window.location.reload()">
            <span class="ms">refresh</span> Refresh
        </button>
        <a href="/admin/bookings/create" class="btn btn-ghost">
            <span class="ms">add</span> New Booking
        </a>
        <a href="/admin/vehicles/create" class="btn btn-red">
            <span class="ms">directions_car</span> Add Vehicle
        </a>
    </div>
</div>

<div class="db">

{{-- KPI CARDS --}}
<div class="kpi-grid">

    <div class="kpi k-red">
        <div class="kpi-top">
            <div class="kpi-icon i-red"><span class="ms">calendar_month</span></div>
            <div class="kpi-badge {{ $growth > 0 ? 'b-up' : ($growth < 0 ? 'b-down' : 'b-flat') }}">
                <span class="ms">{{ $growth > 0 ? 'trending_up' : ($growth < 0 ? 'trending_down' : 'trending_flat') }}</span>
                {{ $growth > 0 ? '+' : '' }}{{ $growth }}%
            </div>
        </div>
        <div class="kpi-num">{{ $todayBookings }}</div>
        <div class="kpi-lbl">Today&apos;s Bookings</div>
        <canvas class="kpi-spark" id="sp1"></canvas>
    </div>

    <div class="kpi">
        <div class="kpi-top">
            <div class="kpi-icon i-green"><span class="ms">group</span></div>
            <div class="kpi-badge b-up"><span class="ms">trending_up</span>+12%</div>
        </div>
        <div class="kpi-num">{{ number_format($totalCustomers) }}</div>
        <div class="kpi-lbl">Total Customers</div>
        <canvas class="kpi-spark" id="sp2"></canvas>
    </div>

    <div class="kpi">
        <div class="kpi-top">
            <div class="kpi-icon i-blue"><span class="ms">directions_car</span></div>
            <div class="kpi-badge b-up"><span class="ms">trending_up</span>+5%</div>
        </div>
        <div class="kpi-num">{{ $activeVehicles }}</div>
        <div class="kpi-lbl">Active Vehicles</div>
        <canvas class="kpi-spark" id="sp3"></canvas>
    </div>

    <div class="kpi">
        <div class="kpi-top">
            <div class="kpi-icon i-amber"><span class="ms">pending_actions</span></div>
            @if($pendingBookings > 0)
                <div class="kpi-badge b-warn"><span class="ms">warning</span>Action Needed</div>
            @else
                <div class="kpi-badge b-up"><span class="ms">check_circle</span>All Clear</div>
            @endif
        </div>
        <div class="kpi-num">{{ $pendingBookings }}</div>
        <div class="kpi-lbl">Pending Approvals</div>
        <canvas class="kpi-spark" id="sp4"></canvas>
    </div>

</div>

{{-- MID ROW: Chart + Fleet --}}
<div class="mid-row">

    <div class="panel">
        <div class="panel-hdr">
            <div class="panel-title"><span class="ms">bar_chart</span>Bookings Overview — Last 14 Days</div>
            <a href="/admin/bookings" class="panel-link">View All &rarr;</a>
        </div>
        <div class="chart-wrap"><canvas id="bChart"></canvas></div>
    </div>

    <div class="panel">
        <div class="panel-hdr">
            <div class="panel-title"><span class="ms">garage</span>Test Drive Fleet</div>
            <a href="/admin/trims" class="panel-link">Manage &rarr;</a>
        </div>
        <div class="fleet-list">
            @forelse($fleetTrims as $trim)
            <div class="fleet-item">
                <div class="fleet-thumb">
                    @if($trim->vehicle && $trim->vehicle->image_url)
                        @php
                            $src = \Illuminate\Support\Facades\Storage::disk('public')->exists($trim->vehicle->image_url)
                                 ? \Illuminate\Support\Facades\Storage::url($trim->vehicle->image_url)
                                 : $trim->vehicle->image_url;
                        @endphp
                        <img src="{{ $src }}" alt="{{ $trim->name }}" onerror="this.parentElement.innerHTML='<span class=ms>directions_car</span>'">
                    @else
                        <span class="ms">directions_car</span>
                    @endif
                </div>
                <div class="fleet-info">
                    <div class="fleet-name">{{ $trim->vehicle->model ?? 'Vehicle' }} &mdash; {{ $trim->name }}</div>
                    <div class="fleet-meta">{{ optional($trim->vehicle)->brand->name ?? '' }} &bull; {{ optional($trim->vehicle)->year ?? '' }}</div>
                </div>
                <div class="fleet-badge fb-fleet">Fleet</div>
            </div>
            @empty
            <div class="empty-p">
                <span class="ms">directions_car</span>
                <p>No fleet vehicles yet.<br>Mark trims as &ldquo;in test drive fleet&rdquo;.</p>
            </div>
            @endforelse
        </div>
    </div>

</div>

{{-- QUICK STATS --}}
<div class="quick-row">
    <div class="qcard">
        <div class="qcard-icon" style="background:rgba(224,27,34,0.10)">
            <span class="ms" style="color:#FF5F65">storefront</span>
        </div>
        <div>
            <p class="qcard-num">{{ $totalBrands }}</p>
            <p class="qcard-lbl">Car Brands</p>
        </div>
    </div>
    <div class="qcard">
        <div class="qcard-icon" style="background:rgba(59,130,246,0.10)">
            <span class="ms" style="color:#60A5FA">event_available</span>
        </div>
        <div>
            <p class="qcard-num">{{ $confirmedCount }}</p>
            <p class="qcard-lbl">Confirmed Bookings</p>
        </div>
    </div>
    <div class="qcard">
        <div class="qcard-icon" style="background:rgba(16,185,129,0.10)">
            <span class="ms" style="color:#10B981">star</span>
        </div>
        <div>
            <p class="qcard-num">{{ $vipCount }}</p>
            <p class="qcard-lbl">VIP Members</p>
        </div>
    </div>
</div>

{{-- RECENT BOOKINGS --}}
<div class="panel">
    <div class="panel-hdr">
        <div class="panel-title"><span class="ms">receipt_long</span>Recent Bookings</div>
        <a href="/admin/bookings" class="panel-link">View All &rarr;</a>
    </div>
    @if($recentBookings->isEmpty())
        <div class="empty-p">
            <span class="ms">event_busy</span>
            <p>No bookings yet. They will appear here once customers start booking test drives.</p>
        </div>
    @else
    <table class="data-table">
        <thead><tr>
            <th>Ref #</th><th>Customer</th><th>Vehicle</th>
            <th>Branch</th><th>Date &amp; Time</th><th>Status</th>
            <th style="text-align:right">Action</th>
        </tr></thead>
        <tbody>
        @foreach($recentBookings as $b)
        @php
            $ref   = $b->reference ?? ('#BK-'.str_pad($b->id + 9000, 4, '0', STR_PAD_LEFT));
            $name  = $b->user->name ?? 'Unknown';
            $init  = strtoupper(substr($name, 0, 1));
            $model = optional($b->trim->vehicle)->model ?? '&mdash;';
            $trim  = $b->trim->name ?? '';
            $branch= $b->branch->name ?? '&mdash;';
            $dt    = $b->date ? \Carbon\Carbon::parse($b->date)->format('M d') : '';
            $time  = $b->time ?? '';
            $sCls  = match($b->status) {
                'confirmed' => 's-confirmed',
                'pending'   => 's-pending',
                'cancelled' => 's-cancelled',
                'completed' => 's-completed',
                default     => 's-cancelled',
            };
            $sLbl  = ucfirst($b->status ?? 'unknown');
        @endphp
        <tr>
            <td><span class="td-ref">{{ $ref }}</span></td>
            <td><div class="td-cust"><div class="av">{{ $init }}</div><span>{{ $name }}</span></div></td>
            <td class="td-sec">{{ $model }}@if($trim) <span style="color:#55556A"> / {{ $trim }}</span>@endif</td>
            <td class="td-sec">{{ $branch }}</td>
            <td class="td-dt">{{ $dt }}@if($dt && $time) &nbsp;&bull;&nbsp; @endif{{ $time }}</td>
            <td><span class="sbadge {{ $sCls }}">{{ $sLbl }}</span></td>
            <td style="text-align:right">
                <a href="/admin/bookings/{{ $b->id }}/edit" class="row-btn" title="Edit">
                    <span class="ms">open_in_new</span>
                </a>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>

</div>{{-- /db --}}
</div>{{-- /eld --}}

<script>
document.addEventListener('DOMContentLoaded', function () {
    const RED   = '#E01B22', RED_A30 = 'rgba(224,27,34,0.28)', RED_A0 = 'rgba(224,27,34,0)';
    const GREEN = '#10B981', AMBER = '#F59E0B', BLUE = '#3B82F6';
    const GRID  = '#1E1E2A', TICK  = '#55556A';

    Chart.defaults.font.family = "'Inter',sans-serif";
    Chart.defaults.color       = TICK;

    function spark(id, data, color) {
        const el = document.getElementById(id);
        if (!el) return;
        new Chart(el, {
            type: 'line',
            data: {
                labels: data.map((_,i) => i),
                datasets: [{ data, borderColor: color, borderWidth: 1.5, tension: 0.4, fill: false, pointRadius: 0 }]
            },
            options: { animation: false, responsive: true, maintainAspectRatio: false, plugins: { legend: {display:false}, tooltip: {enabled:false} }, scales: { x:{display:false}, y:{display:false} } }
        });
    }

    spark('sp1', [3,5,4,7,6,8,5,9,{{ $todayBookings }},{{ $todayBookings }}], RED);
    spark('sp2', [180,210,190,240,220,260,250,280,290,{{ min($totalCustomers ?: 300, 9999) }}], GREEN);
    spark('sp3', [90,95,100,98,105,110,108,115,120,{{ $activeVehicles ?: 10 }}], BLUE);
    spark('sp4', [2,5,3,8,4,6,5,9,7,{{ $pendingBookings }}], AMBER);

    // Main chart
    const ctx = document.getElementById('bChart');
    if (!ctx) return;
    const raw = @json($chartData);
    const labels = raw.map(d => d.label);
    const conf   = raw.map(d => d.confirmed);
    const pend   = raw.map(d => d.pending);

    const gR = ctx.getContext('2d').createLinearGradient(0,0,0,220);
    gR.addColorStop(0, RED_A30); gR.addColorStop(1, RED_A0);
    const gA = ctx.getContext('2d').createLinearGradient(0,0,0,220);
    gA.addColorStop(0,'rgba(245,158,11,0.15)'); gA.addColorStop(1,'rgba(245,158,11,0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label:'Confirmed', data:conf, borderColor:RED, backgroundColor:gR, borderWidth:2, tension:0.4, fill:true, pointRadius:3, pointBackgroundColor:RED, pointBorderColor:'#16161E', pointBorderWidth:2, pointHoverRadius:5 },
                { label:'Pending',   data:pend, borderColor:AMBER, backgroundColor:gA, borderWidth:1.5, tension:0.4, fill:true, pointRadius:2, pointBackgroundColor:AMBER, pointBorderColor:'#16161E', pointBorderWidth:2, pointHoverRadius:4, borderDash:[4,3] }
            ]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            interaction:{ mode:'index', intersect:false },
            plugins:{
                legend:{ display:true, position:'top', align:'end', labels:{ boxWidth:8, boxHeight:8, borderRadius:4, usePointStyle:true, padding:14, font:{size:11}, color:'#9B9BAD' } },
                tooltip:{ backgroundColor:'#1C1C26', borderColor:'#252535', borderWidth:1, titleColor:'#F0EFF6', bodyColor:'#9B9BAD', padding:12, titleFont:{family:"'Hanken Grotesk',sans-serif", size:13, weight:'600'}, callbacks:{ label: c => ` ${c.dataset.label}: ${c.parsed.y}` } }
            },
            scales:{
                x:{ grid:{display:false}, border:{display:false}, ticks:{font:{size:11},color:TICK,maxRotation:0} },
                y:{ beginAtZero:true, grid:{color:GRID}, border:{display:false}, ticks:{font:{size:11},color:TICK,precision:0,padding:8} }
            }
        }
    });
});
</script>
</x-filament-panels::page>
