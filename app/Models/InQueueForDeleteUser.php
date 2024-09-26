<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class InQueueForDeleteUser extends Model
{
    protected $connection = 'mysql_secondary';
    protected $table = 'in_queue_for_delete_user';

    public static function countDeletedUsers()
    {

        $today = Carbon::now('Europe/Moscow')->addDays(10)->toDateString();
        $yesterday = Carbon::yesterday('Europe/Moscow')->addDays(10)->toDateString();

        $todayCount = self::whereDate('date', $today)->count();

        $yesterdayCount = self::whereDate('date', $yesterday)->count();

        return [
            'today' => $todayCount,
            'yesterday' => $yesterdayCount,
        ];
    }
}
