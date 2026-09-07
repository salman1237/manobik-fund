<?php

namespace App\Livewire\Blood;

use App\Models\BloodDonor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class DonorProfileForm extends Component
{
    public ?BloodDonor $profile = null;

    public string $bloodGroup = 'O+';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public bool $isAvailable = true;

    public function mount(): void
    {
        $this->profile = Auth::user()->bloodDonorProfile;

        if ($this->profile) {
            $this->bloodGroup = $this->profile->blood_group;
            $this->latitude = $this->profile->latitude ? (float) $this->profile->latitude : null;
            $this->longitude = $this->profile->longitude ? (float) $this->profile->longitude : null;
            $this->isAvailable = $this->profile->is_available;
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'bloodGroup' => 'required|in:'.implode(',', BloodDonor::BLOOD_GROUPS),
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'isAvailable' => 'boolean',
        ]);

        if ($this->profile) {
            Gate::authorize('update', $this->profile);

            $this->profile->update([
                'blood_group' => $data['bloodGroup'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'is_available' => $data['isAvailable'],
            ]);
        } else {
            Gate::authorize('create', BloodDonor::class);

            $this->profile = Auth::user()->bloodDonorProfile()->create([
                'blood_group' => $data['bloodGroup'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'is_available' => $data['isAvailable'],
            ]);
        }

        session()->flash('status', 'Blood donor profile saved.');
    }

    public function toggleAvailability(): void
    {
        Gate::authorize('update', $this->profile);

        $this->profile->update(['is_available' => ! $this->profile->is_available]);
        $this->isAvailable = $this->profile->is_available;
    }

    public function render()
    {
        return view('livewire.blood.donor-profile-form');
    }
}
