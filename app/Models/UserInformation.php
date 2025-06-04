<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInformation extends Model
{
    use HasFactory;

    protected $table = 'user_information';

    public $incrementing = false;
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(Secondaryuser::class, 'user_id', 'id');
    }
}
