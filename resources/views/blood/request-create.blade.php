<x-public-layout title="Post a Blood Request">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Post a Blood Request') }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <livewire:blood.request-form />
        </div>
    </div>
</x-public-layout>
