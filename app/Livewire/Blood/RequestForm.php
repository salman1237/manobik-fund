<?php

namespace App\Livewire\Blood;

use App\Models\BloodDonor;
use App\Models\BloodRequest;
use App\Services\CriticalBloodAlertService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class RequestForm extends Component
{
    public string $requesterName = '';

    public string $requesterPhone = '';

    public string $bloodGroup = 'O+';

    public ?string $hospitalName = null;

    public string $urgency = BloodRequest::URGENCY_NORMAL;

    public bool $submitted = false;

    /**
     * Guest-postable, and a critical-urgency request triggers real SMS
     * alerts to donors - throttle to stop it being used to spam donors
     * with fake urgent alerts (spec §12 security review).
     */
    protected function rateLimitKey(): string
    {
        return 'blood-request:'.request()->ip();
    }

    public function submit(): void
    {
        if (RateLimiter::tooManyAttempts($this->rateLimitKey(), maxAttempts: 3)) {
            $this->addError('requesterPhone', 'Too many requests posted from this connection. Please wait a few minutes and try again.');

            return;
        }

        RateLimiter::hit($this->rateLimitKey(), decaySeconds: 300);

        $data = $this->validate([
            'requesterName' => 'required|string|max:255',
            'requesterPhone' => 'required|string|max:30',
            'bloodGroup' => 'required|in:'.implode(',', BloodDonor::BLOOD_GROUPS),
            'hospitalName' => 'nullable|string|max:255',
            'urgency' => 'required|in:normal,urgent,critical',
        ]);

        $bloodRequest = BloodRequest::query()->create([
            'requested_by' => Auth::id(),
            'requester_name' => $data['requesterName'],
            'requester_phone' => $data['requesterPhone'],
            'blood_group' => $data['bloodGroup'],
            'hospital_name' => $data['hospitalName'],
            'urgency' => $data['urgency'],
            'status' => BloodRequest::STATUS_OPEN,
        ]);

        app(CriticalBloodAlertService::class)->alertMatchingDonors($bloodRequest);

        $this->submitted = true;
        $this->reset(['requesterName', 'requesterPhone', 'hospitalName']);
    }

    public function render()
    {
        return view('livewire.blood.request-form');
    }
}
