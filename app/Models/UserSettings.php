<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSettings extends Model
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
    protected $table = 'user_settings';

    /**
     * The primary key for the model.
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * The "type" of the primary key ID.
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Disable auto increment
     * @var bool
     */
    public $timestamps = false;

    /**
     * Indicates if the IDs are auto-incrementing.
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'user_id',
        'login_with_apple',
        'login_with_facebook',
        'login_with_google',
        'login_with_vk',
        'top_profiles',
        'status_seen',
        'status_online',
        'status_recently_active',
        'new_couples_push',
        'new_messages_push',
        'new_likes_push',
        'new_super_likes_push',
        'new_couples_email',
        'new_messages_email',
        'is_global_search',
        'show_my_orientation',
        'show_my_gender',
        'show_me_on_finder',
        'show_my_age',
        'show_distance_from_me',
        'is_phone_verified',
        'is_email_verified',
        'search_radius',
        'age_range',
        'visibility',
        'recommendations'
    ];

    /**
     * The attributes that should be cast.
     * @var array
     */
    protected $casts = [
        'login_with_apple' => 'boolean',
        'login_with_facebook' => 'boolean',
        'login_with_google' => 'boolean',
        'login_with_vk' => 'boolean',
        'top_profiles' => 'boolean',
        'status_seen' => 'boolean',
        'status_online' => 'boolean',
        'status_recently_active' => 'boolean',
        'new_couples_push' => 'boolean',
        'new_messages_push' => 'boolean',
        'new_likes_push' => 'boolean',
        'new_super_likes_push' => 'boolean',
        'new_couples_email' => 'boolean',
        'new_messages_email' => 'boolean',
        'is_global_search' => 'boolean',
        'show_my_orientation' => 'boolean',
        'show_my_gender' => 'boolean',
        'show_me_on_finder' => 'boolean',
        'show_my_age' => 'boolean',
        'show_distance_from_me' => 'boolean',
        'is_phone_verified' => 'boolean',
        'is_email_verified' => 'boolean',
        'search_radius' => 'integer',
    ];

    /**
     * Get the user associated with the settings.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(Secondaryuser::class, 'user_id', 'id');
    }

    /**
     * Scope a query to only include users with phone verified.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePhoneVerified($query)
    {
        return $query->where('is_phone_verified', true);
    }

    /**
     * Scope a query to only include users with email verified.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEmailVerified($query)
    {
        return $query->where('is_email_verified', true);
    }

    /**
     * Get the age range as an array.
     * @return array
     */
    public function getAgeRangeAttribute()
    {
        return array_map('intval', explode('-', $this->attributes['age_range']));
    }

    /**
     * Set the age range.
     * @param array $value
     * @return void
     */
    public function setAgeRangeAttribute(array $value)
    {
        $this->attributes['age_range'] = implode('-', $value);
    }
}
