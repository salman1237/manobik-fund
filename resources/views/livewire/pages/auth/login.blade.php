<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="font-serif text-3xl font-semibold text-ink mb-2">{{ __('Welcome back') }}</h1>
    <p class="text-sm text-ink-faint mb-9">{{ __('Log in to track your donations and campaigns.') }}</p>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{--
        Mobile browsers (Android Chrome's native Autofill Framework in
        particular) can fill saved credentials into these inputs without
        ever firing an 'input'/'change' event - which is what Livewire's
        wire:model relies on to sync. That leaves $wire's copy of
        form.email/form.password empty even though the field visually
        shows the autofilled value, so the server sees "field is
        required" on submit. x-on:submit.capture forces a read of the
        actual DOM value into the Livewire model right before submission,
        bypassing the event-driven sync entirely.
    --}}
    <form wire:submit="login" x-data class="flex flex-col gap-4"
          x-on:submit.capture="
              $wire.set('form.email', $refs.email.value, false);
              $wire.set('form.password', $refs.password.value, false);
          ">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" x-ref="email" id="email" class="block mt-2 w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-semibold text-primary hover:text-primary-dark" href="{{ route('password.request') }}" wire:navigate>
                        {{ __('Forgot?') }}
                    </a>
                @endif
            </div>

            <x-text-input wire:model="form.password" x-ref="password" id="password" class="block mt-2 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <label for="remember" class="flex items-center gap-2.5 text-sm text-ink-muted">
            <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-warm-border text-primary shadow-sm focus:ring-primary" name="remember">
            {{ __('Keep me signed in') }}
        </label>

        <x-primary-button class="w-full justify-center py-3.5 text-[15px]">
            {{ __('Log In') }}
        </x-primary-button>

        <p class="text-center text-sm text-ink-faint">
            {{ __("Don't have an account?") }}
            <a href="{{ route('register') }}" wire:navigate class="font-semibold text-primary hover:text-primary-dark">{{ __('Sign up') }}</a>
        </p>
    </form>
</div>
