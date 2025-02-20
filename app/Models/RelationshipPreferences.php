<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelationshipPreferences extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'relationship_preferences';

    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'preference',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    public function userRelationshipPreference()
    {
        return $this->hasMany(UserRelationshipPreference::class, 'preference_id', 'id');
    }
}
