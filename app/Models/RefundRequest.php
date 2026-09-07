<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const RESOLUTION_GATEWAY_REFUND = 'gateway_refund';

    public const RESOLUTION_CREDIT_REDIRECT = 'credit_redirect';

    protected $fillable = [
        'donation_id',
        'user_id',
        'reason',
        'status',
        'resolution_type',
        'redirect_campaign_id',
        'processed_by',
        'rejection_reason',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function redirectCampaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'redirect_campaign_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
