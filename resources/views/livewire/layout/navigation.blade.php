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

    public function with(): array
    {
        return [
            'unreadCount' => auth()->user()?->unreadNotifications()->count() ?? 0,
            'humanityBadgePoints' => auth()->user()?->humanityBadgePoints() ?? 0,
        ];
    }
}; ?>

<div x-data="{ mobileOpen: false }">
    <!-- Mobile top bar -->
    <div class="lg:hidden flex items-center justify-between bg-warm-surface border-b border-warm-border-soft px-4 h-16">
        <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2">
            <x-application-logo class="h-6 w-6 text-accent" />
            <span class="font-serif text-lg font-semibold text-ink">Manobik Fund</span>
        </a>
        <button @click="mobileOpen = ! mobileOpen" class="p-2 rounded-lg text-ink-faint hover:bg-warm-alt">
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <!-- Sidebar -->
    <div :class="{'block': mobileOpen, 'hidden': ! mobileOpen}"
         class="hidden lg:flex lg:flex-col w-full lg:w-64 lg:shrink-0 bg-warm-surface border-b lg:border-b-0 lg:border-r border-warm-border-soft lg:h-screen lg:sticky lg:top-0 px-5 py-6">

        <a href="{{ route('home') }}" wire:navigate class="hidden lg:flex items-center gap-2 px-2 pb-7">
            <x-application-logo class="h-6 w-6 text-accent" />
            <span class="font-serif text-lg font-semibold text-ink">Manobik Fund</span>
        </a>

        <nav class="flex flex-col gap-1">
            <a href="{{ route('dashboard') }}" wire:navigate
               @class([
                   'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition',
                   'bg-primary-light text-primary-dark' => request()->routeIs('dashboard'),
                   'text-ink-muted hover:bg-warm-alt hover:text-ink' => ! request()->routeIs('dashboard'),
               ])>
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                Dashboard
            </a>
            <a href="{{ route('seeker.campaigns.index') }}" wire:navigate
               @class([
                   'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition',
                   'bg-primary-light text-primary-dark' => request()->routeIs('seeker.campaigns.*'),
                   'text-ink-muted hover:bg-warm-alt hover:text-ink' => ! request()->routeIs('seeker.campaigns.*'),
               ])>
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7.5-4.6-10-9.2C.4 8.1 2 4.5 5.6 4A5.4 5.4 0 0 1 12 7.4 5.4 5.4 0 0 1 18.4 4C22 4.5 23.6 8.1 22 11.8 19.5 16.4 12 21 12 21Z"/></svg>
                My Campaigns
            </a>
            <a href="{{ route('donations.index') }}" wire:navigate
               @class([
                   'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition',
                   'bg-primary-light text-primary-dark' => request()->routeIs('donations.index'),
                   'text-ink-muted hover:bg-warm-alt hover:text-ink' => ! request()->routeIs('donations.index'),
               ])>
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                My Donations
            </a>
            <a href="{{ route('notifications.index') }}" wire:navigate
               @class([
                   'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition',
                   'bg-primary-light text-primary-dark' => request()->routeIs('notifications.*'),
                   'text-ink-muted hover:bg-warm-alt hover:text-ink' => ! request()->routeIs('notifications.*'),
               ])>
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                <span class="flex-1">Notifications</span>
                @if ($unreadCount > 0)
                    <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 bg-accent-dark text-white text-[10px] font-bold rounded-full">{{ $unreadCount }}</span>
                @endif
            </a>
            <a href="{{ route('profile') }}" wire:navigate
               @class([
                   'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition',
                   'bg-primary-light text-primary-dark' => request()->routeIs('profile'),
                   'text-ink-muted hover:bg-warm-alt hover:text-ink' => ! request()->routeIs('profile'),
               ])>
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                Profile
            </a>
        </nav>

        <div class="mt-auto pt-6 flex flex-col gap-3">
            <div class="px-4 py-3.5 bg-warm-alt rounded-2xl">
                <div class="text-[11px] font-bold text-ink-faint uppercase tracking-wide mb-1">Humanity Badges</div>
                <div class="font-serif text-xl font-bold text-accent-dark">{{ $humanityBadgePoints }} pts</div>
            </div>

            <div class="flex items-center justify-between px-2">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-ink truncate" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                    <div class="text-xs text-ink-faint truncate">{{ auth()->user()->email }}</div>
                </div>
                <button wire:click="logout" title="Log out" class="p-2 rounded-lg text-ink-faint hover:bg-warm-alt hover:text-ink shrink-0">
                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>
