<x-public-layout title="Find Blood Donors">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Find Blood Donors') }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <livewire:blood.donor-search />
        </div>
    </div>
</x-public-layout>
