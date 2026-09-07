<div class="bg-white border border-gray-200 rounded-lg p-6 max-w-lg">
    @if ($submitted)
        <p class="text-emerald-700 font-medium">Your blood request has been posted. Nearby donors and volunteers will be able to see it.</p>
    @else
        <form wire:submit="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Your Name</label>
                <input type="text" wire:model="requesterName" class="mt-1 block w-full rounded-md border-gray-300" />
                @error('requesterName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Phone</label>
                <input type="text" wire:model="requesterPhone" class="mt-1 block w-full rounded-md border-gray-300" />
                @error('requesterPhone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Blood Group Needed</label>
                    <select wire:model="bloodGroup" class="mt-1 block w-full rounded-md border-gray-300">
                        @foreach (\App\Models\BloodDonor::BLOOD_GROUPS as $group)
                            <option value="{{ $group }}">{{ $group }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Urgency</label>
                    <select wire:model="urgency" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="normal">Normal</option>
                        <option value="urgent">Urgent</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Hospital (optional)</label>
                <input type="text" wire:model="hospitalName" class="mt-1 block w-full rounded-md border-gray-300" />
            </div>

            <x-primary-button type="submit">Post Request</x-primary-button>
        </form>
    @endif
</div>
