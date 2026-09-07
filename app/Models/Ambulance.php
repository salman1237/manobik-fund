<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ambulance extends Model
{
    use HasFactory;

    public const VEHICLE_TYPES = ['van', 'micro', 'freezer', 'other'];

    protected $fillable = [
        'name',
        'driver_contact',
        'vehicle_type',
        'district',
        'latitude',
        'longitude',
        'is_available',
        'added_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_available' => 'boolean',
        ];
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
