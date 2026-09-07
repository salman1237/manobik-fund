<div class="max-w-2xl">
    <div class="mb-9 flex items-center justify-between">
        @foreach (['Basic Info', 'Hospital Info', 'Documents', 'Banking'] as $index => $label)
            @php $n = $index + 1; @endphp
            <div class="flex-1 text-center">
                <div @class([
                    'mx-auto flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold',
                    'bg-primary text-white' => $step >= $n,
                    'bg-warm-alt text-ink-faint' => $step < $n,
                ])>
                    {{ $n }}
                </div>
                <p class="mt-2 text-xs font-semibold text-ink-faint">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-warm-surface border border-warm-border-soft rounded-2xl p-7">
        @if ($step === 1)
            <form wire:submit="saveStepOne" class="flex flex-col gap-4">
                <h2 class="font-serif text-lg font-semibold text-ink">Basic Information</h2>

                <div>
                    <x-input-label for="category" value="Category" />
                    <select id="category" wire:model="category" class="mt-2 block w-full rounded-xl border-warm-border text-sm focus:border-primary focus:ring-primary">
                        <option value="treatment">Treatment Fund</option>
                        <option value="emergency">Emergency Response</option>
                        <option value="camp">Medical Camp</option>
                        <option value="education">Education & Training</option>
                    </select>
                    <x-input-error :messages="$errors->get('category')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="title" value="Campaign Title" />
                    <x-text-input id="title" wire:model="title" class="block mt-2 w-full" />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" wire:model="description" rows="5" class="mt-2 block w-full rounded-xl border-warm-border text-sm focus:border-primary focus:ring-primary"></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="targetAmountTaka" value="Target Amount (BDT)" />
                        <x-text-input id="targetAmountTaka" type="number" step="0.01" wire:model="targetAmountTaka" class="block mt-2 w-full" />
                        <x-input-error :messages="$errors->get('targetAmountTaka')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="deadline" value="Deadline" />
                        <x-text-input id="deadline" type="date" wire:model="deadline" class="block mt-2 w-full" />
                        <x-input-error :messages="$errors->get('deadline')" class="mt-2" />
                    </div>
                </div>

                <div class="flex justify-end gap-2.5 pt-3">
                    <x-secondary-button type="button" wire:click="saveAndExit">Save & Exit</x-secondary-button>
                    <x-primary-button type="submit">Save & Continue</x-primary-button>
                </div>
            </form>
        @elseif ($step === 2)
            <form wire:submit="saveStepTwo" class="flex flex-col gap-4">
                <h2 class="font-serif text-lg font-semibold text-ink">Hospital / Medical Info</h2>

                <div>
                    <x-input-label for="hospitalName" value="Hospital Name" />
                    <x-text-input id="hospitalName" wire:model="hospitalName" class="block mt-2 w-full" />
                    <x-input-error :messages="$errors->get('hospitalName')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="latitude" value="Latitude" />
                        <x-text-input id="latitude" type="number" step="0.0000001" wire:model="latitude" class="block mt-2 w-full" />
                        <x-input-error :messages="$errors->get('latitude')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="longitude" value="Longitude" />
                        <x-text-input id="longitude" type="number" step="0.0000001" wire:model="longitude" class="block mt-2 w-full" />
                        <x-input-error :messages="$errors->get('longitude')" class="mt-2" />
                    </div>
                </div>

                <div class="flex justify-between gap-2.5 pt-3">
                    <x-secondary-button type="button" wire:click="goToStep(1)">Back</x-secondary-button>
                    <div class="flex gap-2.5">
                        <x-secondary-button type="button" wire:click="saveAndExit">Save & Exit</x-secondary-button>
                        <x-primary-button type="submit">Save & Continue</x-primary-button>
                    </div>
                </div>
            </form>
        @elseif ($step === 3)
            <form wire:submit="saveStepThree" class="flex flex-col gap-4">
                <h2 class="font-serif text-lg font-semibold text-ink">Supporting Documents</h2>
                <p class="text-sm text-ink-faint">PDF or image, up to 10MB each. Optional here &mdash; can be added later while the campaign is a draft.</p>

                <div>
                    <x-input-label for="medicalReport" value="Medical Report" />
                    <input type="file" id="medicalReport" wire:model="medicalReport" class="mt-2 block w-full text-sm text-ink-muted file:mr-3 file:px-3.5 file:py-2 file:rounded-lg file:border-0 file:bg-warm-alt file:text-ink file:font-semibold" />
                    <x-input-error :messages="$errors->get('medicalReport')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="idProof" value="ID Proof" />
                    <input type="file" id="idProof" wire:model="idProof" class="mt-2 block w-full text-sm text-ink-muted file:mr-3 file:px-3.5 file:py-2 file:rounded-lg file:border-0 file:bg-warm-alt file:text-ink file:font-semibold" />
                    <x-input-error :messages="$errors->get('idProof')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="hospitalBill" value="Hospital Bill" />
                    <input type="file" id="hospitalBill" wire:model="hospitalBill" class="mt-2 block w-full text-sm text-ink-muted file:mr-3 file:px-3.5 file:py-2 file:rounded-lg file:border-0 file:bg-warm-alt file:text-ink file:font-semibold" />
                    <x-input-error :messages="$errors->get('hospitalBill')" class="mt-2" />
                </div>

                @if ($campaignId && $this->campaign()?->documents->isNotEmpty())
                    <div class="text-sm text-ink-muted bg-warm-alt rounded-xl px-4 py-3">
                        <p class="font-semibold text-ink mb-1">Already uploaded:</p>
                        <ul class="list-disc list-inside">
                            @foreach ($this->campaign()->documents as $document)
                                <li>{{ ucfirst(str_replace('_', ' ', $document->type)) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex justify-between gap-2.5 pt-3">
                    <x-secondary-button type="button" wire:click="goToStep({{ $this->isMedicalCategory() ? 2 : 1 }})">Back</x-secondary-button>
                    <div class="flex gap-2.5">
                        <x-secondary-button type="button" wire:click="saveAndExit">Save & Exit</x-secondary-button>
                        <x-primary-button type="submit">Save & Continue</x-primary-button>
                    </div>
                </div>
            </form>
        @elseif ($step === 4)
            <form wire:submit="saveStepFourAndSubmit" class="flex flex-col gap-4">
                <h2 class="font-serif text-lg font-semibold text-ink">Banking Details</h2>
                <p class="text-sm text-ink-faint">Used only for fund disbursement once your campaign is approved and funded. Stored encrypted.</p>

                <div>
                    <x-input-label for="bankAccountName" value="Account Holder Name" />
                    <x-text-input id="bankAccountName" wire:model="bankAccountName" class="block mt-2 w-full" />
                    <x-input-error :messages="$errors->get('bankAccountName')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="bankAccountNumber" value="Account Number" />
                    <x-text-input id="bankAccountNumber" wire:model="bankAccountNumber" class="block mt-2 w-full" />
                    <x-input-error :messages="$errors->get('bankAccountNumber')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="bankName" value="Bank Name" />
                        <x-text-input id="bankName" wire:model="bankName" class="block mt-2 w-full" />
                        <x-input-error :messages="$errors->get('bankName')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="bankBranch" value="Branch (optional)" />
                        <x-text-input id="bankBranch" wire:model="bankBranch" class="block mt-2 w-full" />
                    </div>
                </div>

                <div>
                    <x-input-label for="bankRoutingNumber" value="Routing Number (optional)" />
                    <x-text-input id="bankRoutingNumber" wire:model="bankRoutingNumber" class="block mt-2 w-full" />
                </div>

                <div class="flex justify-between gap-2.5 pt-3">
                    <x-secondary-button type="button" wire:click="goToStep(3)">Back</x-secondary-button>
                    <x-primary-button type="submit">Submit for Verification</x-primary-button>
                </div>
            </form>
        @endif
    </div>
</div>
