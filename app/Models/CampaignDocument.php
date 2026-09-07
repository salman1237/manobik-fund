<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignDocument extends Model
{
    use HasFactory;

    public const TYPE_MEDICAL_REPORT = 'medical_report';

    public const TYPE_ID_PROOF = 'id_proof';

    public const TYPE_HOSPITAL_BILL = 'hospital_bill';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'campaign_id',
        'type',
        'file_path',
        'uploaded_by',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
