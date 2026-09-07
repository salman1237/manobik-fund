<x-public-layout title="Blood Donation Drives">
    <div class="py-10">
        <div class="max-w-2xl mx-auto px-6 sm:px-10 lg:px-16">
            <h1 class="font-serif text-3xl font-semibold text-ink mb-8">Upcoming Blood Donation Drives</h1>

            <div class="flex flex-col gap-3.5">
                @forelse ($drives as $drive)
                    <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-5">
                        <p class="font-semibold text-ink">{{ $drive->title }}</p>
                        <p class="text-sm text-ink-faint mt-0.5">{{ $drive->location }} &middot; {{ $drive->scheduled_at->format('M j, Y g:i A') }}</p>
                        @if ($drive->description)
                            <p class="text-sm text-ink-muted mt-2.5">{{ $drive->description }}</p>
                        @endif
                    </div>
                @empty
                    <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-10 text-center text-ink-faint">
                        No upcoming drives scheduled.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-public-layout>
