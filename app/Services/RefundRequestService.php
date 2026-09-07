<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\RefundRequest;
use App\Models\User;
use App\Notifications\RefundRequestResolvedNotification;
use App\Services\Payments\ShurjoPayGatewayService;
use App\Services\Payments\StripeGatewayService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * spec section 6, Phase 7: "Refund request flow: user requests -> Executive
 * Admin approves/rejects -> gateway refund or credit redirect to another
 * campaign."
 */
class RefundRequestService
{
    public function request(Donation $donation, User $user, string $reason): RefundRequest
    {
        if ($donation->user_id !== $user->id) {
            throw new InvalidArgumentException('A refund can only be requested by the donor themselves.');
        }

        if (! $donation->isRefundEligible()) {
            throw new RuntimeException('This donation is not eligible for a refund.');
        }

        return RefundRequest::query()->create([
            'donation_id' => $donation->id,
            'user_id' => $user->id,
            'reason' => $reason,
            'status' => RefundRequest::STATUS_PENDING,
        ]);
    }

    public function approveWithGatewayRefund(RefundRequest $refundRequest, User $actor): RefundRequest
    {
        $this->assertPending($refundRequest);

        $donation = $refundRequest->donation;
        $this->gatewayFor($donation)->refund($donation);

        DB::transaction(function () use ($refundRequest, $donation, $actor) {
            if ($donation->campaign) {
                $donation->campaign->decrement('raised_amount', $donation->amount);
            }

            $donation->update(['status' => Donation::STATUS_REFUNDED]);

            $refundRequest->update([
                'status' => RefundRequest::STATUS_APPROVED,
                'resolution_type' => RefundRequest::RESOLUTION_GATEWAY_REFUND,
                'processed_by' => $actor->id,
                'processed_at' => now(),
            ]);
        });

        $refundRequest->user->notify(new RefundRequestResolvedNotification($refundRequest->fresh()));

        return $refundRequest->fresh();
    }

    public function approveWithCreditRedirect(RefundRequest $refundRequest, User $actor, Campaign $redirectCampaign): RefundRequest
    {
        $this->assertPending($refundRequest);

        $donation = $refundRequest->donation;

        DB::transaction(function () use ($refundRequest, $donation, $actor, $redirectCampaign) {
            if ($donation->campaign) {
                $donation->campaign->decrement('raised_amount', $donation->amount);
            }

            $redirectCampaign->increment('raised_amount', $donation->amount);
            $donation->update(['campaign_id' => $redirectCampaign->id]);

            $refundRequest->update([
                'status' => RefundRequest::STATUS_APPROVED,
                'resolution_type' => RefundRequest::RESOLUTION_CREDIT_REDIRECT,
                'redirect_campaign_id' => $redirectCampaign->id,
                'processed_by' => $actor->id,
                'processed_at' => now(),
            ]);
        });

        $refundRequest->user->notify(new RefundRequestResolvedNotification($refundRequest->fresh()));

        return $refundRequest->fresh();
    }

    public function reject(RefundRequest $refundRequest, User $actor, string $reason): RefundRequest
    {
        $this->assertPending($refundRequest);

        $refundRequest->update([
            'status' => RefundRequest::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'processed_by' => $actor->id,
            'processed_at' => now(),
        ]);

        $refundRequest->user->notify(new RefundRequestResolvedNotification($refundRequest->fresh()));

        return $refundRequest->fresh();
    }

    protected function assertPending(RefundRequest $refundRequest): void
    {
        if (! $refundRequest->isPending()) {
            throw new RuntimeException("Refund request [{$refundRequest->id}] has already been processed.");
        }
    }

    protected function gatewayFor(Donation $donation): PaymentGateway
    {
        return match ($donation->gateway) {
            Donation::GATEWAY_STRIPE => app(StripeGatewayService::class),
            Donation::GATEWAY_SHURJOPAY => app(ShurjoPayGatewayService::class),
            default => throw new InvalidArgumentException("Unknown gateway [{$donation->gateway}]."),
        };
    }
}
