<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignDocument;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class CampaignWizard extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $campaignId = null;

    public int $step = 1;

    // Step 1 - basic info
    public string $category = Campaign::CATEGORY_TREATMENT;

    public string $title = '';

    public string $description = '';

    public ?float $targetAmountTaka = null;

    public ?string $deadline = null;

    // Step 2 - hospital/medical info
    public ?string $hospitalName = null;

    public ?float $latitude = null;

    public ?float $longitude = null;

    // Step 3 - documents
    public $medicalReport = null;

    public $idProof = null;

    public $hospitalBill = null;

    // Step 4 - banking
    public ?string $bankAccountName = null;

    public ?string $bankAccountNumber = null;

    public ?string $bankName = null;

    public ?string $bankBranch = null;

    public ?string $bankRoutingNumber = null;

    public function mount(?Campaign $campaign = null): void
    {
        if ($campaign?->exists) {
            $this->authorize('update', $campaign);

            $this->campaignId = $campaign->id;
            $this->category = $campaign->category;
            $this->title = $campaign->title;
            $this->description = (string) $campaign->description;
            $this->targetAmountTaka = $campaign->target_amount / 100;
            $this->deadline = $campaign->deadline?->toDateString();
            $this->hospitalName = $campaign->hospital_name;
            $this->latitude = $campaign->latitude ? (float) $campaign->latitude : null;
            $this->longitude = $campaign->longitude ? (float) $campaign->longitude : null;

            $bank = $campaign->bank_account_details ?? [];
            $this->bankAccountName = $bank['account_name'] ?? null;
            $this->bankAccountNumber = $bank['account_number'] ?? null;
            $this->bankName = $bank['bank_name'] ?? null;
            $this->bankBranch = $bank['branch'] ?? null;
            $this->bankRoutingNumber = $bank['routing_number'] ?? null;
        } else {
            $this->authorize('create', Campaign::class);
        }
    }

    protected function campaign(): ?Campaign
    {
        return $this->campaignId ? Campaign::query()->findOrFail($this->campaignId) : null;
    }

    public function saveStepOne(): void
    {
        $data = $this->validate([
            'category' => 'required|in:'.implode(',', [
                Campaign::CATEGORY_TREATMENT,
                Campaign::CATEGORY_EMERGENCY,
                Campaign::CATEGORY_CAMP,
                Campaign::CATEGORY_EDUCATION,
            ]),
            'title' => 'required|string|min:10|max:255',
            'description' => 'required|string|min:30',
            'targetAmountTaka' => 'required|numeric|min:100',
            'deadline' => 'required|date|after:today',
        ]);

        $attributes = [
            'category' => $data['category'],
            'title' => $data['title'],
            'description' => $data['description'],
            'target_amount' => (int) round($data['targetAmountTaka'] * 100),
            'deadline' => $data['deadline'],
        ];

        $campaign = $this->campaign();

        if ($campaign) {
            $this->authorize('update', $campaign);
            $campaign->update($attributes);
        } else {
            $campaign = Campaign::query()->create($attributes + [
                'seeker_id' => Auth::id(),
                'status' => Campaign::STATUS_DRAFT,
            ]);
            $this->campaignId = $campaign->id;
        }

        // Education campaigns have no hospital/medical dimension to
        // capture (spec Phase 10: "simplify the form for these types") -
        // skip straight to documents.
        $this->step = $data['category'] === Campaign::CATEGORY_EDUCATION ? 3 : 2;
    }

    public function isMedicalCategory(): bool
    {
        return $this->category !== Campaign::CATEGORY_EDUCATION;
    }

    public function saveStepTwo(): void
    {
        $data = $this->validate([
            'hospitalName' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $this->campaign()?->update([
            'hospital_name' => $data['hospitalName'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
        ]);

        $this->step = 3;
    }

    public function saveStepThree(): void
    {
        $this->validate([
            'medicalReport' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
            'idProof' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
            'hospitalBill' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ]);

        $campaign = $this->campaign();

        foreach ([
            CampaignDocument::TYPE_MEDICAL_REPORT => $this->medicalReport,
            CampaignDocument::TYPE_ID_PROOF => $this->idProof,
            CampaignDocument::TYPE_HOSPITAL_BILL => $this->hospitalBill,
        ] as $type => $upload) {
            if (! $upload) {
                continue;
            }

            // Medical docs/ID proofs are sensitive - the 'local' disk root
            // (storage/app/private) is not web-accessible.
            $path = $upload->store('campaign-documents/'.$campaign->id, 'local');

            CampaignDocument::query()->create([
                'campaign_id' => $campaign->id,
                'type' => $type,
                'file_path' => $path,
                'uploaded_by' => Auth::id(),
            ]);
        }

        $this->medicalReport = null;
        $this->idProof = null;
        $this->hospitalBill = null;

        $this->step = 4;
    }

    public function saveStepFourAndSubmit(): void
    {
        $data = $this->validate([
            'bankAccountName' => 'required|string|max:255',
            'bankAccountNumber' => 'required|string|max:64',
            'bankName' => 'required|string|max:255',
            'bankBranch' => 'nullable|string|max:255',
            'bankRoutingNumber' => 'nullable|string|max:64',
        ]);

        $campaign = $this->campaign();

        $campaign->update([
            'bank_account_details' => [
                'account_name' => $data['bankAccountName'],
                'account_number' => $data['bankAccountNumber'],
                'bank_name' => $data['bankName'],
                'branch' => $data['bankBranch'],
                'routing_number' => $data['bankRoutingNumber'],
            ],
            'status' => Campaign::STATUS_PENDING_VERIFICATION,
        ]);

        session()->flash('status', 'Campaign submitted for verification.');

        $this->redirectRoute('seeker.campaigns.index', navigate: true);
    }

    public function saveAndExit(): void
    {
        $this->redirectRoute('seeker.campaigns.index', navigate: true);
    }

    public function goToStep(int $step): void
    {
        if ($this->campaignId && $step <= 4 && $step >= 1) {
            $this->step = $step;
        }
    }

    public function render()
    {
        return view('livewire.campaigns.campaign-wizard');
    }
}
