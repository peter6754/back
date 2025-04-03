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
        'superbooms',
        'superbooms_last_reset',
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
     * Get remaining superlikes (handles negative values)
     */
    public function getRemainingSuperlikes(): int
    {
        return max(0, $this->superlikes ?? 0);
    }

    /**
     * Use one superlike
     */
    public function useSuperlike(): bool
    {
        if ($this->getRemainingSuperlikes() > 0) {
            $this->decrement('superlikes');
            return true;
        }
        return false;
    }

    /**
     * Allocate weekly superlikes for subscribed users
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

        $currentWeek = now()->startOfWeek()->toDateString();
        
        // Only allocate if not already done this week
        if ($this->superlikes_last_reset !== $currentWeek) {
            $this->update([
                'superlikes' => 5,
                'superlikes_last_reset' => $currentWeek,
            ]);
        }
    }
}

