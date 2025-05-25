<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    /**
     * The database associated with the model.
     * @var string
     */
    protected $connection = 'mysql_secondary';

    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'chat_messages';

    /**
     * Disable auto increment
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'receiver_id',
        'message',
        'type',
        'is_seen',
        'gift',
        'contact_type'
    ];

    /**
     * The attributes that should be cast.
     * @var array
     */
    protected $casts = [
        'date' => 'datetime:Y-m-d H:i:s.v',
        'is_seen' => 'boolean',
    ];

    /**
     * The model's default values for attributes.
     * @var array
     */
    protected $attributes = [
        'is_seen' => false,
    ];

    /**
     * Get the conversation that owns the message.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the sender of the message.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the receiver of the message.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Scope a query to only include unseen messages.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnseen($query)
    {
        return $query->where('is_seen', false);
    }

    /**
     * Scope a query to only include messages of a specific type.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark the message as seen.
     * @return bool
     */
    public function markAsSeen()
    {
        return $this->update(['is_seen' => true]);
    }
}
