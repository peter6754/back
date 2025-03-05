<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInformation extends Model
{
    use HasFactory;

    protected $table = 'user_information';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'bio',
        'zodiac_sign',
        'education',
        'educational_institution',
        'family',
        'communication',
        'love_language',
        'alcohole',
        'smoking',
        'sport',
        'food',
        'social_network',
        'sleep',
        'family_status',
        'role',
        'company',
        'streak',
        'superlikes',
        'superbooms',
        'superboom_due_date',
        'like_update',
        'last_banner'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(Secondaryuser::class, 'id', 'user_id');
    }
}

