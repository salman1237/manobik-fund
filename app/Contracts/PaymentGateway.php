<?php

namespace App\Contracts;

use App\Models\Donation;

interface PaymentGateway
{
    /**
     * Start a hosted checkout for the given (already-persisted, pending)
     * donation and return the URL the donor should be redirected to.
     */
    public function createCheckout(Donation $donation, string $successUrl, string $cancelUrl): string;
}
