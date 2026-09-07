<div>
    @if ($canPost)
        <form wire:submit="post" class="mb-6 bg-white border border-gray-200 rounded-lg p-4">
            <label for="content" class="block text-sm font-medium text-gray-700">Post a patient update</label>
            <textarea id="content" wire:model="content" rows="3"
                      class="mt-1 block w-full rounded-md border-gray-300"
                      placeholder="Share today's progress..."></textarea>
            @error('content') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror

            <div class="flex justify-end mt-2">
                <x-primary-button type="submit">Post Update</x-primary-button>
            </div>
        </form>
    @endif

    <div class="space-y-4">
        @forelse ($campaign->updates as $update)
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-400">{{ $update->created_at->format('M j, Y - g:i A') }}</p>
                <p class="text-gray-800 mt-1 whitespace-pre-line">{{ $update->content }}</p>
            </div>
        @empty
            <p class="text-sm text-gray-500">No updates posted yet.</p>
        @endforelse
    </div>
</div>
