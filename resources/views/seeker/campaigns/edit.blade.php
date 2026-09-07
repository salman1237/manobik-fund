<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Campaign') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <livewire:campaigns.campaign-wizard :campaign="$campaign" :key="'wizard-'.$campaign->id" />
        </div>
    </div>
</x-app-layout>
