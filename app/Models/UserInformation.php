<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInformation extends Model
{
    use HasFactory;

    protected $table = 'user_information';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'bio',
        'zodiac_sign',
        'education',
        'educational_institution',
        'family',
        'communication',
        'love_language',
        'alcohole',
        'smoking',
        'sport',
        'food',
        'social_network',
        'sleep',
        'family_status',
        'role',
        'company',
        'streak',
        'superlikes',
        'superlikes_last_reset',
        'purchased_superlikes',
        'superbooms',
        'superbooms_last_reset',
        'purchased_superbooms',
        'superboom_due_date',
        'like_update',
        'last_banner'
    ];

    protected $casts = [
        'superlikes_last_reset' => 'date',
        'superbooms_last_reset' => 'date',
        'superboom_due_date' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(Secondaryuser::class, 'user_id', 'id');
    }

    /**
     * Get remaining superlikes (allocated + purchased)
     */
    public function getRemainingSuperlikes(): int
    {
        $allocated = max(0, $this->superlikes ?? 0);
        $purchased = max(0, $this->purchased_superlikes ?? 0);
        return $allocated + $purchased;
    }

    /**
     * Get remaining superbooms (allocated + purchased)
     */
    public function getRemainingSuperbooms(): int
    {
        $allocated = max(0, $this->superbooms ?? 0);
        $purchased = max(0, $this->purchased_superbooms ?? 0);
        return $allocated + $purchased;
    }

    /**
     * Use one superlike (first from allocated, then from purchased)
     */
    public function useSuperlike(): bool
    {
        if ($this->getRemainingSuperlikes() > 0) {
            // First use allocated superlikes
            if (($this->superlikes ?? 0) > 0) {
                $this->decrement('superlikes');
            } else {
                // Then use purchased superlikes
                $this->decrement('purchased_superlikes');
            }
            return true;
        }
        return false;
    }

    /**
     * Use one superboom (first from allocated, then from purchased)
     */
    public function useSuperboom(): bool
    {
        if ($this->getRemainingSuperbooms() > 0) {
            // First use allocated superbooms
            if (($this->superbooms ?? 0) > 0) {
                $this->decrement('superbooms');
            } else {
                // Then use purchased superbooms
                $this->decrement('purchased_superbooms');
            }
            return true;
        }
        return false;
    }

    /**
     * Allocate weekly superlikes for subscribed users
     * Resets allocated superlikes to 5 every 7 days from last reset
     */
    public function allocateWeeklySuperlikes(): void
    {
        $user = $this->user;

        // Check if user has Gold or Premium subscription
        if (!$user || !$user->activeSubscription) {
            return;
        }

        $subscriptionType = $user->activeSubscription->package->subscription->type ?? '';
        $isEligible = in_array($subscriptionType, ['Tinderone Gold', 'Tinderone Premium']);

        if (!$isEligible) {
            return;
        }

        // If no last reset date, don't allocate (will be set on purchase)
        if (!$this->superlikes_last_reset) {
            return;
        }

        $lastReset = \Carbon\Carbon::parse($this->superlikes_last_reset);
        $daysSinceReset = $lastReset->diffInDays(now());

        // Only allocate if 7 or more days have passed since last reset
        if ($daysSinceReset >= 7) {
            $this->update([
                'superlikes' => 5,
                'superlikes_last_reset' => now()->toDateString(),
            ]);
        }
    }

    /**
     * Allocate weekly superbooms for subscribed users
     * Resets allocated superbooms to 1 every 7 days from last reset
     */
    public function allocateWeeklySuperbooms(): void
    {
        $user = $this->user;

        // Check if user has Gold or Premium subscription
        if (!$user || !$user->activeSubscription) {
            return;
        }

        $subscriptionType = $user->activeSubscription->package->subscription->type ?? '';
        $isEligible = in_array($subscriptionType, ['Tinderone Gold', 'Tinderone Premium']);

        if (!$isEligible) {
            return;
        }

        // If no last reset date, don't allocate (will be set on purchase)
        if (!$this->superbooms_last_reset) {
            return;
        }

        $lastReset = \Carbon\Carbon::parse($this->superbooms_last_reset);
        $daysSinceReset = $lastReset->diffInDays(now());

        // Only allocate if 7 or more days have passed since last reset
        if ($daysSinceReset >= 7) {
            $this->update([
                'superbooms' => 1,
                'superbooms_last_reset' => now()->toDateString(),
            ]);
        }
    }
}

