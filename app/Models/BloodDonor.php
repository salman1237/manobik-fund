<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class BloodDonor extends Model
{
    use HasFactory;

    public const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    protected $fillable = [
        'user_id',
        'blood_group',
        'latitude',
        'longitude',
        'last_donation_date',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'last_donation_date' => 'date',
            'is_available' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    public function scopeBloodGroup(Builder $query, string $group): Builder
    {
        return $query->where('blood_group', $group);
    }

    /**
     * Great-circle distance (km) to a point, computed in PHP rather than
     * driver-specific SQL trig functions (MySQL has RADIANS/ACOS; SQLite,
     * used in tests, does not) - keeps proximity search portable across
     * both.
     */
    public function distanceInKmFrom(float $latitude, float $longitude): ?float
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        $earthRadiusKm = 6371;

        $latDelta = deg2rad((float) $this->latitude - $latitude);
        $lonDelta = deg2rad((float) $this->longitude - $longitude);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($latitude)) * cos(deg2rad((float) $this->latitude)) * sin($lonDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Available donors of a blood group within radiusKm of a point, nearest
     * first. Filters with a cheap bounding box in SQL first (portable, no
     * trig functions needed), then refines/sorts by exact Haversine
     * distance in PHP.
     */
    public static function nearby(float $latitude, float $longitude, float $radiusKm, ?string $bloodGroup = null): Collection
    {
        $latDegreeKm = 110.574;
        $lonDegreeKm = 111.320 * cos(deg2rad($latitude));

        $latDelta = $radiusKm / $latDegreeKm;
        $lonDelta = $lonDegreeKm > 0 ? $radiusKm / $lonDegreeKm : 180;

        $query = static::query()
            ->available()
            ->whereBetween('latitude', [$latitude - $latDelta, $latitude + $latDelta])
            ->whereBetween('longitude', [$longitude - $lonDelta, $longitude + $lonDelta]);

        if ($bloodGroup) {
            $query->bloodGroup($bloodGroup);
        }

        return $query->get()
            ->map(function (self $donor) use ($latitude, $longitude) {
                $donor->distance_km = $donor->distanceInKmFrom($latitude, $longitude);

                return $donor;
            })
            ->filter(fn (self $donor) => $donor->distance_km !== null && $donor->distance_km <= $radiusKm)
            ->sortBy('distance_km')
            ->values();
    }
}
