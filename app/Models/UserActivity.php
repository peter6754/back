<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    protected $connection = 'mysql_secondary';
    protected $table = 'user_activity';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'last_activity',
        'session_start',
        'session_end',
        'id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getTodayOnlineMen()
    {

        $todayStart = Carbon::now('Europe/Moscow')->startOfDay()->setTimezone('UTC');

        return self::join('users', 'user_activity.user_id', '=', 'users.id')
            ->where('users.gender', 'male')
            ->where('user_activity.session_start', '>=', $todayStart)
            ->distinct('user_id')
            ->count('user_id');
    }

    public static function getTodayOnlineWomen()
    {

        $todayStart = Carbon::now('Europe/Moscow')->startOfDay()->setTimezone('UTC');

        return self::join('users', 'user_activity.user_id', '=', 'users.id')
            ->where('users.gender', 'female')
            ->where('user_activity.session_start', '>=', $todayStart)
            ->distinct('user_id')
            ->count('user_id');
    }

    public static function getTodayOnlineTotal()
    {

        $todayStart = Carbon::now('Europe/Moscow')->startOfDay()->setTimezone('UTC');

        return self::join('users', 'user_activity.user_id', '=', 'users.id')
            ->where('user_activity.session_start', '>=', $todayStart)
            ->distinct('user_id')
            ->count('user_id');
    }
}
