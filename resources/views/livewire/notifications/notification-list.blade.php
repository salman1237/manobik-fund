<div>
    <div class="flex justify-end mb-4">
        <button wire:click="markAllAsRead" class="text-sm text-emerald-700 hover:underline">
            Mark all as read
        </button>
    </div>

    @forelse ($notifications as $notification)
        <div @class([
                'bg-white border rounded-lg p-4 mb-3 flex items-start justify-between',
                'border-emerald-300' => is_null($notification->read_at),
                'border-gray-200' => ! is_null($notification->read_at),
            ])>
            <div>
                <p class="text-gray-800">{{ $notification->data['message'] ?? 'Notification' }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
            </div>
            @if (is_null($notification->read_at))
                <button wire:click="markAsRead('{{ $notification->id }}')" class="text-xs text-gray-500 hover:underline">
                    Mark read
                </button>
            @endif
        </div>
    @empty
        <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
            No notifications yet.
        </div>
    @endforelse

    {{ $notifications->links() }}
</div>
