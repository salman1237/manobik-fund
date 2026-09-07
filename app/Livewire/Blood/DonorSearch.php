<?php

namespace App\Livewire\Blood;

use App\Models\BloodDonor;
use Livewire\Component;

class DonorSearch extends Component
{
    public string $bloodGroup = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public float $radiusKm = 25;

    public function render()
    {
        $results = collect();

        if ($this->latitude !== null && $this->longitude !== null) {
            $results = BloodDonor::nearby(
                $this->latitude,
                $this->longitude,
                $this->radiusKm,
                $this->bloodGroup ?: null,
            );
        } elseif ($this->bloodGroup) {
            $results = BloodDonor::query()
                ->available()
                ->bloodGroup($this->bloodGroup)
                ->with('user')
                ->latest()
                ->get();
        }

        return view('livewire.blood.donor-search', ['results' => $results]);
    }
}
