<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif font-semibold text-xl text-ink leading-tight">
            {{ $campaign->title }}
        </h2>
    </x-slot>

    <div class="max-w-4xl flex flex-col gap-6">
        <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-6">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="text-sm font-semibold text-ink-faint">
                    {{ ucfirst($campaign->category) }} &middot; {{ ucfirst(str_replace('_', ' ', $campaign->status)) }}
                </span>
                @if ($campaign->isEditableBySeeker())
                    <a href="{{ route('seeker.campaigns.edit', $campaign) }}" wire:navigate class="text-sm font-semibold text-primary hover:text-primary-dark shrink-0">Edit</a>
                @endif
            </div>

            @if ($campaign->isPublic())
                <div class="mt-5">
                    <div class="flex justify-between text-sm text-ink-muted mb-1.5">
                        <span>Raised: ৳{{ number_format($campaign->raised_amount / 100) }}</span>
                        <span>Target: ৳{{ number_format($campaign->target_amount / 100) }}</span>
                    </div>
                    <div class="h-2 bg-warm-border-soft rounded-full overflow-hidden">
                        <div class="h-full bg-primary rounded-full" style="width: {{ $campaign->progressPercentage() }}%"></div>
                    </div>
                </div>
            @endif

            <p class="mt-5 text-[15px] leading-relaxed text-ink-muted whitespace-pre-line">{{ $campaign->description }}</p>
        </div>

        @if ($campaign->isPublic())
            @if ($campaign->needsMedicalTracking())
                <div>
                    <h3 class="font-serif text-lg font-semibold text-ink mb-3">Treatment Tracking</h3>
                    <livewire:campaigns.submit-treatment-parameter :campaign="$campaign" :key="'parameters-'.$campaign->id" />
                </div>
            @endif

            <div>
                <h3 class="font-serif text-lg font-semibold text-ink mb-3">{{ $campaign->needsMedicalTracking() ? 'Patient Updates' : 'Updates' }}</h3>
                <livewire:campaigns.post-campaign-update :campaign="$campaign" :key="'updates-'.$campaign->id" />
            </div>
        @endif
    </div>
</x-app-layout>
