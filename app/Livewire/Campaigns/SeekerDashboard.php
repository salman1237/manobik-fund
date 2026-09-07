<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SeekerDashboard extends Component
{
    public function render()
    {
        $campaigns = Campaign::query()
            ->where('seeker_id', Auth::id())
            ->latest()
            ->get();

        return view('livewire.campaigns.seeker-dashboard', [
            'campaigns' => $campaigns,
        ]);
    }
}
