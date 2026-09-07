<x-public-layout title="Donation Cancelled">
    <div class="py-16">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 text-center">
            <div class="bg-white border border-gray-200 rounded-lg p-8">
                <h1 class="text-2xl font-bold text-gray-800">Donation Cancelled</h1>
                <p class="mt-2 text-gray-600">No payment was made. You can try again anytime.</p>

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
