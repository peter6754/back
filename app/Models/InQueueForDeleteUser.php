<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class InQueueForDeleteUser extends Model
{
    use CrudTrait;

    protected $connection = 'mysql_secondary';
    protected $table = 'in_queue_for_delete_user';
    protected $keyType = 'string';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'user_id', 'date', 'is_date_delete',
    ];

    public function user()
    {
        return $this->belongsTo(Secondaryuser::class, 'user_id');
    }

    public function images()
    {
        return $this->hasMany(UserImage::class, 'user_id');
    }

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

    public function getTimeLeftToDelete()
    {
        $now = Carbon::now('Europe/Moscow');
        $deletionDate = Carbon::parse($this->date);

        if ($now->greaterThanOrEqualTo($deletionDate)) {
            return 'Удаление возможно';
        }

        $diff = $now->diffAsCarbonInterval($deletionDate)->cascade();

        return $diff->forHumans([
            'parts' => 3,
            'join' => true,
            'short' => false,
            'syntax' => CarbonInterface::DIFF_ABSOLUTE,
        ]);
    }

    public function getDateQueuedForDeletion()
    {
        return Carbon::parse($this->date)->subDays(10)->toDateTimeString();
    }
}
