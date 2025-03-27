<?php

namespace App\Models;

use Carbon\Carbon;
use DB;
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

    public static function getLastCheckStats()
    {
        $yesterday = Carbon::now('Europe/Moscow')->subDay()->startOfDay()->setTimezone('UTC');
        $todayStart = Carbon::now('Europe/Moscow')->startOfDay()->setTimezone('UTC');
        $todayEnd = Carbon::today()->endOfDay();

        $todayCount = self::whereBetween(
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(user_data, '$.last_check'))"),
            [$todayStart, $todayEnd]
        )->count();

        $yesterdayCount = self::whereBetween(
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(user_data, '$.last_check'))"),
            [$yesterday, $todayStart]
        )->count();

        return [
            'today' => $todayCount,
            'yesterday' => $yesterdayCount,
        ];
    }
}
