<div class="sticky top-6 bg-warm-surface border border-warm-border-soft rounded-[20px] p-7 shadow-[0_12px_32px_-12px_oklch(23%_0.02_55_/_12%)]">
    <div class="flex justify-between items-baseline mb-2">
        <span class="font-serif text-[26px] font-bold text-ink">৳{{ number_format($campaign->raised_amount / 100) }}</span>
        <span class="text-[13px] text-ink-faint">raised of ৳{{ number_format($campaign->target_amount / 100) }}</span>
    </div>
    <div class="h-2 bg-warm-border-soft rounded-full overflow-hidden mb-3.5">
        <div class="h-full bg-primary rounded-full" style="width: {{ $campaign->progressPercentage() }}%"></div>
    </div>
    <div class="flex justify-between text-[13px] text-ink-muted mb-6">
        <span><strong class="text-ink">{{ $campaign->progressPercentage() }}%</strong> funded</span>
        @if ($campaign->deadline)
            <span>{{ now()->diffInDays($campaign->deadline, false) > 0 ? now()->diffInDays($campaign->deadline) . ' days left' : 'Deadline passed' }}</span>
        @endif
    </div>

    <h3 class="font-serif text-lg font-semibold text-ink mb-4">Make a Donation</h3>

    <form wire:submit="donate" class="flex flex-col gap-4">
        <div>
            <x-input-label value="Payment Method" />
            <select wire:model.live="gateway" class="mt-2 block w-full rounded-xl border-warm-border text-sm focus:border-primary focus:ring-primary">
                <option value="shurjopay">bKash / Nagad / Card (BDT)</option>
                <option value="stripe">Card (International - Stripe)</option>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <x-input-label value="Amount" />
                <x-text-input type="number" step="0.01" wire:model="amount" class="mt-2 block w-full" />
                @error('amount') <p class="text-xs text-danger mt-1.5">{{ $message }}</p> @enderror
            </div>
            <div>
                <x-input-label value="Currency" />
                @if ($gateway === 'stripe')
                    <select wire:model="currency" class="mt-2 block w-full rounded-xl border-warm-border text-sm focus:border-primary focus:ring-primary">
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                        <option value="GBP">GBP</option>
                    </select>
                @else
                    <input type="text" value="BDT" disabled class="mt-2 block w-full rounded-xl border-warm-border-soft bg-warm-alt text-ink-faint text-sm" />
                @endif
            </div>
        </div>

        <div>
            <x-input-label value="Your Name" />
            <x-text-input type="text" wire:model="donorName" class="mt-2 block w-full" />
            @error('donorName') <p class="text-xs text-danger mt-1.5">{{ $message }}</p> @enderror
        </div>

        <div>
            <x-input-label value="Email (for receipt)" />
            <x-text-input type="email" wire:model="donorEmail" class="mt-2 block w-full" />
            @error('donorEmail') <p class="text-xs text-danger mt-1.5">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2.5 text-sm text-ink-muted">
            <input type="checkbox" wire:model="isAnonymous" class="rounded border-warm-border text-primary focus:ring-primary" />
            Donate anonymously
        </label>

        <button type="submit"
                class="w-full inline-flex justify-center items-center py-4 bg-accent-dark text-white text-base font-bold rounded-[13px] shadow-[0_8px_20px_-6px_oklch(45%_0.15_38_/_45%)] hover:opacity-95 transition"
                wire:loading.attr="disabled" wire:target="donate">
            <span wire:loading.remove wire:target="donate">Donate Now</span>
            <span wire:loading wire:target="donate">Processing&hellip;</span>
        </button>

        <div class="flex items-center justify-center gap-2 text-[12.5px] text-ink-faint">
            <svg class="h-[13px] w-[13px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            Secured checkout via Stripe &amp; ShurjoPay
        </div>
    </form>
</div>
