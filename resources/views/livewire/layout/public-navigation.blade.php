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

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-10">
                <a href="{{ route('home') }}" wire:navigate class="text-lg font-semibold text-emerald-700">
                    Manobik Fund
                </a>

                <div class="hidden sm:flex sm:space-x-8">
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

            <div class="hidden sm:flex sm:items-center sm:gap-4">
                @auth
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <button wire:click="logout" class="text-sm text-gray-600 hover:text-gray-900">
                        {{ __('Log Out') }}
                    </button>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="text-sm text-gray-600 hover:text-gray-900">
                        {{ __('Log In') }}
                    </a>
                    <a href="{{ route('register') }}" wire:navigate
                       class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm rounded-md hover:bg-emerald-700">
                        {{ __('Sign Up') }}
                    </a>
                @endauth
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('campaigns.index')" :active="request()->routeIs('campaigns.*')" wire:navigate>
                {{ __('Browse Campaigns') }}
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
