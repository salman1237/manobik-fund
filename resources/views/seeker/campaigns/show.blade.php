<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $campaign->title }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-500">
                        {{ ucfirst($campaign->category) }} &middot; {{ ucfirst(str_replace('_', ' ', $campaign->status)) }}
                    </span>
                    @if ($campaign->isEditableBySeeker())
                        <a href="{{ route('seeker.campaigns.edit', $campaign) }}" wire:navigate class="text-sm text-emerald-700 hover:underline">Edit</a>
                    @endif
                </div>

                @if ($campaign->isPublic())
                    <div class="mt-4">
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>Raised: {{ number_format($campaign->raised_amount / 100, 2) }} BDT</span>
                            <span>Target: {{ number_format($campaign->target_amount / 100, 2) }} BDT</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-emerald-600 h-2.5 rounded-full" style="width: {{ $campaign->progressPercentage() }}%"></div>
                        </div>
                    </div>
                @endif

                <p class="mt-4 text-gray-700 whitespace-pre-line">{{ $campaign->description }}</p>
            </div>

            @if ($campaign->isPublic())
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Treatment Tracking</h3>
                    <livewire:campaigns.submit-treatment-parameter :campaign="$campaign" :key="'parameters-'.$campaign->id" />
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Patient Updates</h3>
                    <livewire:campaigns.post-campaign-update :campaign="$campaign" :key="'updates-'.$campaign->id" />
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
