<div>
    @if ($canSubmit)
        <form wire:submit="submit" class="bg-warm-surface border border-warm-border-soft rounded-2xl p-5 flex flex-col gap-3.5 mb-6">
            <h4 class="font-serif font-semibold text-ink">Log a Treatment Update</h4>
            <p class="text-xs text-ink-faint">New entries appear publicly only after admin verification.</p>

            <div class="grid grid-cols-2 gap-3.5">
                <div>
                    <x-input-label value="Type" />
                    <select wire:model="parameterType" class="mt-2 block w-full rounded-xl border-warm-border text-sm focus:border-primary focus:ring-primary">
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
                    <x-input-label value="Date" />
                    <x-text-input type="date" wire:model="recordedAt" class="mt-2 block w-full" />
                    @error('recordedAt') <p class="text-xs text-danger mt-1.5">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <x-input-label value="Label" />
                <x-text-input type="text" wire:model="label" placeholder="e.g. Chemo Cycle 2 of 6" class="mt-2 block w-full" />
                @error('label') <p class="text-xs text-danger mt-1.5">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3.5">
                <div>
                    <x-input-label value="Value" />
                    <x-text-input type="text" wire:model="value" class="mt-2 block w-full" />
                    @error('value') <p class="text-xs text-danger mt-1.5">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-input-label value="Unit (optional)" />
                    <x-text-input type="text" wire:model="unit" placeholder="e.g. cells/mcL" class="mt-2 block w-full" />
                </div>
            </div>

            <div class="flex justify-end">
                <x-primary-button type="submit">Submit</x-primary-button>
            </div>
        </form>
    @endif
</div>
