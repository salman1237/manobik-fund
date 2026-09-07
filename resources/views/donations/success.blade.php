<x-public-layout title="Donation Status">
    <div class="py-16">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 text-center">
            <div class="bg-white border border-gray-200 rounded-lg p-8">
                @if ($donation->isCompleted())
                    <h1 class="text-2xl font-bold text-emerald-700">Thank you, {{ $donation->donor_name }}!</h1>
                    <p class="mt-2 text-gray-600">
                        Your donation of {{ number_format($donation->amount / 100, 2) }} {{ $donation->currency }}
                        has been received. A receipt has been sent to {{ $donation->donor_email }}.
                    </p>
                @else
                    <h1 class="text-2xl font-bold text-amber-600">Confirming your payment...</h1>
                    <p class="mt-2 text-gray-600">
                        We're still confirming your payment with the gateway. If you completed the payment,
                        this will update shortly. You'll also receive an email receipt once confirmed.
                    </p>
                @endif

                @if ($donation->campaign)
                    <a href="{{ route('campaigns.show', $donation->campaign) }}" wire:navigate
                       class="inline-block mt-6 text-emerald-700 hover:underline">
                        Back to campaign
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-public-layout>
