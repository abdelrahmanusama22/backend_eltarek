<x-filament-panels::page>
@php
    $metrics = $this->getMetrics();
@endphp

<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
#ana * { box-sizing: border-box; }
#ana {
    font-family: 'Inter', sans-serif;
    background: #0C0C10;
    color: #F0EFF6;
    margin: -8px -16px 0;
    min-height: 100vh;
    padding-bottom: 60px;
}

/* ── HEADER ── */
.ana-hdr {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 32px;
    background: #111118;
    border-bottom: 1px solid #1E1E2A;
    position: sticky; top: 0; z-index: 50;
    backdrop-filter: blur(16px);
}
.ana-title { font-family: 'Hanken Grotesk', sans-serif; font-size: 18px; font-weight: 700; color: #F0EFF6; margin: 0 0 2px; }
.ana-sub   { font-size: 12px; color: #9B9BAD; margin: 0; }

/* Period Tabs */
.period-tabs {
    display: inline-flex; align-items: center;
    background: #16161E; border: 1px solid #1E1E2A; border-radius: 10px; padding: 4px; gap: 2px;
}
.period-tab {
    padding: 7px 16px; border-radius: 7px;
    font-size: 12px; font-weight: 600; cursor: pointer; border: none;
    transition: all 0.15s ease; color: #9B9BAD; background: transparent;
    font-family: 'Inter', sans-serif;
}
.period-tab.active { background: #E01B22; color: #fff; box-shadow: 0 2px 12px rgba(224,27,34,0.35); }
.period-tab:not(.active):hover { color: #F0EFF6; background: #1E1E2A; }

.btn-refresh {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 14px; border-radius: 8px;
    font-size: 12px; font-weight: 500; cursor: pointer;
    border: 1px solid #1E1E2A; background: #16161E; color: #9B9BAD;
    font-family: 'Inter', sans-serif; transition: all 0.15s;
}
.btn-refresh:hover { background: #1C1C26; color: #F0EFF6; border-color: #252535; }

/* ── BODY ── */
.ana-body { padding: 24px 32px 0; }

/* ── KPI GRID ── */
.kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }
.kpi {
    background: #16161E; border: 1px solid #1E1E2A; border-radius: 14px;
    padding: 20px 22px; position: relative; overflow: hidden;
    transition: all 0.2s ease; cursor: default;
}
.kpi::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.04) 50%, transparent);
}
.kpi:hover { border-color: #252535; background: #1B1B24; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
.kpi.k-red:hover   { border-color: rgba(224,27,34,0.3);  box-shadow: 0 10px 30px rgba(224,27,34,0.08); }
.kpi.k-green:hover { border-color: rgba(16,185,129,0.3); box-shadow: 0 10px 30px rgba(16,185,129,0.08); }
.kpi.k-blue:hover  { border-color: rgba(59,130,246,0.3);  box-shadow: 0 10px 30px rgba(59,130,246,0.08); }
.kpi.k-amber:hover { border-color: rgba(245,158,11,0.3);  box-shadow: 0 10px 30px rgba(245,158,11,0.08); }

.kpi-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; }
.kpi-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.kpi-icon .ms { font-size: 19px; }
.i-red   { background: rgba(224,27,34,0.12); color: #FF5F65; }
.i-green { background: rgba(16,185,129,0.12); color: #10B981; }
.i-blue  { background: rgba(59,130,246,0.12);  color: #60A5FA; }
.i-amber { background: rgba(245,158,11,0.12);  color: #FCD34D; }
.i-purple { background: rgba(168,85,247,0.12); color: #C084FC; }

.kpi-badge {
    display: flex; align-items: center; gap: 3px;
    font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px;
    font-family: 'JetBrains Mono', monospace;
}
.kpi-badge .ms { font-size: 12px; }
.b-up   { background: rgba(16,185,129,0.12); color: #10B981; }
.b-down { background: rgba(224,27,34,0.12);  color: #FF5F65; }
.b-flat { background: rgba(155,155,173,0.08); color: #9B9BAD; }
.b-warn { background: rgba(245,158,11,0.12); color: #FCD34D; }

.kpi-num {
    font-family: 'Hanken Grotesk', sans-serif;
    font-size: 34px; font-weight: 700; color: #F0EFF6;
    line-height: 1; letter-spacing: -1.5px; margin-bottom: 5px;
}
.kpi-lbl { font-size: 12.5px; color: #9B9BAD; }
.kpi-sub { font-size: 11px; color: #55556A; margin-top: 3px; }

/* ── PANEL ── */
.panel { background: #16161E; border: 1px solid #1E1E2A; border-radius: 14px; overflow: hidden; }
.panel-hdr {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 22px 14px; border-bottom: 1px solid #1E1E2A;
}
.panel-title {
    font-family: 'Hanken Grotesk', sans-serif; font-size: 13.5px; font-weight: 600; color: #F0EFF6;
    display: flex; align-items: center; gap: 8px; margin: 0;
}
.panel-title .ms { font-size: 16px; color: #9B9BAD; }
.panel-sub { font-size: 12px; color: #55556A; margin: 3px 0 0; }

/* ── CHART ── */
.chart-wrap { padding: 18px 22px; height: 260px; position: relative; }

/* ── MID GRID ── */
.mid-grid { display: grid; grid-template-columns: 1fr 360px; gap: 14px; margin-bottom: 20px; }

/* ── BOTTOM GRID ── */
.bot-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px; }

/* ── RANK LIST ── */
.rank-list { padding: 6px 0; }
.rank-item {
    display: flex; align-items: center; gap: 14px;
    padding: 11px 22px; transition: background 0.12s; cursor: default;
}
.rank-item:hover { background: #1C1C26; }
.rank-num {
    width: 26px; height: 26px; border-radius: 7px;
    background: #1E1E2A; border: 1px solid #252535;
    display: flex; align-items: center; justify-content: center;
    font-size: 11.5px; font-weight: 700; color: #55556A;
    font-family: 'JetBrains Mono', monospace; flex-shrink: 0;
}
.rank-item:first-child .rank-num { background: rgba(224,27,34,0.12); border-color: rgba(224,27,34,0.2); color: #E01B22; }
.rank-item:nth-child(2) .rank-num { background: rgba(245,158,11,0.1); border-color: rgba(245,158,11,0.2); color: #FCD34D; }
.rank-item:nth-child(3) .rank-num { background: rgba(59,130,246,0.1); border-color: rgba(59,130,246,0.2); color: #60A5FA; }
.rank-icon { width: 38px; height: 38px; border-radius: 9px; background: #1E1E2A; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.rank-icon .ms { font-size: 18px; color: #55556A; }
.rank-info { flex: 1; min-width: 0; }
.rank-name { font-size: 13px; font-weight: 500; color: #F0EFF6; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; }
.rank-meta { font-size: 11.5px; color: #9B9BAD; }
.rank-val {
    font-family: 'JetBrains Mono', monospace; font-size: 11.5px; font-weight: 600;
    padding: 3px 9px; border-radius: 20px; flex-shrink: 0;
}
.rv-red   { background: rgba(224,27,34,0.1);   color: #FF5F65;  }
.rv-blue  { background: rgba(59,130,246,0.1);  color: #60A5FA;  }
.rv-green { background: rgba(16,185,129,0.1);  color: #10B981;  }

/* ── FEED ── */
.feed-list { padding: 8px 0; max-height: 340px; overflow-y: auto; }
.feed-list::-webkit-scrollbar { width: 4px; }
.feed-list::-webkit-scrollbar-track { background: transparent; }
.feed-list::-webkit-scrollbar-thumb { background: #252535; border-radius: 4px; }
.feed-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 22px; transition: background 0.1s; border-bottom: 1px solid #1A1A24;
}
.feed-item:last-child { border-bottom: none; }
.feed-item:hover { background: #1C1C26; }
.feed-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.fd-red    { background: #E01B22; box-shadow: 0 0 6px rgba(224,27,34,0.5); }
.fd-green  { background: #10B981; box-shadow: 0 0 6px rgba(16,185,129,0.5); }
.fd-blue   { background: #60A5FA; box-shadow: 0 0 6px rgba(59,130,246,0.5); }
.fd-purple { background: #C084FC; box-shadow: 0 0 6px rgba(168,85,247,0.5); }
.feed-info { flex: 1; min-width: 0; }
.feed-event { font-size: 12.5px; font-weight: 500; color: #F0EFF6; font-family: 'JetBrains Mono', monospace; }
.feed-user  { font-size: 11.5px; color: #9B9BAD; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.feed-time  { font-size: 11px; color: #55556A; font-family: 'JetBrains Mono', monospace; flex-shrink: 0; }

/* Live pulse dot */
.live-dot { position: relative; display: inline-flex; width: 10px; height: 10px; }
.live-dot-ping { position: absolute; width: 100%; height: 100%; border-radius: 50%; background: #10B981; opacity: 0.75; animation: ping 1.5s cubic-bezier(0,0,0.2,1) infinite; }
.live-dot-core { position: relative; width: 10px; height: 10px; border-radius: 50%; background: #10B981; }
@keyframes ping { 75%,100% { transform: scale(2); opacity: 0; } }

/* Empty state */
.empty-s { padding: 44px 22px; text-align: center; }
.empty-s .ms { font-size: 40px; color: #252535; display: block; margin-bottom: 10px; }
.empty-s p { font-size: 13px; color: #55556A; margin: 0; }

/* ── CATEGORY TAG ── */
.cat-tag {
    display: inline-flex; align-items: center;
    font-size: 10.5px; font-weight: 600; padding: 2px 8px;
    border-radius: 6px; font-family: 'JetBrains Mono', monospace;
}
.ct-booking { background: rgba(224,27,34,0.1);   color: #FF5F65; }
.ct-catalog  { background: rgba(59,130,246,0.1);  color: #60A5FA; }
.ct-finance  { background: rgba(16,185,129,0.1);  color: #10B981; }
.ct-general  { background: rgba(168,85,247,0.1);  color: #C084FC; }

/* ── DIVIDER ── */
.ana-divider { height: 1px; background: #1E1E2A; margin: 0 22px; }

.ms { font-family: 'Material Symbols Outlined'; font-weight: 300; font-style: normal; font-size: 20px; line-height: 1; letter-spacing: normal; text-transform: none; white-space: nowrap; word-wrap: normal; direction: ltr; }

@media (max-width: 1280px) {
    .kpi-row  { grid-template-columns: repeat(2, 1fr); }
    .mid-grid { grid-template-columns: 1fr; }
    .bot-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .ana-body { padding: 14px; }
    .kpi-row  { grid-template-columns: 1fr 1fr; gap: 10px; }
}
</style>

<div id="ana">

{{-- ═══════════════════ HEADER ═══════════════════ --}}
<div class="ana-hdr">
    <div>
        <p class="ana-title">
            <span class="ms" style="font-size:18px;color:#E01B22;vertical-align:-3px">bar_chart</span>
            Analytics & Mobile Insights
        </p>
        <p class="ana-sub">Live telemetry, test-drive conversion rates, and catalog engagement</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
        <button class="btn-refresh" onclick="window.location.reload()">
            <span class="ms" style="font-size:15px">refresh</span> Refresh
        </button>
        <div class="period-tabs">
            <button wire:click="setPeriod('today')"  class="period-tab {{ $period === 'today'  ? 'active' : '' }}">Today</button>
            <button wire:click="setPeriod('7days')"  class="period-tab {{ $period === '7days'  ? 'active' : '' }}">7 Days</button>
            <button wire:click="setPeriod('30days')" class="period-tab {{ $period === '30days' ? 'active' : '' }}">30 Days</button>
            <button wire:click="setPeriod('all')"    class="period-tab {{ $period === 'all'    ? 'active' : '' }}">All Time</button>
        </div>
    </div>
</div>

<div class="ana-body">

{{-- ═══════════════════ KPI CARDS ═══════════════════ --}}
<div class="kpi-row">

    {{-- Bookings --}}
    <div class="kpi k-red">
        <div class="kpi-top">
            <div class="kpi-icon i-red"><span class="ms">calendar_month</span></div>
            <div class="kpi-badge b-{{ $metrics['totalBookings'] > 0 ? 'up' : 'flat' }}">
                <span class="ms">{{ $metrics['totalBookings'] > 0 ? 'trending_up' : 'trending_flat' }}</span>
                {{ $metrics['confirmedBookings'] }} confirmed
            </div>
        </div>
        <div class="kpi-num">{{ number_format($metrics['totalBookings']) }}</div>
        <div class="kpi-lbl">Total Bookings</div>
        <div class="kpi-sub">Test-drive & showroom requests</div>
    </div>

    {{-- Conversion Rate --}}
    <div class="kpi k-green">
        <div class="kpi-top">
            <div class="kpi-icon i-green"><span class="ms">conversion_path</span></div>
            <div class="kpi-badge {{ $metrics['conversionRate'] >= 50 ? 'b-up' : ($metrics['conversionRate'] >= 20 ? 'b-warn' : 'b-down') }}">
                <span class="ms">{{ $metrics['conversionRate'] >= 50 ? 'trending_up' : 'trending_down' }}</span>
                {{ $metrics['conversionRate'] >= 50 ? 'Healthy' : ($metrics['conversionRate'] >= 20 ? 'Average' : 'Low') }}
            </div>
        </div>
        <div class="kpi-num">{{ $metrics['conversionRate'] }}<span style="font-size:18px;color:#9B9BAD">%</span></div>
        <div class="kpi-lbl">Conversion Rate</div>
        <div class="kpi-sub">Confirmed ÷ Total bookings</div>
    </div>

    {{-- App Interactions --}}
    <div class="kpi k-blue">
        <div class="kpi-top">
            <div class="kpi-icon i-blue"><span class="ms">touch_app</span></div>
            <div class="kpi-badge b-{{ $metrics['totalEvents'] > 0 ? 'up' : 'flat' }}">
                <span class="ms">{{ $metrics['totalEvents'] > 0 ? 'phone_iphone' : 'smartphone' }}</span>
                Live telemetry
            </div>
        </div>
        <div class="kpi-num">{{ number_format($metrics['totalEvents']) }}</div>
        <div class="kpi-lbl">App Interactions</div>
        <div class="kpi-sub">Screens, compares & calculations</div>
    </div>

    {{-- Customers --}}
    <div class="kpi k-amber">
        <div class="kpi-top">
            <div class="kpi-icon i-amber"><span class="ms">group</span></div>
            <div class="kpi-badge b-{{ $metrics['activeUsers'] > 0 ? 'up' : 'flat' }}">
                <span class="ms">{{ $metrics['activeUsers'] > 0 ? 'person_add' : 'person' }}</span>
                +{{ $metrics['activeUsers'] }} new
            </div>
        </div>
        <div class="kpi-num">{{ number_format($metrics['totalCustomers']) }}</div>
        <div class="kpi-lbl">Total Customers</div>
        <div class="kpi-sub">Verified phone accounts</div>
    </div>

</div>

{{-- ═══════════════════ CHART + BRANCHES ═══════════════════ --}}
<div class="mid-grid">

    {{-- Activity Chart --}}
    <div class="panel">
        <div class="panel-hdr">
            <div>
                <p class="panel-title"><span class="ms">show_chart</span> Daily Booking & App Activity</p>
                <p class="panel-sub">Trend of test-drive requests and live app sessions</p>
            </div>
            <div style="display:flex;align-items:center;gap:16px">
                <div style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:#9B9BAD;">
                    <span style="width:20px;height:2px;background:#E01B22;border-radius:2px;display:inline-block"></span> Bookings
                </div>
                <div style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:#9B9BAD;">
                    <span style="width:20px;height:2px;background:#60A5FA;border-radius:2px;display:inline-block;border-top:2px dashed #60A5FA;border-bottom:none"></span> App Events
                </div>
            </div>
        </div>
        <div class="chart-wrap">
            <canvas id="ana-chart"></canvas>
        </div>
    </div>

    {{-- Top Branches --}}
    <div class="panel">
        <div class="panel-hdr">
            <div>
                <p class="panel-title"><span class="ms">store</span> Top Showrooms</p>
                <p class="panel-sub">Branches with highest appointment volume</p>
            </div>
        </div>
        <div class="rank-list">
            @forelse($metrics['branchBookings'] as $item)
                <div class="rank-item">
                    <div class="rank-num">{{ $loop->iteration }}</div>
                    <div class="rank-icon"><span class="ms">location_on</span></div>
                    <div class="rank-info">
                        <div class="rank-name">{{ $item->branch?->name ?? 'Showroom #'.$item->branch_id }}</div>
                        <div class="rank-meta">{{ $item->branch?->city?->name ?? 'Cairo' }}</div>
                    </div>
                    <div class="rank-val rv-red">{{ $item->total }} bookings</div>
                </div>
            @empty
                <div class="empty-s">
                    <span class="ms">store_mall_directory</span>
                    <p>No branch data in this period</p>
                </div>
            @endforelse
        </div>
    </div>

</div>

{{-- ═══════════════════ BOTTOM: VEHICLES + LIVE FEED ═══════════════════ --}}
<div class="bot-grid">

    {{-- Most Viewed Vehicles --}}
    <div class="panel">
        <div class="panel-hdr">
            <div>
                <p class="panel-title"><span class="ms">visibility</span> Most Viewed Vehicles</p>
                <p class="panel-sub">Cars generating highest interest from app users</p>
            </div>
            <div style="background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2);border-radius:8px;padding:4px 10px;font-size:11px;color:#60A5FA;font-family:'JetBrains Mono',monospace;">
                <span class="ms" style="font-size:12px;vertical-align:-2px">phone_iphone</span> Mobile data
            </div>
        </div>
        <div class="rank-list">
            @forelse($metrics['topViewedVehicles'] as $vehicle)
                <div class="rank-item">
                    <div class="rank-num">{{ $loop->iteration }}</div>
                    <div class="rank-icon"><span class="ms">directions_car</span></div>
                    <div class="rank-info">
                        <div class="rank-name">{{ $vehicle->model_name ?: 'Unknown Model' }}</div>
                        <div class="rank-meta">Catalog engagement</div>
                    </div>
                    <div class="rank-val rv-blue">{{ number_format($vehicle->views) }} views</div>
                </div>
            @empty
                <div class="empty-s">
                    <span class="ms">directions_car</span>
                    <p>No vehicle telemetry recorded yet<br>
                    <small style="font-size:11px;color:#3D3D50;">Launch the mobile app to start tracking</small></p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Live Mobile Activity Feed --}}
    <div class="panel">
        <div class="panel-hdr">
            <div>
                <p class="panel-title">
                    <span class="ms">rss_feed</span>
                    Live Mobile Activity
                </p>
                <p class="panel-sub">Real-time interaction stream from app users</p>
            </div>
            <div style="display:flex;align-items:center;gap:7px">
                <div class="live-dot"><span class="live-dot-ping"></span><span class="live-dot-core"></span></div>
                <span style="font-size:11.5px;color:#10B981;font-weight:600">Live</span>
            </div>
        </div>
        <div class="feed-list">
            @forelse($metrics['recentEvents'] as $evt)
                @php
                    $catClass = match($evt->category) {
                        'booking'  => ['dot' => 'fd-red',    'tag' => 'ct-booking'],
                        'catalog'  => ['dot' => 'fd-blue',   'tag' => 'ct-catalog'],
                        'finance'  => ['dot' => 'fd-green',  'tag' => 'ct-finance'],
                        default    => ['dot' => 'fd-purple', 'tag' => 'ct-general'],
                    };
                @endphp
                <div class="feed-item">
                    <div class="feed-dot {{ $catClass['dot'] }}"></div>
                    <div class="feed-info">
                        <div class="feed-event">{{ $evt->event_name }}</div>
                        <div class="feed-user">
                            <span class="cat-tag {{ $catClass['tag'] }}">{{ $evt->category }}</span>
                            &nbsp;{{ $evt->user?->name ?? ($evt->user?->phone ? substr($evt->user->phone, 0, 7).'***' : 'Guest') }}
                        </div>
                    </div>
                    <div class="feed-time">{{ $evt->created_at?->diffForHumans(short: true) ?? '—' }}</div>
                </div>
            @empty
                <div class="empty-s">
                    <span class="ms">rss_feed</span>
                    <p>Waiting for mobile events...<br>
                    <small style="font-size:11px;color:#3D3D50;">Events appear here as users interact with the app</small></p>
                </div>
            @endforelse
        </div>
    </div>

</div>

</div> {{-- /ana-body --}}
</div> {{-- /ana --}}

<script>
    document.addEventListener('livewire:navigated', initAnaChart);
    document.addEventListener('DOMContentLoaded', initAnaChart);

    function initAnaChart() {
        const ctx = document.getElementById('ana-chart');
        if (!ctx) return;
        if (window._anaChart) { window._anaChart.destroy(); }

        const labels   = @json($metrics['chartLabels']);
        const bookings = @json($metrics['chartBookings']);
        const events   = @json($metrics['chartEvents']).map(v => Math.round(v / 10));

        window._anaChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Bookings',
                        data: bookings,
                        borderColor: '#E01B22',
                        backgroundColor: (ctx) => {
                            const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 240);
                            g.addColorStop(0, 'rgba(224,27,34,0.18)');
                            g.addColorStop(1, 'rgba(224,27,34,0)');
                            return g;
                        },
                        fill: true, tension: 0.4, borderWidth: 2,
                        pointBackgroundColor: '#E01B22', pointRadius: 3, pointHoverRadius: 6,
                        pointBorderColor: '#0C0C10', pointBorderWidth: 2,
                    },
                    {
                        label: 'App Events (÷10)',
                        data: events,
                        borderColor: '#60A5FA',
                        backgroundColor: 'transparent',
                        borderDash: [5, 4], tension: 0.4, borderWidth: 1.5,
                        pointBackgroundColor: '#60A5FA', pointRadius: 2, pointHoverRadius: 5,
                        pointBorderColor: '#0C0C10', pointBorderWidth: 2,
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#16161E',
                        borderColor: '#252535',
                        borderWidth: 1,
                        titleColor: '#F0EFF6',
                        bodyColor: '#9B9BAD',
                        padding: 12,
                        titleFont: { family: 'Hanken Grotesk', weight: '600', size: 13 },
                        bodyFont: { family: 'Inter', size: 12 },
                        cornerRadius: 10,
                        callbacks: {
                            label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y}`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.03)', drawTicks: false },
                        border: { dash: [4, 4], color: 'transparent' },
                        ticks: { color: '#55556A', font: { size: 11, family: 'JetBrains Mono' }, maxTicksLimit: 8 }
                    },
                    y: {
                        grid: { color: 'rgba(255,255,255,0.04)', drawTicks: false },
                        border: { display: false },
                        ticks: { color: '#55556A', font: { size: 11, family: 'JetBrains Mono' }, stepSize: 1, maxTicksLimit: 6 },
                        beginAtZero: true
                    }
                }
            }
        });
    }
</script>

</x-filament-panels::page>
