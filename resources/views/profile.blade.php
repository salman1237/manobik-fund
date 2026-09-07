<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif font-semibold text-xl text-ink leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="p-6 bg-primary-light border border-warm-border-soft rounded-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 max-w-xl">
            <div>
                <p class="text-sm font-semibold text-primary-dark">{{ __('Humanity Badges') }}</p>
                <p class="text-xs text-primary-dark/70">{{ __('Earned automatically on completed donations') }}</p>
            </div>
            <p class="font-serif text-2xl font-bold text-primary-dark shrink-0">{{ auth()->user()->humanityBadgePoints() }} pts</p>
        </div>

        <div class="p-6 sm:p-8 bg-warm-surface border border-warm-border-soft rounded-2xl">
            <div class="max-w-xl">
                <livewire:profile.update-profile-information-form />
            </div>
        </div>

        <div class="p-6 sm:p-8 bg-warm-surface border border-warm-border-soft rounded-2xl">
            <div class="max-w-xl">
                <livewire:profile.update-password-form />
            </div>
        </div>

        <div class="p-6 sm:p-8 bg-warm-surface border border-warm-border-soft rounded-2xl">
            <div class="max-w-xl">
                <livewire:profile.delete-user-form />
            </div>
        </div>
    </div>
</x-app-layout>
