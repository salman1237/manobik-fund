<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="font-serif text-2xl font-semibold text-ink">Notifications</h1>
        <button wire:click="markAllAsRead" class="text-[13.5px] font-bold text-primary hover:text-primary-dark">
            Mark all as read
        </button>
    </div>

    <div class="flex flex-col gap-2.5">
        @forelse ($notifications as $notification)
            @php($isUnread = is_null($notification->read_at))
            <div @class([
                    'flex gap-4 px-5 py-4.5 bg-warm-surface rounded-2xl transition',
                    'border border-primary-light border-l-[3px] border-l-primary' => $isUnread,
                    'border border-warm-border-soft opacity-70' => ! $isUnread,
                ])>
                <div @class([
                        'w-10 h-10 rounded-[11px] flex items-center justify-center shrink-0',
                        'bg-primary-light text-primary-dark' => $isUnread,
                        'bg-warm-alt text-ink-faint' => ! $isUnread,
                    ])>
                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[14.5px] leading-relaxed text-ink-muted">{{ $notification->data['message'] ?? 'Notification' }}</p>
                    <div class="flex items-center gap-3 mt-2">
                        <p class="text-xs text-ink-faint">{{ $notification->created_at->diffForHumans() }}</p>
                        @if ($isUnread)
                            <button wire:click="markAsRead('{{ $notification->id }}')" class="text-xs font-semibold text-primary hover:text-primary-dark">
                                Mark read
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-10 text-center text-ink-faint">
                No notifications yet.
            </div>
        @endforelse
    </div>

    <div class="mt-5">
        {{ $notifications->links() }}
    </div>
</div>
