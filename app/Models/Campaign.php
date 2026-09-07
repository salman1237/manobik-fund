<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Campaign extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    public const CATEGORY_TREATMENT = 'treatment';

    public const CATEGORY_EMERGENCY = 'emergency';

    public const CATEGORY_CAMP = 'camp';

    public const CATEGORY_EDUCATION = 'education';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_VERIFICATION = 'pending_verification';

    public const STATUS_FIELD_VISIT = 'field_visit';

    public const STATUS_EXECUTIVE_REVIEW = 'executive_review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_FUNDED = 'funded';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'seeker_id',
        'category',
        'title',
        'slug',
        'description',
        'hospital_name',
        'latitude',
        'longitude',
        'bank_account_details',
        'target_amount',
        'raised_amount',
        'status',
        'rejection_reason',
        'deadline',
        'assigned_volunteer_id',
        'volunteer_assigned_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'bank_account_details' => 'encrypted:array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'target_amount' => 'integer',
            'raised_amount' => 'integer',
            'deadline' => 'date',
            'published_at' => 'datetime',
            'volunteer_assigned_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Campaign $campaign) {
            if (blank($campaign->slug)) {
                $campaign->slug = static::uniqueSlugFor($campaign->title);
            }
        });
    }

    protected static function uniqueSlugFor(string $title): string
    {
        $base = Str::slug($title) ?: 'campaign';
        $slug = $base;
        $suffix = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-".++$suffix;
        }

        return $slug;
    }

    public function seeker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seeker_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CampaignDocument::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(CampaignUpdate::class)->latest();
    }

    public function assignedVolunteer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_volunteer_id');
    }

    public function fieldVisitReports(): HasMany
    {
        return $this->hasMany(FieldVisitReport::class)->latest();
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function treatmentParameters(): HasMany
    {
        return $this->hasMany(TreatmentParameter::class);
    }

    public function fundUtilizations(): HasMany
    {
        return $this->hasMany(FundUtilization::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
        $this->addMediaCollection('gallery');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(300);
    }

    public function isEditableBySeeker(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublic(): bool
    {
        return in_array($this->status, [
            self::STATUS_PUBLISHED,
            self::STATUS_FUNDED,
            self::STATUS_COMPLETED,
        ], true);
    }

    /**
     * Live funding progress as a 0-100 percentage. Always computed from the
     * current raised/target amounts (never cached) so the public progress
     * bar/counter reflects real, live data - no stale or fabricated numbers
     * (client decision, 2026-09-07).
     */
    public function progressPercentage(): int
    {
        if ($this->target_amount <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->raised_amount / $this->target_amount) * 100));
    }

    /**
     * Shapes verified treatment_parameters + fund_utilization rows into the
     * chart data the public campaign page renders, keyed off parameter_type
     * (spec §4.2/§8) rather than a table per illness type:
     *  - "milestone"     -> a simple list (e.g. "Chemo Cycle 2 of 6")
     *  - "hospital_days" -> a single running-count timeline stat
     *  - everything else -> a line series of value-over-time ("vitals")
     * Only is_verified=true entries are ever included - unverified Seeker
     * submissions never reach the public page (spec §4.2 anti-fabrication).
     */
    public function publicChartData(): array
    {
        $verified = $this->treatmentParameters()
            ->where('is_verified', true)
            ->orderBy('recorded_at')
            ->get()
            ->groupBy(fn (TreatmentParameter $p) => $p->parameter_type);

        $milestones = [];
        $timeline = [];
        $vitals = [];

        foreach ($verified as $type => $entries) {
            $category = $entries->first()->chartCategory();

            match ($category) {
                'milestone' => $milestones = $entries->map(fn ($e) => [
                    'label' => $e->label,
                    'value' => $e->value,
                    'recorded_at' => $e->recorded_at->toDateString(),
                ])->all(),
                'timeline' => $timeline = [
                    'label' => $entries->last()->label,
                    'value' => $entries->last()->value,
                    'unit' => $entries->last()->unit,
                ],
                default => $vitals[$type] = [
                    'label' => $entries->first()->label,
                    'unit' => $entries->first()->unit,
                    'points' => $entries->map(fn ($e) => [
                        'x' => $e->recorded_at->toDateString(),
                        'y' => is_numeric($e->value) ? (float) $e->value : $e->value,
                    ])->all(),
                ],
            };
        }

        $fundUtilization = $this->fundUtilizations()
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get()
            ->map(fn ($row) => ['category' => $row->category, 'amount' => (int) $row->total])
            ->all();

        return [
            'milestones' => $milestones,
            'timeline' => $timeline,
            'vitals' => $vitals,
            'fundUtilization' => $fundUtilization,
        ];
    }
}
