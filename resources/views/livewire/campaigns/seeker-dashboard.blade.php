@php
    $categoryGradients = [
        'treatment' => 'linear-gradient(135deg, oklch(52% 0.09 175) 0%, oklch(68% 0.10 165) 100%)',
        'emergency' => 'linear-gradient(135deg, oklch(62% 0.14 35) 0%, oklch(72% 0.15 55) 100%)',
        'camp' => 'linear-gradient(135deg, oklch(55% 0.11 260) 0%, oklch(70% 0.09 240) 100%)',
        'education' => 'linear-gradient(135deg, oklch(58% 0.10 95) 0%, oklch(74% 0.11 90) 100%)',
    ];
@endphp

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-serif text-2xl font-semibold text-ink mb-1">My Campaigns</h1>
            <p class="text-sm text-ink-faint">Track how each of your campaigns is progressing.</p>
        </div>

        @if (auth()->user()->isDonationSeeker())
            <a href="{{ route('seeker.campaigns.create') }}" wire:navigate
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary-dark transition">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
                Start a Campaign
            </a>
        @else
            <span class="text-sm text-ink-faint">Verify your email to start a campaign.</span>
        @endif
    </div>

    @if ($campaigns->isEmpty())
        <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-10 text-center text-ink-faint">
            You haven't started any campaigns yet.
        </div>
    @else
        <div class="flex flex-col gap-3.5">
            @foreach ($campaigns as $campaign)
                <div class="bg-warm-surface border border-warm-border-soft rounded-2xl px-6.5 py-5.5 flex items-center gap-5.5">
                    <div class="w-[72px] h-[72px] rounded-[13px] shrink-0" style="background: {{ $categoryGradients[$campaign->category] ?? $categoryGradients['treatment'] }};"></div>

                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2.5 mb-1.5">
                            <span class="font-serif text-[16.5px] font-semibold text-ink truncate">{{ $campaign->title }}</span>
                            <span @class([
                                'text-[11.5px] font-bold px-2.5 py-1 rounded-full shrink-0',
                                'text-ink-faint bg-warm-alt' => $campaign->status === 'draft',
                                'text-warn-dark bg-warn-light' => in_array($campaign->status, ['pending_verification', 'field_visit', 'executive_review']),
                                'text-primary-dark bg-primary-light' => in_array($campaign->status, ['published', 'funded', 'completed']),
                                'text-danger bg-danger-light' => in_array($campaign->status, ['rejected', 'cancelled']),
                            ])>
                                {{ ucfirst(str_replace('_', ' ', $campaign->status)) }}
                            </span>
                        </div>

                        @if (in_array($campaign->status, ['published', 'funded', 'completed']))
                            <div class="h-1.5 w-full max-w-[280px] bg-warm-border-soft rounded-full overflow-hidden mb-1.5">
                                <div class="h-full bg-primary rounded-full" style="width: {{ $campaign->progressPercentage() }}%"></div>
                            </div>
                            <p class="text-[12.5px] text-ink-faint">
                                ৳{{ number_format($campaign->raised_amount / 100) }} raised of ৳{{ number_format($campaign->target_amount / 100) }} &middot; {{ $campaign->progressPercentage() }}%
                            </p>
                        @else
                            <p class="text-[12.5px] text-ink-faint">{{ ucfirst($campaign->category) }}</p>
                        @endif

                        @if ($campaign->status === 'rejected' && $campaign->rejection_reason)
                            <p class="text-xs text-danger mt-1.5">Reason: {{ $campaign->rejection_reason }}</p>
                        @endif
                    </div>

                    <div class="shrink-0">
                        @if ($campaign->isEditableBySeeker())
                            <a href="{{ route('seeker.campaigns.edit', $campaign) }}" wire:navigate
                               class="text-[13.5px] font-bold text-primary hover:text-primary-dark">Continue editing &rarr;</a>
                        @else
                            <a href="{{ route('seeker.campaigns.show', $campaign) }}" wire:navigate
                               class="text-[13.5px] font-bold text-primary hover:text-primary-dark">View &rarr;</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
