<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloodRequest extends Model
{
    use HasFactory;

    public const URGENCY_NORMAL = 'normal';

    public const URGENCY_URGENT = 'urgent';

    public const URGENCY_CRITICAL = 'critical';

    public const STATUS_OPEN = 'open';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'requested_by',
        'requester_name',
        'requester_phone',
        'blood_group',
        'hospital_name',
        'latitude',
        'longitude',
        'urgency',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
