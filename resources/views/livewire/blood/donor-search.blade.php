<div>
    <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-6 mb-8 grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
        <div>
            <x-input-label value="Blood Group" />
            <select wire:model.live="bloodGroup" class="mt-2 block w-full rounded-xl border-warm-border text-sm focus:border-primary focus:ring-primary">
                <option value="">Any</option>
                @foreach (\App\Models\BloodDonor::BLOOD_GROUPS as $group)
                    <option value="{{ $group }}">{{ $group }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label value="Your Latitude" />
            <x-text-input type="number" step="0.0000001" wire:model.live="latitude" class="mt-2 block w-full" />
        </div>
        <div>
            <x-input-label value="Your Longitude" />
            <x-text-input type="number" step="0.0000001" wire:model.live="longitude" class="mt-2 block w-full" />
        </div>
        <div>
            <x-input-label value="Radius (km)" />
            <x-text-input type="number" wire:model.live="radiusKm" class="mt-2 block w-full" />
        </div>
    </div>

    @if ($results->isEmpty())
        <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-10 text-center text-ink-faint">
            Enter a blood group or your location to search for donors.
        </div>
    @else
        <div class="flex flex-col gap-3">
            @foreach ($results as $donor)
                <div class="bg-warm-surface border border-warm-border-soft rounded-2xl px-5 py-4.5 flex items-center gap-4">
                    <div class="w-11 h-11 rounded-[11px] bg-danger-light text-danger flex items-center justify-center font-extrabold text-[13px] shrink-0">
                        {{ $donor->blood_group }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-ink text-[15px] truncate">{{ $donor->user->name }}</p>
                        @auth
                            <p class="text-xs text-ink-faint mt-0.5">{{ $donor->user->phone ?? $donor->user->email }}</p>
                        @else
                            <p class="text-xs text-ink-faint mt-0.5">Log in to view contact details</p>
                        @endauth
                    </div>
                    @if (isset($donor->distance_km))
                        <span class="text-xs font-semibold text-ink-faint shrink-0">{{ round($donor->distance_km, 1) }} km away</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
