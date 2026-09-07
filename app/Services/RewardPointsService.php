<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\RewardPoint;

/**
 * "Humanity Badges" - spec §6 Phase 7: "Award points automatically on
 * completed donation (configurable % in Settings)."
 */
class RewardPointsService
{
    protected const DEFAULT_PERCENT = 1;

    /**
     * Guest donations (no linked user account) earn nothing - there's
     * nobody to award points to. Safe to call more than once for the same
     * donation; the reward_points.donation_id unique constraint plus this
     * existence check make it a no-op after the first award.
     */
    public function awardForDonation(Donation $donation): ?RewardPoint
    {
        if (! $donation->user_id || $donation->rewardPoint()->exists()) {
            return $donation->rewardPoint;
        }

        $percent = (float) setting('donation_reward_percent', self::DEFAULT_PERCENT);
        $points = (int) floor(($donation->amount / 100) * ($percent / 100));

        if ($points <= 0) {
            return null;
        }

        return RewardPoint::query()->create([
            'user_id' => $donation->user_id,
            'donation_id' => $donation->id,
            'points' => $points,
        ]);
    }
}
