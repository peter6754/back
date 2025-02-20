<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInterests extends Model
{

    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'user_interests';

    /**
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'interest_id',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(Secondaryuser::class, 'id', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function interest()
    {
        return $this->belongsTo(Interests::class, 'interest_id', 'id');
    }


}
