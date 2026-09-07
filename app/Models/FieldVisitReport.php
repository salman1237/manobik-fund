<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldVisitReport extends Model
{
    use HasFactory;

    public const RECOMMENDATION_APPROVE = 'approve';

    public const RECOMMENDATION_REJECT = 'reject';

    protected $fillable = [
        'campaign_id',
        'volunteer_id',
        'notes',
        'images',
        'recommendation',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function volunteer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'volunteer_id');
    }
}
