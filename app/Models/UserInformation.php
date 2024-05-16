<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInformation extends Model
{
    use HasFactory;
    protected $connection = 'mysql_secondary';
    protected $table = 'user_information';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
