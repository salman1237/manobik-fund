<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundUtilization extends Model
{
    use HasFactory;

    public const CATEGORY_SURGERY = 'surgery';

    public const CATEGORY_MEDICATION = 'medication';

    public const CATEGORY_ICU = 'icu';

    public const CATEGORY_POST_OP = 'post_op';

    public const CATEGORY_OTHER = 'other';

    protected $fillable = [
        'campaign_id',
        'category',
        'amount',
        'description',
        'proof_file',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
