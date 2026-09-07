<div>
    @if ($canRequest)
        @if (! $showForm)
            <button wire:click="$set('showForm', true)" class="text-xs font-semibold text-danger hover:underline">
                Request Refund
            </button>
        @else
            <form wire:submit="submit" class="mt-2 flex flex-col gap-2">
                <textarea wire:model="reason" rows="2" placeholder="Why are you requesting a refund?"
                          class="block w-full text-sm rounded-xl border-warm-border focus:border-primary focus:ring-primary"></textarea>
                @error('reason') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                <div class="flex gap-2">
                    <button type="submit" class="text-xs font-semibold px-3.5 py-1.5 bg-danger text-white rounded-lg hover:opacity-90">Submit Request</button>
                    <button type="button" wire:click="$set('showForm', false)" class="text-xs font-semibold text-ink-faint hover:text-ink">Cancel</button>
                </div>
            </form>
        @endif
    @endif
</div>
