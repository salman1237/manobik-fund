<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif font-semibold text-xl text-ink leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="flex flex-col gap-8">
        <div>
            <h1 class="font-serif text-2xl font-semibold text-ink mb-1.5">{{ __('Welcome back, :name.', ['name' => auth()->user()->name]) }}</h1>
            <p class="text-sm text-ink-faint">Here&rsquo;s a quick look at your activity on Manobik Fund.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="bg-warm-surface border border-warm-border-soft rounded-2xl px-6 py-5.5">
                <div class="text-[12.5px] font-semibold text-ink-faint mb-2">{{ __('Total Raised') }}</div>
                <div class="font-serif text-2xl font-bold text-ink">৳{{ number_format($totalRaised / 100) }}</div>
            </div>
            <div class="bg-warm-surface border border-warm-border-soft rounded-2xl px-6 py-5.5">
                <div class="text-[12.5px] font-semibold text-ink-faint mb-2">{{ __('My Campaigns') }}</div>
                <div class="font-serif text-2xl font-bold text-ink">{{ $campaignsCount }}</div>
            </div>
            <div class="bg-warm-surface border border-warm-border-soft rounded-2xl px-6 py-5.5">
                <div class="text-[12.5px] font-semibold text-ink-faint mb-2">{{ __('Donations Made') }}</div>
                <div class="font-serif text-2xl font-bold text-ink">{{ $donationsCount }}</div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('seeker.campaigns.index') }}" wire:navigate
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white text-sm font-semibold rounded-xl hover:bg-primary-dark transition">
                {{ __('My Campaigns') }}
            </a>
            <a href="{{ route('donations.index') }}" wire:navigate
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-warm-surface border border-warm-border text-ink text-sm font-semibold rounded-xl hover:bg-warm-alt transition">
                {{ __('My Donations') }}
            </a>
            <a href="{{ route('seeker.campaigns.create') }}" wire:navigate
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-warm-surface border border-warm-border text-ink text-sm font-semibold rounded-xl hover:bg-warm-alt transition">
                {{ __('Start a Campaign') }}
            </a>
        </div>
    </div>
</x-app-layout>
