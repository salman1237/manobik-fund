<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-warm-surface border-b border-warm-border-soft">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-10">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 shrink-0">
                    <x-application-logo class="h-6 w-6 text-accent" />
                    <span class="font-serif text-lg font-semibold text-ink">Manobik Fund</span>
                </a>

                <div class="hidden lg:flex lg:space-x-8">
                    <x-nav-link :href="route('campaigns.index')" :active="request()->routeIs('campaigns.*') || request()->routeIs('home')" wire:navigate>
                        {{ __('Browse Campaigns') }}
                    </x-nav-link>
                    <x-nav-link :href="route('blood.donors')" :active="request()->routeIs('blood.*')" wire:navigate>
                        {{ __('Blood Donors') }}
                    </x-nav-link>
                    <x-nav-link :href="route('ambulances.index')" :active="request()->routeIs('ambulances.*')" wire:navigate>
                        {{ __('Ambulances') }}
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden lg:flex lg:items-center lg:gap-3">
                @auth
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <button wire:click="logout" class="text-sm font-medium text-ink-muted hover:text-ink ms-2">
                        {{ __('Log Out') }}
                    </button>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="text-sm font-semibold text-ink-muted hover:text-ink px-3 py-2">
                        {{ __('Log In') }}
                    </a>
                    <a href="{{ route('register') }}" wire:navigate
                       class="inline-flex items-center px-5 py-2.5 bg-primary text-white text-sm font-semibold rounded-xl hover:bg-primary-dark transition">
                        {{ __('Sign Up') }}
                    </a>
                @endauth
            </div>

            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-lg text-ink-faint hover:text-ink hover:bg-warm-alt">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="open ? 'hidden' : 'inline-flex'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="open ? 'inline-flex' : 'hidden'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="open ? 'block' : 'hidden'" class="lg:hidden" @click="open = false">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('campaigns.index')" :active="request()->routeIs('campaigns.*')" wire:navigate>
                {{ __('Browse Campaigns') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('blood.donors')" :active="request()->routeIs('blood.*')" wire:navigate>
                {{ __('Blood Donors') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('ambulances.index')" :active="request()->routeIs('ambulances.*')" wire:navigate>
                {{ __('Ambulances') }}
            </x-responsive-nav-link>

            @auth
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>{{ __('Log Out') }}</x-responsive-nav-link>
                </button>
            @else
                <x-responsive-nav-link :href="route('login')" wire:navigate>{{ __('Log In') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('register')" wire:navigate>{{ __('Sign Up') }}</x-responsive-nav-link>
            @endauth
        </div>
    </div>
</nav>
