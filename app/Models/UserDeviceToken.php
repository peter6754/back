<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

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
     * @param string $user_id
     * @return array
     */
    public static function getPushTokens(string $user_id = ""): array
    {
        // User not found
        if (empty($user_id)) {
            return [];
        }

        // Get user token
        $getTokens = static::select(['token'])->where([
            'user_id' => $user_id
        ])->get();
        $pushTokens = [];

        // Draw data
        if (!empty($getTokens)) {
            foreach ($getTokens as $token) {
                $pushTokens[] = $token->token;
            }
        }

        // Response
        return $pushTokens;
    }

    /**
     * Add a new device token for a user.
     * @param string $userId
     * @param array $query
     * @return bool
     */
    public static function addToken(string $userId, array $query): bool
    {
        // Значения по умолчанию
        $attributes = [
            'token' => $query['token'],
            'user_id' => $userId
        ];

        // Сначала пытаемся обновить
        if (static::where($attributes)->exists()) {
            return static::where($attributes)->update($query);
        }

        // Если нет - создаем
        return (bool)static::create(
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
     * @return array
     */
    public function getKey(): array
    {
        return [
            'user_id' => $this->getAttribute('user_id'),
            'token' => $this->getAttribute('token')
        ];
    }

    /**
     * @return string[]
     */
    public function getKeyName(): array
    {
        return ['user_id', 'token'];
    }
}
