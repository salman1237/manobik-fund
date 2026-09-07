<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif font-semibold text-xl text-ink leading-tight">
            {{ __('Start a Campaign') }}
        </h2>
    </x-slot>

    <livewire:campaigns.campaign-wizard />
</x-app-layout>
