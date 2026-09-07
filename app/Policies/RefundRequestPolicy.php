<?php

namespace App\Policies;

use App\Models\Donation;
use App\Models\RefundRequest;
use App\Models\User;

class RefundRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['executive_admin', 'super_admin']);
    }

    public function view(User $user, RefundRequest $refundRequest): bool
    {
        return $user->id === $refundRequest->user_id || $user->hasAnyRole(['executive_admin', 'super_admin']);
    }

    public function request(User $user, Donation $donation): bool
    {
        return $donation->user_id === $user->id && $donation->isRefundEligible();
    }

    /**
     * Only Executive Admin and Super Admin manage refunds/redirects
     * (spec §3 role table).
     */
    public function resolve(User $user, RefundRequest $refundRequest): bool
    {
        return $user->hasAnyRole(['executive_admin', 'super_admin']);
    }
}
