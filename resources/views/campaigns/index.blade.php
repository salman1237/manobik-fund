<x-public-layout title="Browse Campaigns">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Browse Campaigns') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" action="{{ route('campaigns.index') }}" class="bg-white border border-gray-200 rounded-lg p-4 flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                    <input type="text" id="search" name="search" value="{{ $search }}"
                           placeholder="Search by title or hospital..."
                           class="mt-1 block w-full rounded-md border-gray-300" />
                </div>
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                    <select id="category" name="category" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">All</option>
                        <option value="treatment" @selected($category === 'treatment')>Treatment Fund</option>
                        <option value="emergency" @selected($category === 'emergency')>Emergency Response</option>
                        <option value="camp" @selected($category === 'camp')>Medical Camp</option>
                        <option value="education" @selected($category === 'education')>Education & Training</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-md hover:bg-emerald-700">
                    Filter
                </button>
            </form>

            @if ($campaigns->isEmpty())
                <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
                    No campaigns found.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($campaigns as $campaign)
                        <a href="{{ route('campaigns.show', $campaign) }}" wire:navigate
                           class="block bg-white border border-gray-200 rounded-lg p-5 hover:shadow-md transition">
                            <span class="text-xs font-medium uppercase tracking-wide text-emerald-700">
                                {{ ucfirst($campaign->category) }}
                            </span>
                            <h3 class="mt-1 font-semibold text-gray-900">{{ $campaign->title }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $campaign->hospital_name }}</p>

                            <div class="mt-4">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-emerald-600 h-2 rounded-full" style="width: {{ $campaign->progressPercentage() }}%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>{{ number_format($campaign->raised_amount / 100) }} raised</span>
                                    <span>{{ $campaign->progressPercentage() }}%</span>
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
