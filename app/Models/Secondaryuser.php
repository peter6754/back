<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Carbon\Carbon;

class Secondaryuser extends Model
{
    use CrudTrait;
    use HasFactory, Notifiable;

    protected $connection = 'mysql_secondary';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    public $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id', 'name', 'username', 'phone', 'email', 'birth_date', 'lat', 'long',
        'age', 'gender', 'sexual_orientation', 'mode', 'registration_date',
        'last_check', 'is_online',
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'registration_date' => 'datetime',
        'last_check' => 'datetime',
        'lat' => 'decimal:8',
        'long' => 'decimal:8',
        'is_online' => 'boolean',
    ];

    public function images()
    {
        return $this->hasMany(UserImage::class, 'user_id');
    }

    public function verificationRequest()
    {
        return $this->hasOne(VerificationRequests::class, 'user_id', 'id');
    }

    public function verificationImages()
    {
        return $this->hasMany(VerificationRequests::class, 'user_id');
    }

    public function userInformation()
    {
        return $this->hasOne(UserInformation::class, 'user_id', 'id');
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

            if ($user->isDirty('age')) {
                $user->forceFill([
                    'birth_date' => Carbon::now()->subYears($user->age)->format('Y-m-d'),
                ]);
            }
        });
    }

}
