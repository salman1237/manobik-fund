<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-lg">
    @if (session('status'))
        <p class="text-sm text-emerald-700 mb-3">{{ session('status') }}</p>
    @endif

    <form wire:submit="save" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Blood Group</label>
            <select wire:model="bloodGroup" class="mt-1 block w-full rounded-md border-gray-300">
                @foreach (\App\Models\BloodDonor::BLOOD_GROUPS as $group)
                    <option value="{{ $group }}">{{ $group }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700">Latitude</label>
                <input type="number" step="0.0000001" wire:model="latitude" class="mt-1 block w-full rounded-md border-gray-300" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Longitude</label>
                <input type="number" step="0.0000001" wire:model="longitude" class="mt-1 block w-full rounded-md border-gray-300" />
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" wire:model="isAvailable" class="rounded border-gray-300" />
            Available to donate
        </label>

        <x-primary-button type="submit">{{ $profile ? 'Update Profile' : 'Register as Donor' }}</x-primary-button>
    </form>
</div>
