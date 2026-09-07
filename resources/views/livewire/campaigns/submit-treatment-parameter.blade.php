<div>
    @if ($canSubmit)
        <form wire:submit="submit" class="bg-white border border-gray-200 rounded-lg p-4 space-y-3 mb-6">
            <h4 class="font-medium text-gray-900">Log a Treatment Update</h4>
            <p class="text-xs text-gray-500">New entries appear publicly only after admin verification.</p>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Type</label>
                    <select wire:model="parameterType" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="milestone">Milestone</option>
                        <option value="pain_scale">Pain Scale (1-10)</option>
                        <option value="wbc_count">WBC Count</option>
                        <option value="platelet">Platelet Count</option>
                        <option value="creatinine">Creatinine</option>
                        <option value="bilirubin">Bilirubin</option>
                        <option value="hospital_days">Hospital Days</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="date" wire:model="recordedAt" class="mt-1 block w-full rounded-md border-gray-300" />
                    @error('recordedAt') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Label</label>
                <input type="text" wire:model="label" placeholder="e.g. Chemo Cycle 2 of 6"
                       class="mt-1 block w-full rounded-md border-gray-300" />
                @error('label') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Value</label>
                    <input type="text" wire:model="value" class="mt-1 block w-full rounded-md border-gray-300" />
                    @error('value') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Unit (optional)</label>
                    <input type="text" wire:model="unit" placeholder="e.g. cells/mcL" class="mt-1 block w-full rounded-md border-gray-300" />
                </div>
            </div>

            <div class="flex justify-end">
                <x-primary-button type="submit">Submit</x-primary-button>
            </div>
        </form>
    @endif
</div>
