<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="font-serif text-3xl font-semibold text-ink mb-2">{{ __('Create your account') }}</h1>
    <p class="text-sm text-ink-faint mb-9">{{ __('Join Manobik Fund to donate or start a campaign.') }}</p>

    {{--
        See login.blade.php for why: mobile browser autofill (Android
        Chrome's native Autofill Framework especially) can fill these
        inputs without firing an event Livewire's wire:model listens for,
        leaving $wire's copy empty even though the field looks filled.
        This forces a read of the actual DOM values right before submit.
    --}}
    <form wire:submit="register" x-data class="flex flex-col gap-4"
          x-on:submit.capture="
              $wire.set('name', $refs.name.value, false);
              $wire.set('email', $refs.email.value, false);
              $wire.set('password', $refs.password.value, false);
              $wire.set('password_confirmation', $refs.password_confirmation.value, false);
          ">
        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" x-ref="name" id="name" class="block mt-2 w-full" type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" x-ref="email" id="email" class="block mt-2 w-full" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="password" x-ref="password" id="password" class="block mt-2 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input wire:model="password_confirmation" x-ref="password_confirmation" id="password_confirmation" class="block mt-2 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center py-3.5 text-[15px] mt-2">
            {{ __('Create Account') }}
        </x-primary-button>

        <p class="text-center text-sm text-ink-faint">
            {{ __('Already registered?') }}
            <a href="{{ route('login') }}" wire:navigate class="font-semibold text-primary hover:text-primary-dark">{{ __('Log in') }}</a>
        </p>
    </form>
</div>
