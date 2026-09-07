<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, MustVerifyEmailTrait, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Only staff roles (volunteer and above) may access the internal Filament panel.
     * Plain Authenticated Users / Donation Seekers use the public site only.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole([
            'super_admin',
            'executive_admin',
            'verification_admin',
            'volunteer',
        ]);
    }

    /**
     * "Donation Seeker" is not a stored role (spec §3) - it's a capability
     * unlocked on any Authenticated User once their email is verified.
     */
    public function isDonationSeeker(): bool
    {
        return $this->hasVerifiedEmail();
    }

    /**
     * True for the internal staff roles that operate a Filament panel.
     */
    public function isStaff(): bool
    {
        return $this->hasAnyRole([
            'super_admin',
            'executive_admin',
            'verification_admin',
            'volunteer',
        ]);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(\App\Models\Campaign::class, 'seeker_id');
    }

    public function donations(): HasMany
    {
        return $this->hasMany(\App\Models\Donation::class);
    }

    public function rewardPoints(): HasMany
    {
        return $this->hasMany(\App\Models\RewardPoint::class);
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(\App\Models\RefundRequest::class);
    }

    /**
     * Total "Humanity Badges" points, always summed live from reward_points
     * rather than a cached counter column - keeps this consistent with the
     * client's "no fake/static numbers" stance applied elsewhere.
     */
    public function humanityBadgePoints(): int
    {
        return (int) $this->rewardPoints()->sum('points');
    }
}
