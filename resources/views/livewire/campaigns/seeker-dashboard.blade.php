<div class="max-w-5xl mx-auto py-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">My Campaigns</h1>

        @if (auth()->user()->isDonationSeeker())
            <a href="{{ route('seeker.campaigns.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm rounded-md hover:bg-emerald-700">
                Start a Campaign
            </a>
        @else
            <span class="text-sm text-gray-500">Verify your email to start a campaign.</span>
        @endif
    </div>

    @if ($campaigns->isEmpty())
        <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
            You haven't started any campaigns yet.
        </div>
    @else
        <div class="space-y-3">
            @foreach ($campaigns as $campaign)
                <div class="bg-white border border-gray-200 rounded-lg p-4 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">{{ $campaign->title }}</p>
                        <p class="text-sm text-gray-500">
                            {{ ucfirst($campaign->category) }} &middot;
                            <span @class([
                                'font-medium',
                                'text-gray-500' => $campaign->status === 'draft',
                                'text-amber-600' => in_array($campaign->status, ['pending_verification', 'field_visit', 'executive_review']),
                                'text-emerald-600' => in_array($campaign->status, ['published', 'funded', 'completed']),
                                'text-red-600' => in_array($campaign->status, ['rejected', 'cancelled']),
                            ])>
                                {{ ucfirst(str_replace('_', ' ', $campaign->status)) }}
                            </span>
                        </p>
                        @if ($campaign->status === 'published' || $campaign->status === 'funded')
                            <p class="text-xs text-gray-500 mt-1">
                                Raised {{ number_format($campaign->raised_amount / 100, 2) }} / {{ number_format($campaign->target_amount / 100, 2) }} BDT
                                ({{ $campaign->progressPercentage() }}%)
                            </p>
                        @endif
                        @if ($campaign->status === 'rejected' && $campaign->rejection_reason)
                            <p class="text-xs text-red-500 mt-1">Reason: {{ $campaign->rejection_reason }}</p>
                        @endif
                    </div>

                    <div class="flex gap-2">
                        @if ($campaign->isEditableBySeeker())
                            <a href="{{ route('seeker.campaigns.edit', $campaign) }}" wire:navigate
                               class="text-sm text-emerald-700 hover:underline">Continue editing</a>
                        @else
                            <a href="{{ route('seeker.campaigns.show', $campaign) }}" wire:navigate
                               class="text-sm text-emerald-700 hover:underline">View</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
