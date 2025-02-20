<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPets extends Model
{
    use HasFactory;
    protected $table = 'user_pets';
    public $incrementing = false;
    protected $fillable = ['user_id', 'pet'];
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(Secondaryuser::class, 'id', 'user_id');
    }
}
