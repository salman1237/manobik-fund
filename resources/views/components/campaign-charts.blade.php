@props(['campaign'])

@php($data = $campaign->publicChartData())

@if (empty($data['vitals']) && empty($data['milestones']) && empty($data['timeline']) && empty($data['fundUtilization']))
    {{-- Nothing verified yet - render nothing rather than an empty chart shell. --}}
@else
    <div class="space-y-6" x-data x-init="
        const charts = {};
        const renderCharts = () => {
            Object.values(charts).forEach(c => c.destroy());

            @foreach ($data['vitals'] as $type => $vital)
                {
                    const ctx = document.getElementById('vital-chart-{{ $campaign->id }}-{{ $type }}');
                    if (ctx) {
                        const points = @js($vital['points']);
                        charts['{{ $type }}'] = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: points.map(p => p.x),
                                datasets: [{
                                    label: @js($vital['label'].($vital['unit'] ? ' ('.$vital['unit'].')' : '')),
                                    data: points.map(p => p.y),
                                    borderColor: 'oklch(45% 0.10 175)',
                                    backgroundColor: 'oklch(45% 0.10 175 / 20%)',
                                    tension: 0.2,
                                }],
                            },
                            options: {
                                plugins: { legend: { display: false } },
                            },
                        });
                    }
                }
            @endforeach

            @if (! empty($data['fundUtilization']))
                {
                    const ctx = document.getElementById('fund-utilization-chart-{{ $campaign->id }}');
                    if (ctx) {
                        charts['fundUtilization'] = new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: @js(array_column($data['fundUtilization'], 'category')),
                                datasets: [{
                                    data: @js(array_column($data['fundUtilization'], 'amount')),
                                    backgroundColor: [
                                        'oklch(45% 0.10 175)',
                                        'oklch(62% 0.15 40)',
                                        'oklch(70% 0.14 70)',
                                        'oklch(55% 0.18 25)',
                                        'oklch(55% 0.11 260)',
                                    ],
                                }],
                            },
                        });
                    }
                }
            @endif
        };

        renderCharts();
        document.addEventListener('livewire:navigated', renderCharts);
    ">
        @if (! empty($data['milestones']))
            <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-6">
                <h3 class="font-serif text-lg font-semibold text-ink mb-4">Treatment Milestones</h3>
                <ul class="flex flex-col gap-2.5">
                    @foreach ($data['milestones'] as $milestone)
                        <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-sm">
                            <span class="text-ink-muted">{{ $milestone['label'] }}</span>
                            <span class="text-ink-faint shrink-0">{{ $milestone['value'] }} &middot; {{ \Illuminate\Support\Carbon::parse($milestone['recorded_at'])->format('M j') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! empty($data['timeline']))
            <div class="bg-warm-alt rounded-2xl p-6 text-center">
                <p class="font-serif text-3xl font-semibold text-primary-dark">{{ $data['timeline']['value'] }} {{ $data['timeline']['unit'] }}</p>
                <p class="text-[12.5px] text-ink-faint mt-1">{{ $data['timeline']['label'] }}</p>
            </div>
        @endif

        @foreach ($data['vitals'] as $type => $vital)
            <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-6">
                <h3 class="font-serif text-lg font-semibold text-ink mb-4">{{ $vital['label'] }}</h3>
                <canvas id="vital-chart-{{ $campaign->id }}-{{ $type }}" height="120"></canvas>
            </div>
        @endforeach

        @if (! empty($data['fundUtilization']))
            <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-6">
                <h3 class="font-serif text-lg font-semibold text-ink mb-4">Fund Utilization</h3>
                <canvas id="fund-utilization-chart-{{ $campaign->id }}" height="200"></canvas>
            </div>
        @endif
    </div>
@endif
