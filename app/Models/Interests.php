<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Interests extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'interests';

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'club_name',
        'image',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(
            Secondaryuser::class,
            'user_interests',
            'interest_id',
            'user_id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userInterests()
    {
        return $this->hasMany(UserInterests::class, 'interest_id', 'id');
    }
    //
}
