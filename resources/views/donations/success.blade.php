<x-public-layout title="Donation Status">
    <div class="py-16">
        <div class="max-w-xl mx-auto px-6 text-center">
            <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-9">
                @if ($donation->isCompleted())
                    <div class="mx-auto mb-4 h-12 w-12 rounded-full bg-primary-light flex items-center justify-center">
                        <svg class="h-6 w-6 text-primary-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <h1 class="font-serif text-2xl font-semibold text-ink">Thank you, {{ $donation->donor_name }}!</h1>
                    <p class="mt-2.5 text-ink-muted">
                        Your donation of {{ number_format($donation->amount / 100, 2) }} {{ $donation->currency }}
                        has been received. A receipt has been sent to {{ $donation->donor_email }}.
                    </p>
                @else
                    <h1 class="font-serif text-2xl font-semibold text-warn-dark">Confirming your payment&hellip;</h1>
                    <p class="mt-2.5 text-ink-muted">
                        We're still confirming your payment with the gateway. If you completed the payment,
                        this will update shortly. You'll also receive an email receipt once confirmed.
                    </p>
                @endif

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
