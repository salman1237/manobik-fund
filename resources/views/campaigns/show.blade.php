@php
    $categoryLabels = [
        'treatment' => 'Treatment Fund',
        'emergency' => 'Emergency Response',
        'camp' => 'Medical Camp',
        'education' => 'Education & Training',
    ];
    $categoryGradients = [
        'treatment' => 'linear-gradient(135deg, oklch(52% 0.09 175) 0%, oklch(68% 0.10 165) 100%)',
        'emergency' => 'linear-gradient(135deg, oklch(62% 0.14 35) 0%, oklch(72% 0.15 55) 100%)',
        'camp' => 'linear-gradient(135deg, oklch(55% 0.11 260) 0%, oklch(70% 0.09 240) 100%)',
        'education' => 'linear-gradient(135deg, oklch(58% 0.10 95) 0%, oklch(74% 0.11 90) 100%)',
    ];
    $isVerified = $campaign->fieldVisitReports->isNotEmpty();
@endphp

<x-public-layout :title="$campaign->title">
    <div class="py-10">
        <div class="max-w-6xl mx-auto px-6 sm:px-10 lg:px-16">
            <div class="flex items-center gap-2 text-[13px] text-ink-faint mb-6 min-w-0">
                <a href="{{ route('campaigns.index') }}" wire:navigate class="text-ink-faint hover:text-primary shrink-0">Campaigns</a>
                <span class="shrink-0">/</span>
                <span class="text-ink truncate">{{ $campaign->title }}</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-10">
                <!-- LEFT -->
                <div class="flex flex-col gap-7 min-w-0">
                    <div>
                        <div class="flex flex-wrap items-center gap-3 mb-3.5">
                            <span class="text-xs font-bold text-primary-dark bg-primary-light px-3 py-1.5 rounded-full uppercase tracking-wide">
                                {{ $categoryLabels[$campaign->category] ?? ucfirst($campaign->category) }}
                            </span>
                            @if ($isVerified)
                                <span class="flex items-center gap-1.5 text-xs font-bold text-primary-dark">
                                    <svg class="h-[13px] w-[13px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                    Verified by Manobik Fund
                                </span>
                            @endif
                        </div>
                        <h1 class="font-serif text-3xl sm:text-4xl font-semibold leading-tight tracking-tight mb-3">{{ $campaign->title }}</h1>
                        <div class="flex items-center gap-1.5 text-sm text-ink-muted">
                            <svg class="h-[15px] w-[15px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-6.5-4.35-8.5-8.15C1.8 9.4 3.2 6 6.5 5.6A5 5 0 0 1 12 8.4 5 5 0 0 1 17.5 5.6c3.3.4 4.7 3.8 3 7.25C18.5 16.65 12 21 12 21Z"/></svg>
                            {{ $campaign->hospital_name }}
                        </div>
                    </div>

                    <div class="rounded-[20px]" style="height:340px; background: {{ $categoryGradients[$campaign->category] ?? $categoryGradients['treatment'] }};"></div>

                    <p class="text-[15.5px] leading-relaxed text-ink-muted whitespace-pre-line">{{ $campaign->description }}</p>

                    <x-campaign-charts :campaign="$campaign" />

                    @if ($campaign->disbursements->isNotEmpty())
                        <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-7">
                            <div class="flex items-center gap-2.5 mb-5">
                                <svg class="h-5 w-5 text-accent-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 3 6v6c0 5 3.8 8.7 9 10 5.2-1.3 9-5 9-10V6l-9-4Z"/></svg>
                                <h2 class="font-serif text-lg font-semibold text-ink">Transparency: Fund Disbursement</h2>
                            </div>
                            <ul class="flex flex-col gap-3.5">
                                @foreach ($campaign->disbursements as $disbursement)
                                    <li class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm border-b border-warm-border-soft pb-3.5 last:border-0 last:pb-0">
                                        <div>
                                            <p class="text-ink font-semibold">{{ number_format($disbursement->amount / 100, 2) }} BDT disbursed</p>
                                            <p class="text-ink-faint text-xs mt-0.5">{{ $disbursement->disbursed_at->format('M j, Y') }}</p>
                                        </div>
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($disbursement->deposit_slip_file) }}"
                                           target="_blank" rel="noopener" class="text-primary font-semibold hover:text-primary-dark text-xs shrink-0">
                                            View Deposit Slip
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <div class="flex items-center gap-2.5 mb-5">
                            <svg class="h-5 w-5 text-ink" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v12H7l-3 3V4Z"/></svg>
                            <h2 class="font-serif text-lg font-semibold text-ink">{{ $campaign->needsMedicalTracking() ? 'Patient Updates' : 'Updates' }}</h2>
                            @if ($campaign->needsMedicalTracking())
                                <span class="text-xs font-bold text-primary-dark bg-primary-light px-2.5 py-1 rounded-full ml-1">Posted daily</span>
                            @endif
                        </div>

                        @forelse ($campaign->updates as $update)
                            <div class="flex gap-4.5">
                                <div class="flex flex-col items-center w-3.5 shrink-0">
                                    <div class="w-2.5 h-2.5 rounded-full bg-primary shrink-0 mt-1.5"></div>
                                    @unless ($loop->last)
                                        <div class="w-0.5 flex-1 bg-warm-border-soft"></div>
                                    @endunless
                                </div>
                                <div class="pb-6.5 min-w-0 flex-1">
                                    <div class="text-[12.5px] text-ink-faint mb-1">{{ $update->created_at->format('M j, Y - g:i A') }}</div>
                                    <div class="bg-warm-surface border border-warm-border-soft rounded-2xl px-4.5 py-4 text-[14.5px] leading-relaxed text-ink-muted whitespace-pre-line">{{ $update->content }}</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-ink-faint">No updates posted yet.</p>
                        @endforelse
                    </div>
                </div>

                <!-- RIGHT -->
                <div>
                    <livewire:donations.donation-form :campaign="$campaign" />
                </div>
            </div>
        </div>
    </div>
</x-public-layout>
