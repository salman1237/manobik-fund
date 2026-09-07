<x-public-layout title="Blood Requests">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Open Blood Requests') }}</h2>
            <a href="{{ route('blood.requests.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm rounded-md hover:bg-emerald-700">
                Post a Request
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-3">
            @forelse ($requests as $request)
                <div class="bg-white border border-gray-200 rounded-lg p-4 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">
                            {{ $request->blood_group }} needed
                            @if ($request->hospital_name) &middot; {{ $request->hospital_name }} @endif
                        </p>
                        <p class="text-xs text-gray-500">Requested by {{ $request->requester_name }} &middot; {{ $request->requester_phone }}</p>
                    </div>
                    <span @class([
                        'text-xs font-medium px-2 py-1 rounded-full',
                        'bg-gray-100 text-gray-600' => $request->urgency === 'normal',
                        'bg-amber-100 text-amber-700' => $request->urgency === 'urgent',
                        'bg-red-100 text-red-700' => $request->urgency === 'critical',
                    ])>
                        {{ ucfirst($request->urgency) }}
                    </span>
                </div>
            @empty
                <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
                    No open blood requests right now.
                </div>
            @endforelse

            {{ $requests->links() }}
        </div>
    </div>
</x-public-layout>
