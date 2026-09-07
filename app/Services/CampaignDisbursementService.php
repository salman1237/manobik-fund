<?php

namespace App\Services;

use App\Exceptions\InvalidCampaignTransition;
use App\Models\Campaign;
use App\Models\Disbursement;
use App\Models\User;
use App\Notifications\FundsDisbursedNotification;
use Illuminate\Support\Facades\DB;

/**
 * Executive Admin disbursement action (spec section 6, Phase 6): transfers
 * funds to the beneficiary, uploads the deposit slip as public proof, and
 * optionally logs the fund_utilization breakdown tied to that disbursement
 * (spec section 4.2/5's "fund_utilization entries the Executive Admin logs
 * on disbursement").
 */
class CampaignDisbursementService
{
    public function disburse(
        Campaign $campaign,
        User $actor,
        int $amount,
        string $depositSlipPath,
        string $newStatus,
        array $fundUtilizationEntries = [],
    ): Disbursement {
        if (! in_array($campaign->status, [Campaign::STATUS_PUBLISHED, Campaign::STATUS_FUNDED], true)) {
            throw new InvalidCampaignTransition(
                "Cannot disburse funds for a campaign in status [{$campaign->status}]; expected [".Campaign::STATUS_PUBLISHED.'] or ['.Campaign::STATUS_FUNDED.'].'
            );
        }

        if (! in_array($newStatus, [Campaign::STATUS_FUNDED, Campaign::STATUS_COMPLETED], true)) {
            throw new InvalidCampaignTransition("Disbursement must set status to [".Campaign::STATUS_FUNDED.'] or ['.Campaign::STATUS_COMPLETED."], got [{$newStatus}].");
        }

        $disbursement = DB::transaction(function () use ($campaign, $actor, $amount, $depositSlipPath, $newStatus, $fundUtilizationEntries) {
            $disbursement = $campaign->disbursements()->create([
                'amount' => $amount,
                'deposit_slip_file' => $depositSlipPath,
                'disbursed_by' => $actor->id,
                'disbursed_at' => now(),
            ]);

            foreach ($fundUtilizationEntries as $entry) {
                if (blank($entry['category'] ?? null) || blank($entry['amount'] ?? null)) {
                    continue;
                }

                $campaign->fundUtilizations()->create([
                    'category' => $entry['category'],
                    'amount' => (int) round(((float) $entry['amount']) * 100),
                    'description' => $entry['description'] ?? null,
                ]);
            }

            $campaign->update(['status' => $newStatus]);

            activity()
                ->performedOn($campaign)
                ->causedBy($actor)
                ->withProperties(['disbursement_id' => $disbursement->id, 'amount' => $amount, 'new_status' => $newStatus])
                ->log('Funds disbursed');

            return $disbursement;
        });

        $campaign->seeker->notify(new FundsDisbursedNotification($campaign, $disbursement));

        return $disbursement;
    }
}
