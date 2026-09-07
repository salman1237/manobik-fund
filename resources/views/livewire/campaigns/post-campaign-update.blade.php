<div>
    @if ($canPost)
        <form wire:submit="post" class="mb-6 bg-warm-surface border border-warm-border-soft rounded-2xl p-5">
            <label for="content" class="block text-sm font-semibold text-ink-muted">Post a patient update</label>
            <textarea id="content" wire:model="content" rows="3"
                      class="mt-2 block w-full rounded-xl border-warm-border text-sm focus:border-primary focus:ring-primary"
                      placeholder="Share today's progress..."></textarea>
            @error('content') <p class="text-sm text-danger mt-1.5">{{ $message }}</p> @enderror

            <div class="flex justify-end mt-3">
                <x-primary-button type="submit">Post Update</x-primary-button>
            </div>
        </form>
    @endif

    <div class="flex flex-col gap-3.5">
        @forelse ($campaign->updates as $update)
            <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-4.5">
                <p class="text-[12.5px] text-ink-faint">{{ $update->created_at->format('M j, Y - g:i A') }}</p>
                <p class="text-ink-muted mt-1.5 whitespace-pre-line">{{ $update->content }}</p>
            </div>
        @empty
            <p class="text-sm text-ink-faint">No updates posted yet.</p>
        @endforelse
    </div>
</div>
