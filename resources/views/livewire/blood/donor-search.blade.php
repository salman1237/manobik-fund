<div>
    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700">Blood Group</label>
            <select wire:model.live="bloodGroup" class="mt-1 block w-full rounded-md border-gray-300">
                <option value="">Any</option>
                @foreach (\App\Models\BloodDonor::BLOOD_GROUPS as $group)
                    <option value="{{ $group }}">{{ $group }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Your Latitude</label>
            <input type="number" step="0.0000001" wire:model.live="latitude" class="mt-1 block w-full rounded-md border-gray-300" />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Your Longitude</label>
            <input type="number" step="0.0000001" wire:model.live="longitude" class="mt-1 block w-full rounded-md border-gray-300" />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Radius (km)</label>
            <input type="number" wire:model.live="radiusKm" class="mt-1 block w-full rounded-md border-gray-300" />
        </div>
    </div>

    @if ($results->isEmpty())
        <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
            Enter a blood group or your location to search for donors.
        </div>
    @else
        <div class="space-y-3">
            @foreach ($results as $donor)
                <div class="bg-white border border-gray-200 rounded-lg p-4 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">{{ $donor->user->name }} &middot; {{ $donor->blood_group }}</p>
                        @auth
                            <p class="text-xs text-gray-500">{{ $donor->user->phone ?? $donor->user->email }}</p>
                        @else
                            <p class="text-xs text-gray-400">Log in to view contact details</p>
                        @endauth
                    </div>
                    @if (isset($donor->distance_km))
                        <span class="text-xs text-gray-500">{{ round($donor->distance_km, 1) }} km away</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
