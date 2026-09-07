<?php

namespace App\Livewire\Blood;

use App\Models\BloodDonor;
use App\Models\BloodRequest;
use App\Services\CriticalBloodAlertService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RequestForm extends Component
{
    public string $requesterName = '';

    public string $requesterPhone = '';

    public string $bloodGroup = 'O+';

    public ?string $hospitalName = null;

    public string $urgency = BloodRequest::URGENCY_NORMAL;

    public bool $submitted = false;

    public function submit(): void
    {
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
