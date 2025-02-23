<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRelationshipPreference extends Model
{

    protected $table = 'user_relationship_preferences';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'preference_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(Secondaryuser::class, 'id', 'user_id');
    }

    public function relationshipPreference()
    {
        return $this->belongsTo(RelationshipPreferences::class, 'preference_id', 'id');
    }

}
