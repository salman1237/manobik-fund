<?php

namespace App\Services;

use App\Models\BloodDonor;
use App\Models\BloodRequest;
use App\Notifications\CriticalBloodRequestNotification;

/**
 * spec §6 Phase 11: SMS alerts for critical blood requests. Only fires for
 * urgency=critical - normal/urgent requests rely on the public listing
 * page instead of interrupting donors.
 */
class CriticalBloodAlertService
{
    public function alertMatchingDonors(BloodRequest $bloodRequest): int
    {
        if ($bloodRequest->urgency !== BloodRequest::URGENCY_CRITICAL) {
            return 0;
        }

        $donors = BloodDonor::query()
            ->available()
            ->bloodGroup($bloodRequest->blood_group)
            ->with('user')
            ->get();

        foreach ($donors as $donor) {
            $donor->user->notify(new CriticalBloodRequestNotification($bloodRequest));
        }

        return $donors->count();
    }
}
