<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PostCampaignUpdate extends Component
{
    public Campaign $campaign;

    public string $content = '';

    public function mount(Campaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    public function post(): void
    {
        $this->authorize('postUpdate', $this->campaign);

        $this->validate([
            'content' => 'required|string|min:5|max:2000',
        ]);

        $this->campaign->updates()->create([
            'posted_by' => Auth::id(),
            'content' => $this->content,
        ]);

        $this->content = '';
        $this->dispatch('campaign-update-posted');
    }

    public function render()
    {
        return view('livewire.campaigns.post-campaign-update', [
            'canPost' => Auth::user()?->can('postUpdate', $this->campaign) ?? false,
        ]);
    }
}
