<x-public-layout :title="$campaign->title">
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <span class="text-xs font-medium uppercase tracking-wide text-emerald-700">
                        {{ ucfirst($campaign->category) }}
                    </span>
                    <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $campaign->title }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ $campaign->hospital_name }}</p>
                    <p class="mt-4 text-gray-700 whitespace-pre-line">{{ $campaign->description }}</p>
                </div>

                <x-campaign-charts :campaign="$campaign" />

                @if ($campaign->disbursements->isNotEmpty())
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">Transparency: Fund Disbursement</h2>
                        <ul class="space-y-3">
                            @foreach ($campaign->disbursements as $disbursement)
                                <li class="flex items-center justify-between text-sm border-b border-gray-100 pb-2 last:border-0">
                                    <div>
                                        <p class="text-gray-800 font-medium">{{ number_format($disbursement->amount / 100, 2) }} BDT disbursed</p>
                                        <p class="text-gray-500 text-xs">{{ $disbursement->disbursed_at->format('M j, Y') }}</p>
                                    </div>
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($disbursement->deposit_slip_file) }}"
                                       target="_blank" rel="noopener" class="text-emerald-700 hover:underline text-xs">
                                        View Deposit Slip
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">Patient Updates</h2>
                    @forelse ($campaign->updates as $update)
                        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-3">
                            <p class="text-xs text-gray-400">{{ $update->created_at->format('M j, Y - g:i A') }}</p>
                            <p class="text-gray-800 mt-1 whitespace-pre-line">{{ $update->content }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No updates posted yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>{{ number_format($campaign->raised_amount / 100, 2) }} raised</span>
                        <span>{{ $campaign->progressPercentage() }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-emerald-600 h-2.5 rounded-full" style="width: {{ $campaign->progressPercentage() }}%"></div>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Target: {{ number_format($campaign->target_amount / 100, 2) }}</p>
                    @if ($campaign->deadline)
                        <p class="text-sm text-gray-500">Deadline: {{ $campaign->deadline->format('M j, Y') }}</p>
                    @endif
                </div>

                <livewire:donations.donation-form :campaign="$campaign" />
            </div>
        </div>
    </div>
</x-public-layout>
