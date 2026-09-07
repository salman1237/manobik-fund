<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use App\Models\TreatmentParameter;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class SubmitTreatmentParameter extends Component
{
    public Campaign $campaign;

    public string $parameterType = TreatmentParameter::TYPE_PAIN_SCALE;

    public string $label = '';

    public string $value = '';

    public ?string $unit = null;

    public string $recordedAt;

    public function mount(Campaign $campaign): void
    {
        $this->campaign = $campaign;
        $this->recordedAt = now()->toDateString();
    }

    public function submit(): void
    {
        Gate::authorize('create', [TreatmentParameter::class, $this->campaign]);

        $data = $this->validate([
            'parameterType' => 'required|string|max:100',
            'label' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'recordedAt' => 'required|date|before_or_equal:today',
        ]);

        $this->campaign->treatmentParameters()->create([
            'parameter_type' => $data['parameterType'],
            'label' => $data['label'],
            'value' => $data['value'],
            'unit' => $data['unit'],
            'recorded_at' => $data['recordedAt'],
            'is_verified' => false,
        ]);

        $this->reset(['label', 'value', 'unit']);
        $this->dispatch('treatment-parameter-submitted');
    }

    public function render()
    {
        return view('livewire.campaigns.submit-treatment-parameter', [
            'canSubmit' => Gate::allows('create', [TreatmentParameter::class, $this->campaign]),
        ]);
    }
}
