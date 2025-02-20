<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'conversations';

    /**
     * Disable timestamps
     * @var bool
     */
    public $timestamps = false;

    /**
     * The primary key for the model.
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     * @var array<string>
     */
    protected $fillable = [
        'user1_id',
        'user2_id',
        'is_pinned_by_user1',
        'is_pinned_by_user2'
    ];

    /**
     * The attributes that should be cast.
     * @var array<string, string>
     */
    protected $casts = [
        'is_pinned_by_user1' => 'boolean',
        'is_pinned_by_user2' => 'boolean',
    ];

    /**
     * Get the first user in conversation.
     * @return BelongsTo
     */
    public function user1(): BelongsTo
    {
        return $this->belongsTo(Secondaryuser::class, 'user1_id', 'id');
    }

    /**
     * Get the second user in conversation.
     * @return BelongsTo
     */
    public function user2(): BelongsTo
    {
        return $this->belongsTo(Secondaryuser::class, 'user2_id', 'id');
    }

    /**
     * Get all messages for this conversation.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    /**
     * Scope to find conversation between two users.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $user1Id
     * @param string $user2Id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetweenUsers($query, string $user1Id, string $user2Id)
    {
        return $query->where(function ($q) use ($user1Id, $user2Id) {
            $q->where('user1_id', $user1Id)
                ->where('user2_id', $user2Id);
        })->orWhere(function ($q) use ($user1Id, $user2Id) {
            $q->where('user1_id', $user2Id)
                ->where('user2_id', $user1Id);
        });
    }
}
