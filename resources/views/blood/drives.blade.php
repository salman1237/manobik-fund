<x-public-layout title="Blood Donation Drives">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Upcoming Blood Donation Drives') }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-3">
            @forelse ($drives as $drive)
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <p class="font-medium text-gray-900">{{ $drive->title }}</p>
                    <p class="text-sm text-gray-500">{{ $drive->location }} &middot; {{ $drive->scheduled_at->format('M j, Y g:i A') }}</p>
                    @if ($drive->description)
                        <p class="text-sm text-gray-700 mt-2">{{ $drive->description }}</p>
                    @endif
                </div>
            @empty
                <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
                    No upcoming drives scheduled.
                </div>
            @endforelse
        </div>
    </div>
</x-public-layout>
