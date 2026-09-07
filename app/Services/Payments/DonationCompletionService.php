<?php

namespace App\Services\Payments;

use App\Models\Donation;
use App\Notifications\DonationReceiptNotification;
use App\Notifications\DonationReceivedNotification;
use App\Services\RewardPointsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Shared, gateway-agnostic completion logic. Both the Stripe webhook and the
 * ShurjoPay return/verify flow call this - it must be safe to call more than
 * once for the same donation (webhook redelivery, a donor refreshing the
 * return page, etc.), per spec section 8: "donation status updates should
 * be safe to receive twice."
 */
class DonationCompletionService
{
    public function __construct(protected RewardPointsService $rewardPoints) {}

    public function complete(Donation $donation, ?array $gatewayMeta = null): Donation
    {
        if ($donation->isCompleted()) {
            return $donation;
        }

        DB::transaction(function () use ($donation, $gatewayMeta) {
            $donation->update([
                'status' => Donation::STATUS_COMPLETED,
                'gateway_meta' => $gatewayMeta ?? $donation->gateway_meta,
            ]);

            if ($donation->campaign) {
                $donation->campaign->increment('raised_amount', $donation->amount);
            }

            $this->rewardPoints->awardForDonation($donation);
        });

        $donation = $donation->fresh();

        Notification::route('mail', $donation->donor_email)
            ->notify(new DonationReceiptNotification($donation));

        $donation->campaign?->seeker?->notify(new DonationReceivedNotification($donation));

        return $donation;
    }

    public function fail(Donation $donation, ?array $gatewayMeta = null): Donation
    {
        if ($donation->isCompleted()) {
            return $donation;
        }

        $donation->update([
            'status' => Donation::STATUS_FAILED,
            'gateway_meta' => $gatewayMeta ?? $donation->gateway_meta,
        ]);

        return $donation->fresh();
    }
}
