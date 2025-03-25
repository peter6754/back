<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletedUserHimself extends Model
{
    protected $connection = 'mysql_secondary';
    protected $table = 'deleted_user_himself';

    /**
     * @return int
     */
    public static function countDeletedUsers()
    {
        return self::count();
    }
}
