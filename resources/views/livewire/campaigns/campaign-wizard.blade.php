<div class="max-w-3xl mx-auto py-6">
    <div class="mb-8 flex items-center justify-between">
        @foreach (['Basic Info', 'Hospital Info', 'Documents', 'Banking'] as $index => $label)
            @php $n = $index + 1; @endphp
            <div class="flex-1 text-center">
                <div @class([
                    'mx-auto flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold',
                    'bg-emerald-600 text-white' => $step >= $n,
                    'bg-gray-200 text-gray-500' => $step < $n,
                ])>
                    {{ $n }}
                </div>
                <p class="mt-1 text-xs text-gray-500">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg p-6 border border-gray-200">
        @if ($step === 1)
            <form wire:submit="saveStepOne" class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Basic Information</h2>

                <div>
                    <x-input-label for="category" value="Category" />
                    <select id="category" wire:model="category" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="treatment">Treatment Fund</option>
                        <option value="emergency">Emergency Response</option>
                        <option value="camp">Medical Camp</option>
                        <option value="education">Education & Training</option>
                    </select>
                    <x-input-error :messages="$errors->get('category')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="title" value="Campaign Title" />
                    <x-text-input id="title" wire:model="title" class="block mt-1 w-full" />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" wire:model="description" rows="5" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="targetAmountTaka" value="Target Amount (BDT)" />
                        <x-text-input id="targetAmountTaka" type="number" step="0.01" wire:model="targetAmountTaka" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('targetAmountTaka')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="deadline" value="Deadline" />
                        <x-text-input id="deadline" type="date" wire:model="deadline" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('deadline')" class="mt-2" />
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <x-secondary-button type="button" wire:click="saveAndExit">Save & Exit</x-secondary-button>
                    <x-primary-button type="submit">Save & Continue</x-primary-button>
                </div>
            </form>
        @elseif ($step === 2)
            <form wire:submit="saveStepTwo" class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Hospital / Medical Info</h2>

                <div>
                    <x-input-label for="hospitalName" value="Hospital Name" />
                    <x-text-input id="hospitalName" wire:model="hospitalName" class="block mt-1 w-full" />
                    <x-input-error :messages="$errors->get('hospitalName')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="latitude" value="Latitude" />
                        <x-text-input id="latitude" type="number" step="0.0000001" wire:model="latitude" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('latitude')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="longitude" value="Longitude" />
                        <x-text-input id="longitude" type="number" step="0.0000001" wire:model="longitude" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('longitude')" class="mt-2" />
                    </div>
                </div>

                <div class="flex justify-between gap-2 pt-4">
                    <x-secondary-button type="button" wire:click="goToStep(1)">Back</x-secondary-button>
                    <div class="flex gap-2">
                        <x-secondary-button type="button" wire:click="saveAndExit">Save & Exit</x-secondary-button>
                        <x-primary-button type="submit">Save & Continue</x-primary-button>
                    </div>
                </div>
            </form>
        @elseif ($step === 3)
            <form wire:submit="saveStepThree" class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Supporting Documents</h2>
                <p class="text-sm text-gray-500">PDF or image, up to 10MB each. Optional here - can be added later while the campaign is a draft.</p>

                <div>
                    <x-input-label for="medicalReport" value="Medical Report" />
                    <input type="file" id="medicalReport" wire:model="medicalReport" class="mt-1 block w-full text-sm" />
                    <x-input-error :messages="$errors->get('medicalReport')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="idProof" value="ID Proof" />
                    <input type="file" id="idProof" wire:model="idProof" class="mt-1 block w-full text-sm" />
                    <x-input-error :messages="$errors->get('idProof')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="hospitalBill" value="Hospital Bill" />
                    <input type="file" id="hospitalBill" wire:model="hospitalBill" class="mt-1 block w-full text-sm" />
                    <x-input-error :messages="$errors->get('hospitalBill')" class="mt-2" />
                </div>

                @if ($campaignId && $this->campaign()?->documents->isNotEmpty())
                    <div class="text-sm text-gray-600">
                        <p class="font-medium">Already uploaded:</p>
                        <ul class="list-disc list-inside">
                            @foreach ($this->campaign()->documents as $document)
                                <li>{{ ucfirst(str_replace('_', ' ', $document->type)) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex justify-between gap-2 pt-4">
                    <x-secondary-button type="button" wire:click="goToStep({{ $this->isMedicalCategory() ? 2 : 1 }})">Back</x-secondary-button>
                    <div class="flex gap-2">
                        <x-secondary-button type="button" wire:click="saveAndExit">Save & Exit</x-secondary-button>
                        <x-primary-button type="submit">Save & Continue</x-primary-button>
                    </div>
                </div>
            </form>
        @elseif ($step === 4)
            <form wire:submit="saveStepFourAndSubmit" class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Banking Details</h2>
                <p class="text-sm text-gray-500">Used only for fund disbursement once your campaign is approved and funded. Stored encrypted.</p>

                <div>
                    <x-input-label for="bankAccountName" value="Account Holder Name" />
                    <x-text-input id="bankAccountName" wire:model="bankAccountName" class="block mt-1 w-full" />
                    <x-input-error :messages="$errors->get('bankAccountName')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="bankAccountNumber" value="Account Number" />
                    <x-text-input id="bankAccountNumber" wire:model="bankAccountNumber" class="block mt-1 w-full" />
                    <x-input-error :messages="$errors->get('bankAccountNumber')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="bankName" value="Bank Name" />
                        <x-text-input id="bankName" wire:model="bankName" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('bankName')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="bankBranch" value="Branch (optional)" />
                        <x-text-input id="bankBranch" wire:model="bankBranch" class="block mt-1 w-full" />
                    </div>
                </div>

                <div>
                    <x-input-label for="bankRoutingNumber" value="Routing Number (optional)" />
                    <x-text-input id="bankRoutingNumber" wire:model="bankRoutingNumber" class="block mt-1 w-full" />
                </div>

                <div class="flex justify-between gap-2 pt-4">
                    <x-secondary-button type="button" wire:click="goToStep(3)">Back</x-secondary-button>
                    <x-primary-button type="submit">Submit for Verification</x-primary-button>
                </div>
            </form>
        @endif
    </div>
</div>
