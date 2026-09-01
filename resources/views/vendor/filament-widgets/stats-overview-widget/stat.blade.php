@php
    use Filament\Support\Enums\IconPosition;
    use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
    use Filament\Widgets\View\Components\StatsOverviewWidgetComponent\StatComponent\DescriptionComponent;
    use Filament\Widgets\View\Components\StatsOverviewWidgetComponent\StatComponent\StatsOverviewWidgetStatChartComponent;

    $chartColor = $getChartColor() ?? 'gray';
    $descriptionColor = $getDescriptionColor() ?? 'gray';
    $descriptionIcon = $getDescriptionIcon();
    $descriptionIconPosition = $getDescriptionIconPosition();
    $url = $getUrl();
    $tag = $url ? 'a' : 'div';
@endphp

<{!! $tag !!}
    @if ($url)
        {{ \Filament\Support\generate_href_html($url, $shouldOpenUrlInNewTab()) }}
    @endif
    {{
        $getExtraAttributeBag()
            ->class([
                'fi-wi-stats-overview-stat flex flex-col relative group',
            ])
    }}
>
    <div class="flex flex-col relative group w-full">
        <div class="fi-wi-stats-overview-stat-label-ctn flex items-center gap-1">
            {{ \Filament\Support\generate_icon_html($getIcon()) }}
            <span class="fi-wi-stats-overview-stat-label text-[#94949E] font-inter text-sm mb-1">
                {{ $getLabel() }}
            </span>
        </div>

        <div class="flex items-baseline gap-3">
            <div class="fi-wi-stats-overview-stat-value font-bold text-4xl text-white font-hanken">
                {{ $getValue() }}
            </div>

            @if ($description = $getDescription())
                <div
                    {{ (new FilamentComponentAttributeBag)->color(DescriptionComponent::class, $descriptionColor)->class(['flex items-center gap-1 text-sm font-inter whitespace-nowrap']) }}
                >
                    @if ($descriptionIcon && in_array($descriptionIconPosition, [IconPosition::Before, 'before']))
                        {{ \Filament\Support\generate_icon_html($descriptionIcon, attributes: (new \Filament\Support\View\ComponentAttributeBag)->class(['w-4 h-4'])) }}
                    @endif

                    <span>
                        {{ $description }}
                    </span>

                    @if ($descriptionIcon && in_array($descriptionIconPosition, [IconPosition::After, 'after']))
                        {{ \Filament\Support\generate_icon_html($descriptionIcon, attributes: (new \Filament\Support\View\ComponentAttributeBag)->class(['w-4 h-4'])) }}
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if ($chart = $getChart())
        {{-- An empty function to initialize the Alpine component with until it's loaded with `x-load`. --}}
        <div x-data="{ statsOverviewStatChart() {} }">
            <div
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('stats-overview/stat/chart', 'filament/widgets') }}"
                wire:ignore
                x-data="statsOverviewStatChart({
                            key: @js($getKey(false)),
                            labels: @js(array_keys($chart)),
                            values: @js(array_values($chart)),
                        })"
                {{ (new FilamentComponentAttributeBag)->color(StatsOverviewWidgetStatChartComponent::class, $chartColor)->class(['fi-wi-stats-overview-stat-chart']) }}
            >
                {{-- The label and value are already exposed as text, so the trend sparkline is decorative. --}}
                <canvas x-ref="canvas" aria-hidden="true"></canvas>

                <span
                    x-ref="backgroundColorElement"
                    class="fi-wi-stats-overview-stat-chart-bg-color"
                ></span>

                <span
                    x-ref="borderColorElement"
                    class="fi-wi-stats-overview-stat-chart-border-color"
                ></span>
            </div>
        </div>
    @endif
</{!! $tag !!}>
