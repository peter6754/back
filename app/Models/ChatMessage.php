<?php

namespace App\Models;

use App\Services\SeaweedFsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chat_messages';

    /**
     * Enable timestamps
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
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
        'contact_type',
        'date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'datetime:Y-m-d H:i:s.v',
        'is_seen' => 'boolean',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'is_seen' => false,
    ];

    /**
     * Get the conversation that owns the message.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the sender of the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(Secondaryuser::class, 'sender_id');
    }

    /**
     * Get the receiver of the message.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Secondaryuser::class, 'receiver_id');
    }

    /**
     * Scope a query to only include unseen messages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnseen($query)
    {
        return $query->where('is_seen', false);
    }

    /**
     * Scope a query to only include messages of a specific type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark the message as seen.
     *
     * @return bool
     */
    public function markAsSeen()
    {
        return $this->update(['is_seen' => true]);
    }

    /**
     * Check if message has attached file.
     * For media type messages, file info is stored in the message field
     *
     * @return bool
     */
    public function hasFile()
    {
        return $this->type === 'media' && ! empty($this->message);
    }

    /**
     * Get file URL for media messages.
     *
     * @return string|null
     */
    public function getFileUrl()
    {
        if ($this->type === 'media' && $this->message) {
            try {
                $seaweedFsService = app(SeaweedFsService::class);

                return $seaweedFsService->createVolumeUrl($this->message);
            } catch (\Exception $e) {
                \Log::warning('Failed to create SeaweedFS URL for chat message', [
                    'message_id' => $this->id,
                    'fid' => $this->message,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return null;
    }

    /**
     * Get file extension from FID for media messages.
     * For SeaweedFS, we'll store the original filename info or extract from MIME type.
     * This is a simplified version - in production you might want to store filename separately.
     *
     * @return string|null
     */
    public function getFileExtension()
    {
        if ($this->type === 'media' && $this->message) {
            return 'jpg'; // Default for media files
        }

        return null;
    }

    /**
     * Check if file is an image.
     * For media messages, we'll assume images unless we have better metadata.
     *
     * @return bool
     */
    public function isImage()
    {
        return $this->type === 'media';
    }

    /**
     * Check if file is a video.
     * For now, we'll return false - in production you'd store MIME type or file metadata.
     *
     * @return bool
     */
    public function isVideo()
    {
        return false; // Simplified for now
    }

    /**
     * Check if file is a document.
     * For now, we'll return false - in production you'd store MIME type or file metadata.
     *
     * @return bool
     */
    public function isDocument()
    {
        return false; // Simplified for now
    }
}
