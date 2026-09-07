<div>
    @if ($canRequest)
        @if (! $showForm)
            <button wire:click="$set('showForm', true)" class="text-xs text-red-600 hover:underline">
                Request Refund
            </button>
        @else
            <form wire:submit="submit" class="mt-2 space-y-2">
                <textarea wire:model="reason" rows="2" placeholder="Why are you requesting a refund?"
                          class="block w-full text-sm rounded-md border-gray-300"></textarea>
                @error('reason') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <div class="flex gap-2">
                    <button type="submit" class="text-xs px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700">Submit Request</button>
                    <button type="button" wire:click="$set('showForm', false)" class="text-xs text-gray-500">Cancel</button>
                </div>
            </form>
        @endif
    @endif
</div>
