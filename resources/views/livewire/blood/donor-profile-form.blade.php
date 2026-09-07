<div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-6 max-w-lg">
    @if (session('status'))
        <p class="text-sm font-semibold text-primary-dark mb-4">{{ session('status') }}</p>
    @endif

    <form wire:submit="save" class="flex flex-col gap-4">
        <div>
            <x-input-label value="Blood Group" />
            <select wire:model="bloodGroup" class="mt-2 block w-full rounded-xl border-warm-border text-sm focus:border-primary focus:ring-primary">
                @foreach (\App\Models\BloodDonor::BLOOD_GROUPS as $group)
                    <option value="{{ $group }}">{{ $group }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-3.5">
            <div>
                <x-input-label value="Latitude" />
                <x-text-input type="number" step="0.0000001" wire:model="latitude" class="mt-2 block w-full" />
            </div>
            <div>
                <x-input-label value="Longitude" />
                <x-text-input type="number" step="0.0000001" wire:model="longitude" class="mt-2 block w-full" />
            </div>
        </div>

        <label class="flex items-center gap-2.5 text-sm text-ink-muted">
            <input type="checkbox" wire:model="isAvailable" class="rounded border-warm-border text-primary focus:ring-primary" />
            Available to donate
        </label>

        <div>
            <x-primary-button type="submit">{{ $profile ? 'Update Profile' : 'Register as Donor' }}</x-primary-button>
        </div>
    </form>
</div>
