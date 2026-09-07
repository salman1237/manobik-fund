<?php

namespace App\Livewire\Donations;

use App\Models\Donation;
use App\Services\RefundRequestService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RequestRefund extends Component
{
    public Donation $donation;

    public bool $showForm = false;

    public string $reason = '';

    public function mount(Donation $donation): void
    {
        $this->donation = $donation;
    }

    public function submit(): void
    {
        $this->authorize('request', [\App\Models\RefundRequest::class, $this->donation]);

        $this->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        app(RefundRequestService::class)->request($this->donation, Auth::user(), $this->reason);

        $this->showForm = false;
        $this->reason = '';
        $this->dispatch('refund-requested');
    }

    public function render()
    {
        return view('livewire.donations.request-refund', [
            'canRequest' => Auth::user()?->can('request', [\App\Models\RefundRequest::class, $this->donation]) ?? false,
        ]);
    }
}
