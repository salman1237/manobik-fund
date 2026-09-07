@php
    $categoryMeta = [
        'treatment' => ['label' => 'Treatment Fund', 'text' => 'text-primary-dark', 'gradient' => 'linear-gradient(135deg, oklch(52% 0.09 175) 0%, oklch(68% 0.10 165) 100%)', 'bar' => 'bg-primary'],
        'emergency' => ['label' => 'Emergency Response', 'text' => 'text-accent-dark', 'gradient' => 'linear-gradient(135deg, oklch(62% 0.14 35) 0%, oklch(72% 0.15 55) 100%)', 'bar' => 'bg-accent'],
        'camp' => ['label' => 'Medical Camp', 'text' => 'text-[oklch(48%_0.11_260)]', 'gradient' => 'linear-gradient(135deg, oklch(55% 0.11 260) 0%, oklch(70% 0.09 240) 100%)', 'bar' => 'bg-[oklch(55%_0.11_260)]'],
        'education' => ['label' => 'Education & Training', 'text' => 'text-[oklch(45%_0.10_95)]', 'gradient' => 'linear-gradient(135deg, oklch(58% 0.10 95) 0%, oklch(74% 0.11 90) 100%)', 'bar' => 'bg-[oklch(58%_0.10_95)]'],
    ];
@endphp

<x-public-layout title="Browse Campaigns">
    @if (request()->routeIs('home'))
        <div class="relative overflow-hidden px-6 sm:px-10 lg:px-16 pt-16 pb-14" style="background:linear-gradient(155deg, oklch(96% 0.03 175) 0%, oklch(98% 0.012 75) 55%);">
            <div class="relative max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-primary-light text-primary-dark rounded-full text-xs font-semibold mb-6">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg>
                    Volunteer-verified before a single taka moves
                </div>
                <h1 class="font-serif text-4xl sm:text-5xl font-semibold leading-tight tracking-tight mb-5">
                    Real people.<br>Real needs, tracked in real time.
                </h1>
                <p class="text-lg leading-relaxed text-ink-muted mb-8 max-w-lg">
                    Every campaign on Manobik Fund is field-visited, verified, and shows exactly where donations go &mdash; live progress bars, medical updates, and disbursement proof, not just a fundraising thermometer.
                </p>
                <div class="flex flex-wrap items-center gap-3.5">
                    <a href="#browse" class="inline-flex items-center gap-2 px-7 py-3.5 bg-accent-dark text-white text-[15px] font-bold rounded-xl shadow-[0_8px_20px_-6px_oklch(45%_0.15_38_/_45%)] hover:opacity-95 transition">
                        Browse Campaigns
                        <svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                    @auth
                        <a href="{{ route('seeker.campaigns.create') }}" wire:navigate class="inline-flex items-center px-6 py-3.5 border-[1.5px] border-warm-border text-ink text-[15px] font-semibold rounded-xl hover:bg-warm-alt transition">
                            Start a Campaign
                        </a>
                    @else
                        <a href="{{ route('register') }}" wire:navigate class="inline-flex items-center px-6 py-3.5 border-[1.5px] border-warm-border text-ink text-[15px] font-semibold rounded-xl hover:bg-warm-alt transition">
                            Start a Campaign
                        </a>
                    @endauth
                </div>
            </div>

            <div class="relative grid grid-cols-2 sm:grid-cols-4 gap-px bg-warm-border-soft rounded-2xl overflow-hidden mt-14 max-w-4xl shadow-sm">
                <div class="bg-warm-surface px-6 py-6">
                    <div class="font-serif text-2xl font-semibold">৳{{ number_format($stats['totalRaised'] / 100) }}</div>
                    <div class="text-xs text-ink-faint mt-1">Raised for real patients</div>
                </div>
                <div class="bg-warm-surface px-6 py-6">
                    <div class="font-serif text-2xl font-semibold">{{ $stats['campaignsFunded'] }}</div>
                    <div class="text-xs text-ink-faint mt-1">Campaigns fully funded</div>
                </div>
                <div class="bg-warm-surface px-6 py-6">
                    <div class="font-serif text-2xl font-semibold">{{ $stats['verifiedPercentage'] }}%</div>
                    <div class="text-xs text-ink-faint mt-1">Field-verified campaigns</div>
                </div>
                <div class="bg-warm-surface px-6 py-6">
                    <div class="font-serif text-2xl font-semibold">{{ number_format($stats['bloodDonorsCount']) }}</div>
                    <div class="text-xs text-ink-faint mt-1">Registered blood donors</div>
                </div>
            </div>
        </div>
    @else
        <x-slot name="header">
            <h2 class="font-serif font-semibold text-xl text-ink leading-tight">
                {{ __('Browse Campaigns') }}
            </h2>
        </x-slot>
    @endif

    <div id="browse" class="py-10 scroll-mt-6">
        <div class="max-w-7xl mx-auto px-6 sm:px-10 lg:px-16 space-y-8">
            <form method="GET" action="{{ route('campaigns.index') }}" class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" name="category" value=""
                            class="px-4.5 py-2.5 rounded-full text-sm font-semibold transition {{ $category === '' ? 'bg-primary text-white' : 'bg-warm-surface border border-warm-border text-ink-muted hover:bg-warm-alt' }}">
                        All
                    </button>
                    @foreach ($categoryMeta as $key => $meta)
                        <button type="submit" name="category" value="{{ $key }}"
                                class="px-4.5 py-2.5 rounded-full text-sm font-semibold transition {{ $category === $key ? 'bg-primary text-white' : 'bg-warm-surface border border-warm-border text-ink-muted hover:bg-warm-alt' }}">
                            {{ $meta['label'] }}
                        </button>
                    @endforeach
                </div>

                <div class="flex items-center gap-2.5 px-4 py-2.5 bg-warm-surface border border-warm-border rounded-xl w-full sm:w-72">
                    <svg class="h-[17px] w-[17px] text-ink-faint shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by name or hospital&hellip;"
                           class="flex-1 border-0 p-0 bg-transparent text-sm text-ink placeholder:text-ink-faint focus:ring-0" />
                </div>
            </form>

            @if ($campaigns->isEmpty())
                <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-12 text-center text-ink-faint">
                    No campaigns found.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-7">
                    @foreach ($campaigns as $campaign)
                        @php($meta = $categoryMeta[$campaign->category] ?? $categoryMeta['treatment'])
                        <a href="{{ route('campaigns.show', $campaign) }}" wire:navigate
                           class="block bg-warm-surface border border-warm-border-soft rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition">
                            <div class="relative" style="height:168px; background:{{ $meta['gradient'] }};">
                                @if ($campaign->fieldVisitReports->isNotEmpty())
                                    <div class="absolute top-3.5 left-3.5 inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/90 text-primary-dark text-xs font-bold rounded-full">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                        VERIFIED
                                    </div>
                                @endif
                            </div>
                            <div class="p-5.5">
                                <div class="text-xs font-bold {{ $meta['text'] }} uppercase tracking-wide mb-2">
                                    {{ $meta['label'] }}
                                </div>
                                <h3 class="font-serif text-lg font-semibold leading-snug mb-1.5">{{ $campaign->title }}</h3>
                                <div class="flex items-center gap-1.5 text-[13px] text-ink-faint mb-4">
                                    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-6.5-4.35-8.5-8.15C1.8 9.4 3.2 6 6.5 5.6A5 5 0 0 1 12 8.4 5 5 0 0 1 17.5 5.6c3.3.4 4.7 3.8 3 7.25C18.5 16.65 12 21 12 21Z"/></svg>
                                    {{ $campaign->hospital_name }}
                                </div>
                                <div class="h-1.5 bg-warm-border-soft rounded-full overflow-hidden mb-2.5">
                                    <div class="h-full rounded-full {{ $meta['bar'] }}" style="width: {{ $campaign->progressPercentage() }}%"></div>
                                </div>
                                <div class="flex justify-between text-[13px]">
                                    <span class="font-bold text-ink">৳{{ number_format($campaign->raised_amount / 100) }} <span class="font-medium text-ink-faint">raised</span></span>
                                    <span class="text-ink-faint">of ৳{{ number_format($campaign->target_amount / 100) }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div>
                    {{ $campaigns->links() }}
                </div>
            @endif
        </div>
    </div>
</x-public-layout>
