<?php

namespace App\Livewire\Donations;

use App\Contracts\PaymentGateway;
use App\Models\Campaign;
use App\Models\Donation;
use App\Services\Payments\ShurjoPayGatewayService;
use App\Services\Payments\StripeGatewayService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class DonationForm extends Component
{
    public Campaign $campaign;

    public string $gateway = Donation::GATEWAY_SHURJOPAY;

    public ?float $amount = null;

    public string $currency = 'BDT';

    public string $donorName = '';

    public string $donorEmail = '';

    public bool $isAnonymous = false;

    public function mount(Campaign $campaign): void
    {
        $this->campaign = $campaign;

        if (Auth::check()) {
            $this->donorName = Auth::user()->name;
            $this->donorEmail = Auth::user()->email;
        }
    }

    public function updatedGateway(string $value): void
    {
        $this->currency = $value === Donation::GATEWAY_STRIPE ? 'USD' : 'BDT';
    }

    /**
     * Security review (spec §12): donation endpoints are a fraud/abuse
     * target (card testing, scripted spam donations) - throttle by IP
     * before creating anything or touching a payment gateway.
     */
    protected function rateLimitKey(): string
    {
        return 'donate:'.request()->ip();
    }

    public function donate()
    {
        if (RateLimiter::tooManyAttempts($this->rateLimitKey(), maxAttempts: 5)) {
            $this->addError('amount', 'Too many donation attempts. Please wait a minute and try again.');

            return;
        }

        RateLimiter::hit($this->rateLimitKey(), decaySeconds: 60);

        $data = $this->validate([
            'gateway' => 'required|in:'.Donation::GATEWAY_STRIPE.','.Donation::GATEWAY_SHURJOPAY,
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|in:USD,EUR,GBP,BDT',
            'donorName' => 'required|string|max:255',
            'donorEmail' => 'required|email|max:255',
        ]);

        $donation = Donation::query()->create([
            'campaign_id' => $this->campaign->id,
            'user_id' => Auth::id(),
            'donor_name' => $data['donorName'],
            'donor_email' => $data['donorEmail'],
            'amount' => (int) round($data['amount'] * 100),
            'currency' => $data['currency'],
            'gateway' => $data['gateway'],
            'status' => Donation::STATUS_PENDING,
            'is_anonymous' => $this->isAnonymous,
        ]);

        $checkoutUrl = $this->gatewayService($data['gateway'])->createCheckout(
            $donation,
            route('donations.success', $donation),
            route('donations.cancel', $donation),
        );

        return $this->redirect($checkoutUrl, navigate: false);
    }

    protected function gatewayService(string $gateway): PaymentGateway
    {
        return match ($gateway) {
            Donation::GATEWAY_STRIPE => app(StripeGatewayService::class),
            Donation::GATEWAY_SHURJOPAY => app(ShurjoPayGatewayService::class),
        };
    }

    public function render()
    {
        return view('livewire.donations.donation-form');
    }
}
