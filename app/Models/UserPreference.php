<?php
// app/Models/UserPreference.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'user_preferences';

    /**
     * The primary key for the model.
     * @var string
     */
    protected $primaryKey = null;

    /**
     * Indicates if the model's ID is auto-incrementing.
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'user_id',
        'gender',
    ];

    /**
     * The attributes that should be cast.
     * @var array
     */
    protected $casts = [
        'gender' => 'string',
    ];

    /**
     * Get the user that owns the preference.
     */
    public function user()
    {
        return $this->belongsTo(Secondaryuser::class, 'user_id', 'id');
    }

    /**
     * Get the gender options.
     * @return array
     */
    public static function getGenderOptions(): array
    {
        return [
            'male' => 'Male',
            'female' => 'Female',
            'm_f' => 'Male and Female',
            'm_m' => 'Male and Male',
            'f_f' => 'Female and Female',
        ];
    }
}
