<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Donation extends Model
{
    use HasFactory;

    public const GATEWAY_STRIPE = 'stripe';

    public const GATEWAY_SHURJOPAY = 'shurjopay';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'campaign_id',
        'user_id',
        'donor_name',
        'donor_email',
        'amount',
        'currency',
        'gateway',
        'transaction_id',
        'status',
        'is_anonymous',
        'gateway_meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'is_anonymous' => 'boolean',
            'gateway_meta' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rewardPoint(): HasOne
    {
        return $this->hasOne(RewardPoint::class);
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Refundable if completed, has a linked user (spec §3: refunds are an
     * Authenticated User capability), and has no request already in flight
     * or already approved.
     */
    public function isRefundEligible(): bool
    {
        return $this->isCompleted()
            && $this->user_id !== null
            && ! $this->refundRequests()->whereIn('status', [RefundRequest::STATUS_PENDING, RefundRequest::STATUS_APPROVED])->exists();
    }
}
