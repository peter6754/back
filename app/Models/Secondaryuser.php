<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use App\Services\JwtService;
use Carbon\Carbon;

class Secondaryuser extends Model
{
    use CrudTrait;
    use HasFactory, Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    public $table = 'users';

    /**
     * The attributes that are mass assignable.
     * @var array<int, string>
     */
    protected $fillable = [
        'id', 'name', 'username', 'phone', 'email', 'birth_date', 'lat', 'long',
        'age', 'gender', 'sexual_orientation', 'mode', 'registration_date',
        'last_check', 'is_online', 'last_seen_at', 'is_bot'
    ];

    /**
     * The attributes that should be hidden for serialization.
     * @var array
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     * @var array
     */
    protected $casts = [
        'birth_date' => 'date:Y-m-d',
        'lat' => 'decimal:8',
        'long' => 'decimal:8',
        'is_online' => 'boolean',
        'is_bot' => 'boolean',
        'last_seen_at' => 'datetime',
        'registration_date' => 'datetime',
        'last_check' => 'datetime',
        'bot_genders_for_likes' => 'array',
    ];

    /**
     * The model's default values for attributes.
     * @var array
     */
    protected $attributes = [
        'mode' => 'authenticated',
        'is_online' => false,
    ];

    /**
     * @return Secondaryuser|HasMany
     */
    public function images()
    {
        return $this->hasMany(UserImage::class, 'user_id', 'id');
    }

    /**
     * @return UserImage|null
     */
    public function mainImage(): ?UserImage
    {
        return $this->images()->where('is_main', true)->first();
    }

    /**
     * @return string|null
     */
    public function getMainImageFid(): ?string
    {
        $mainImage = $this->mainImage();
        return $mainImage ? $mainImage->image : null;
    }

    /**
     * @return array
     */
    public function getImageFids(): array
    {
        return $this->images()->pluck('image')->toArray();
    }

    /**
     * @return bool
     */
    public function hasImages(): bool
    {
        return $this->images()->exists();
    }

    /**
     * @return int
     */
    public function getImagesCount(): int
    {
        return $this->images()->count();
    }

    public function verificationRequest()
    {
        return $this->hasOne(VerificationRequests::class, 'user_id', 'id');
    }

    public function verificationImages()
    {
        return $this->hasMany(VerificationRequests::class, 'user_id');
    }


    /**
     * @return Secondaryuser|HasMany
     */
    public function userDeviceTokens()
    {
        return $this->hasMany(UserDeviceToken::class, 'user_id', 'id');
    }

    public function userPreferences()
    {
        return $this->hasMany(UserPreference::class, 'user_id', 'id');
    }

    /**
     * @return Secondaryuser|HasOne
     */
    public function userInformation()
    {
        return $this->hasOne(UserInformation::class, 'user_id', 'id');
    }

    /**
     * @return Secondaryuser|HasOne
     */
    public function userSettings()
    {
        return $this->hasOne(UserSettings::class, 'user_id', 'id');
    }

    public static function getGenderStats(): array
    {
        return [
            'male' => self::where('gender', 'male')->count(),
            'female' => self::where('gender', 'female')->count(),
            'm_f' => self::where('gender', 'm_f')->count(),
            'm_m' => self::where('gender', 'm_m')->count(),
            'f_f' => self::where('gender', 'f_f')->count(),
            'total' => self::count(),
        ];
    }

    public function activeSubscription()
    {
        return $this->hasOneThrough(
            BoughtSubscriptions::class,
            Transactions::class,
            'user_id',
            'transaction_id',
            'id',
            'id'
        )
            ->whereHas('transaction', fn($q) => $q->where('status', 'succeeded'))
            ->where('due_date', '>', now())
            ->orderByDesc('due_date')
            ->with(['package.subscription']);
    }

    // Зависимость возраста от даты рождения и наоборот.
    // Мутатор для возраста
//    public function setAgeAttribute($value)
//    {
//        if ($value) {
//            $this->attributes['age'] = $value;
//            $this->attributes['birth_date'] = Carbon::now()->subYears($value)->format('Y-m-d');
//        }
//    }

    // Мутатор для даты рождения.
    public function setBirthDateAttribute($value)
    {
        if ($value) {
            $this->attributes['birth_date'] = $value;
            $this->attributes['age'] = Carbon::parse($value)->age;
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($user) {
            if ($user->isDirty('birth_date')) {
                $user->forceFill([
                    'age' => Carbon::parse($user->birth_date)->age,
                ]);
            }

            // Test fix birth_date bug
//            if ($user->isDirty('age')) {
//                $user->forceFill([
//                    'birth_date' => Carbon::now()->subYears($user->age)->format('Y-m-d'),
//                ]);
//            }
        });

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the user's gender options.
     * @return array
     */
    public static function getGenderOptions(): array
    {
        return ['male', 'female', 'm_f', 'm_m', 'f_f'];
    }

    /**
     * Get the user's sexual orientation options.
     * @return array
     */
    public static function getSexualOrientationOptions(): array
    {
        return ['hetero', 'gay', 'lesbian', 'bisexual', 'asexual', 'not_decided'];
    }

    /**
     * Get the user's mode options.
     * @return array
     */
    public static function getModeOptions(): array
    {
        return ['authenticated', 'deleted', 'inQueueForDelete'];
    }

    /**
     * Scope a query to only include online users.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    /**
     * Scope a query to only include bots.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBots($query)
    {
        return $query->where('is_bot', true);
    }

    /**
     * Get the queue for delete associated with the user.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function queueForDelete()
    {
        return $this->belongsTo(InQueueForDeleteUser::class, 'in_queue_for_delete_user_id', 'user_id');
    }

    /**
     * Get the user's settings.
     * @return HasOne
     */
    public function settings()
    {
        return $this->hasOne(UserSettings::class, 'user_id', 'id');
    }

    /**
     * Get the user's preferences.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function preferences()
    {
        return $this->hasMany(UserPreference::class, 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function interests()
    {
        return $this->hasMany(UserInterests::class, 'user_id');
    }

    /**
     * @return HasOne
     */
    public function relationshipPreference()
    {
        return $this->hasOne(UserRelationshipPreference::class, 'user_id', 'id');
    }

    /**
     * Get the user's conversations where they are user1.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function conversationsAsUser1()
    {
        return $this->hasMany(Conversation::class, 'user1_id', 'id');
    }

    /**
     * Get the user's conversations where they are user2.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function conversationsAsUser2()
    {
        return $this->hasMany(Conversation::class, 'user2_id', 'id');
    }

    /**
     * Get all conversations for the user.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function conversations()
    {
        return Conversation::where(function ($query) {
            $query->where('user1_id', $this->id)
                ->orWhere('user2_id', $this->id);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function connectedAccounts()
    {
        return $this->hasMany(ConnectedAccount::class, 'user_id', 'id');
    }

    /**
     * @param string $provider
     * @param string|null $email
     * @return bool
     */
    public function hasConnectedAccount(string $provider, string $email = null): bool
    {
        $query = $this->connectedAccounts()
            ->where('provider', $provider);

        if ($email) {
            $query->where('email', $email);
        }

        return $query->exists();
    }

    /**
     * @param array $data
     * @return ConnectedAccount
     */
    public function addConnectedAccount(array $data): ConnectedAccount
    {
        return $this->connectedAccounts()->create([
            'email' => $data['email'],
            'provider' => $data['provider'],
            'name' => $data['name']
        ]);
    }

    /**
     * @return HasOneThrough
     */
    public function finalPreference(): HasOneThrough
    {
        return $this->hasOneThrough(
            RelationshipPreferences::class,
            UserRelationshipPreference::class,
            'user_id',
            'id',
            'id',
            'preference_id'
        );
    }

    /**
     * @return HasMany
     */
    public function receivedGifts()
    {
        return $this->hasMany(UserGifts::class, 'receiver_id');
    }

    /**
     * @return HasMany
     */
    public function feedbacks()
    {
        return $this->hasMany(UserFeedbacks::class, 'user_id');
    }

    /**
     * @return HasOne
     */
    public function city()
    {
        return $this->hasOne(UserCities::class, 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pets()
    {
        return $this->hasMany(UserPets::class, 'user_id');
    }

    public function sentReactions()
    {
        return $this->hasMany(UserReaction::class, 'user_id');
    }
}
