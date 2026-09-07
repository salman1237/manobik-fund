<x-public-layout title="Donation Cancelled">
    <div class="py-16">
        <div class="max-w-xl mx-auto px-6 text-center">
            <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-9">
                <h1 class="font-serif text-2xl font-semibold text-ink">Donation Cancelled</h1>
                <p class="mt-2.5 text-ink-muted">No payment was made. You can try again anytime.</p>

                @if ($donation->campaign)
                    <a href="{{ route('campaigns.show', $donation->campaign) }}" wire:navigate
                       class="inline-block mt-7 text-sm font-semibold text-primary hover:text-primary-dark">
                        &larr; Back to campaign
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-public-layout>
