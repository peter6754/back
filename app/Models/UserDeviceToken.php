<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDeviceToken extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_device_tokens';

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
        'user_id',
        'token'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the user that owns the device token.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Find device tokens by user ID.
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findByUserId(string $userId)
    {
        return static::where('user_id', $userId)->get();
    }

    /**
     * Add a new device token for a user.
     *
     * @param string $userId
     * @param string $token
     * @return UserDeviceToken
     */
    public static function addToken(string $userId, string $token): UserDeviceToken
    {
        return static::firstOrCreate([
            'user_id' => $userId,
            'token' => $token
        ]);
    }

    /**
     * Remove a device token.
     *
     * @param string $userId
     * @param string $token
     * @return bool
     */
    public static function removeToken(string $userId, string $token): bool
    {
        return (bool) static::where('user_id', $userId)
            ->where('token', $token)
            ->delete();
    }

    /**
     * Remove all device tokens for a user.
     *
     * @param string $userId
     * @return bool
     */
    public static function removeAllTokens(string $userId): bool
    {
        return (bool) static::where('user_id', $userId)->delete();
    }
}
