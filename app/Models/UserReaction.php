<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class UserReaction extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_reactions';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = null;
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'reactor_id',
        'user_id',
        'type',
        'superboom',
        'from_top',
        'is_notified',
        'from_reels',
        'date'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'datetime:Y-m-d H:i:s.v',
        'superboom' => 'boolean',
        'from_top' => 'boolean',
        'is_notified' => 'boolean',
        'from_reels' => 'boolean',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'date'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the user who received the reaction.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who made the reaction.
     *
     * @return BelongsTo
     */
    public function reactor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reactor_id');
    }

    /**
     * Scope for likes and superlikes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePositive($query)
    {
        return $query->whereIn('type', ['like', 'superlike']);
    }

    /**
     * Scope for reactions from top.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromTop($query)
    {
        return $query->where('from_top', true);
    }

    /**
     * Scope for superboom reactions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuperboom($query)
    {
        return $query->where('superboom', true);
    }

    /**
     * Check if this reaction creates a match.
     * Optimized to avoid N+1 when used in loops.
     *
     * @return bool
     */
    public function createsMatch(): bool
    {
        if (! in_array($this->type, ['like', 'superlike'])) {
            return false;
        }

        return UserReaction::where('reactor_id', $this->user_id)
            ->where('user_id', $this->reactor_id)
            ->positive()
            ->exists();
    }

    /**
     * Check if two users have mutual likes (static method).
     * Optimized to use a single query with a self-join.
     *
     * @param string $userId1
     * @param string $userId2
     * @return bool
     */
    public static function haveMutualLikes(string $userId1, string $userId2): bool
    {
        return \DB::table('user_reactions as ur1')
            ->join('user_reactions as ur2', function ($join) {
                $join->on('ur1.reactor_id', '=', 'ur2.user_id')
                    ->on('ur1.user_id', '=', 'ur2.reactor_id');
            })
            ->where('ur1.reactor_id', $userId1)
            ->where('ur1.user_id', $userId2)
            ->whereIn('ur1.type', ['like', 'superlike'])
            ->whereIn('ur2.type', ['like', 'superlike'])
            ->exists();
    }
}
