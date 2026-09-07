<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentParameter extends Model
{
    use HasFactory;

    public const TYPE_MILESTONE = 'milestone';

    public const TYPE_HOSPITAL_DAYS = 'hospital_days';

    public const TYPE_PAIN_SCALE = 'pain_scale';

    public const TYPE_WBC_COUNT = 'wbc_count';

    public const TYPE_PLATELET = 'platelet';

    public const TYPE_CREATININE = 'creatinine';

    public const TYPE_BILIRUBIN = 'bilirubin';

    protected $fillable = [
        'campaign_id',
        'parameter_type',
        'label',
        'value',
        'unit',
        'recorded_at',
        'is_verified',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'is_verified' => 'boolean',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * The chart-rendering layer keys off parameter_type (+ unit) rather than
     * a new table per illness type, per spec §8.
     */
    public function chartCategory(): string
    {
        return match ($this->parameter_type) {
            self::TYPE_MILESTONE => 'milestone',
            self::TYPE_HOSPITAL_DAYS => 'timeline',
            default => 'vital',
        };
    }
}
