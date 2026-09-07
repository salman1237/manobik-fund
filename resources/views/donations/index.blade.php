<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif font-semibold text-xl text-ink leading-tight">
            {{ __('My Donations') }}
        </h2>
    </x-slot>

    <div class="max-w-4xl flex flex-col gap-6">
        <div class="bg-primary-light border border-warm-border-soft rounded-2xl px-6 py-5 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-primary-dark">Humanity Badges</p>
                <p class="text-xs text-primary-dark/70">Earned automatically on completed donations</p>
            </div>
            <p class="font-serif text-2xl font-bold text-primary-dark">{{ $points }} pts</p>
        </div>

        @if ($donations->isEmpty())
            <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-10 text-center text-ink-faint">
                You haven't made any donations yet.
            </div>
        @else
            <div class="flex flex-col gap-3.5">
                @foreach ($donations as $donation)
                    <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-ink">
                                    {{ number_format($donation->amount / 100, 2) }} {{ $donation->currency }}
                                    @if ($donation->campaign)
                                        &middot; {{ $donation->campaign->title }}
                                    @endif
                                </p>
                                <p class="text-xs text-ink-faint mt-1">
                                    {{ $donation->created_at->format('M j, Y') }} &middot; {{ ucfirst($donation->status) }}
                                </p>
                            </div>
                        </div>

                        @if ($donation->refundRequests->isNotEmpty())
                            <p class="text-xs text-ink-faint mt-3">
                                Refund request: {{ ucfirst($donation->refundRequests->first()->status) }}
                            </p>
                        @else
                            <div class="mt-3">
                                <livewire:donations.request-refund :donation="$donation" :key="'refund-'.$donation->id" />
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
