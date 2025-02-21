<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDeviceToken extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'user_device_tokens';

    /**
     * The primary key for the model.
     * @var string
     */
    protected $primaryKey = ['user_id', 'token'];
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'application',
        'user_id',
        'device',
        'token'
    ];

    /**
     * Indicates if the model should be timestamped.
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the user that owns the device token.
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Secondaryuser::class, 'user_id');
    }

    /**
     * Find device tokens by user ID.
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findByUserId(string $userId)
    {
        return static::where('user_id', $userId)->get();
    }

    /**
     * Add a new device token for a user.
     * @param string $userId
     * @param array $query
     * @return UserDeviceToken
     */
    public static function addToken(string $userId, array $query): UserDeviceToken
    {
        // Значения по умолчанию
        $attributes = [
            'token' => $query['token'],
            'user_id' => $userId
        ];

        // Сначала пытаемся обновить
        if (static::where($attributes)->exists()) {
            static::where($attributes)->update($query);
            return static::where($attributes)->first();
        }

        // Если нет - создаем
        return static::create(
            array_merge(
                $attributes,
                $query
            )
        );
    }

    /**
     * Remove a device token.
     * @param string $userId
     * @param array $query
     * @return bool
     */
    public static function removeToken(string $userId, array $query): bool
    {
        return (bool)static::where('user_id', $userId)
            ->where('token', $query['token'])
            ->delete();
    }

    /**
     * Remove all device tokens for a user.
     * @param string $userId
     * @return bool
     */
    public static function removeAllTokens(string $userId): bool
    {
        return (bool)static::where('user_id', $userId)->delete();
    }
}
