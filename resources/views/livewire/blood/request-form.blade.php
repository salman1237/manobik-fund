<div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-6 max-w-lg">
    @if ($submitted)
        <p class="text-primary-dark font-semibold">Your blood request has been posted. Nearby donors and volunteers will be able to see it.</p>
    @else
        <form wire:submit="submit" class="flex flex-col gap-4">
            <div>
                <x-input-label value="Your Name" />
                <x-text-input type="text" wire:model="requesterName" class="mt-2 block w-full" />
                @error('requesterName') <p class="text-xs text-danger mt-1.5">{{ $message }}</p> @enderror
            </div>

            <div>
                <x-input-label value="Phone" />
                <x-text-input type="text" wire:model="requesterPhone" class="mt-2 block w-full" />
                @error('requesterPhone') <p class="text-xs text-danger mt-1.5">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3.5">
                <div>
                    <x-input-label value="Blood Group Needed" />
                    <select wire:model="bloodGroup" class="mt-2 block w-full rounded-xl border-warm-border text-sm focus:border-primary focus:ring-primary">
                        @foreach (\App\Models\BloodDonor::BLOOD_GROUPS as $group)
                            <option value="{{ $group }}">{{ $group }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label value="Urgency" />
                    <select wire:model="urgency" class="mt-2 block w-full rounded-xl border-warm-border text-sm focus:border-primary focus:ring-primary">
                        <option value="normal">Normal</option>
                        <option value="urgent">Urgent</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>

            <div>
                <x-input-label value="Hospital (optional)" />
                <x-text-input type="text" wire:model="hospitalName" class="mt-2 block w-full" />
            </div>

            <div>
                <x-primary-button type="submit">Post Request</x-primary-button>
            </div>
        </form>
    @endif
</div>
