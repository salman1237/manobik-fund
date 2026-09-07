<div class="bg-white border border-gray-200 rounded-lg p-6">
    <h3 class="font-semibold text-gray-900 mb-4">Make a Donation</h3>

    <form wire:submit="donate" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Payment Method</label>
            <select wire:model.live="gateway" class="mt-1 block w-full rounded-md border-gray-300">
                <option value="shurjopay">bKash / Nagad / Card (BDT)</option>
                <option value="stripe">Card (International - Stripe)</option>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700">Amount</label>
                <input type="number" step="0.01" wire:model="amount" class="mt-1 block w-full rounded-md border-gray-300" />
                @error('amount') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Currency</label>
                @if ($gateway === 'stripe')
                    <select wire:model="currency" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                        <option value="GBP">GBP</option>
                    </select>
                @else
                    <input type="text" value="BDT" disabled class="mt-1 block w-full rounded-md border-gray-200 bg-gray-100 text-gray-500" />
                @endif
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Your Name</label>
            <input type="text" wire:model="donorName" class="mt-1 block w-full rounded-md border-gray-300" />
            @error('donorName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Email (for receipt)</label>
            <input type="email" wire:model="donorEmail" class="mt-1 block w-full rounded-md border-gray-300" />
            @error('donorEmail') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" wire:model="isAnonymous" class="rounded border-gray-300" />
            Donate anonymously
        </label>

        <button type="submit"
                class="w-full inline-flex justify-center items-center px-4 py-2 bg-emerald-600 text-white text-sm rounded-md hover:bg-emerald-700"
                wire:loading.attr="disabled" wire:target="donate">
            <span wire:loading.remove wire:target="donate">Donate Now</span>
            <span wire:loading wire:target="donate">Processing...</span>
        </button>
    </form>
</div>
