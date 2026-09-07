<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif font-semibold text-xl text-ink leading-tight">
            {{ __('Edit Campaign') }}
        </h2>
    </x-slot>

    <livewire:campaigns.campaign-wizard :campaign="$campaign" :key="'wizard-'.$campaign->id" />
</x-app-layout>
