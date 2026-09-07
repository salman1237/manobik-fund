<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Donations') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 flex items-center justify-between">
                <div>
                    <p class="text-sm text-emerald-800 font-medium">Humanity Badges</p>
                    <p class="text-xs text-emerald-600">Earned automatically on completed donations</p>
                </div>
                <p class="text-2xl font-bold text-emerald-700">{{ $points }} pts</p>
            </div>

            @if ($donations->isEmpty())
                <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
                    You haven't made any donations yet.
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($donations as $donation)
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ number_format($donation->amount / 100, 2) }} {{ $donation->currency }}
                                        @if ($donation->campaign)
                                            &middot; {{ $donation->campaign->title }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $donation->created_at->format('M j, Y') }} &middot; {{ ucfirst($donation->status) }}
                                    </p>
                                </div>
                            </div>

                            @if ($donation->refundRequests->isNotEmpty())
                                <p class="text-xs text-gray-500 mt-2">
                                    Refund request: {{ ucfirst($donation->refundRequests->first()->status) }}
                                </p>
                            @else
                                <livewire:donations.request-refund :donation="$donation" :key="'refund-'.$donation->id" />
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
