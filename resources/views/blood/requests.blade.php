<x-public-layout title="Blood Requests">
    <div class="py-10">
        <div class="max-w-2xl mx-auto px-6 sm:px-10 lg:px-16">
            <div class="flex items-center justify-between mb-8">
                <h1 class="font-serif text-3xl font-semibold text-ink">{{ __('Open Blood Requests') }}</h1>
                <a href="{{ route('blood.requests.create') }}" wire:navigate
                   class="inline-flex items-center px-5 py-2.5 bg-danger text-white text-sm font-bold rounded-xl hover:opacity-90 transition">
                    Post a Request
                </a>
            </div>

            <div class="flex flex-col gap-3.5">
                @forelse ($requests as $request)
                    <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-5 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-semibold text-ink">
                                {{ $request->blood_group }} needed
                                @if ($request->hospital_name) &middot; {{ $request->hospital_name }} @endif
                            </p>
                            <p class="text-xs text-ink-faint mt-1">Requested by {{ $request->requester_name }} &middot; {{ $request->requester_phone }}</p>
                        </div>
                        <span @class([
                            'text-xs font-bold px-3 py-1.5 rounded-full shrink-0',
                            'bg-warm-alt text-ink-faint' => $request->urgency === 'normal',
                            'bg-warn-light text-warn-dark' => $request->urgency === 'urgent',
                            'bg-danger-light text-danger' => $request->urgency === 'critical',
                        ])>
                            {{ ucfirst($request->urgency) }}
                        </span>
                    </div>
                @empty
                    <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-10 text-center text-ink-faint">
                        No open blood requests right now.
                    </div>
                @endforelse

                <div>
                    {{ $requests->links() }}
                </div>
            </div>
        </div>
    </div>
</x-public-layout>
