<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoughtSubscriptions extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'bought_subscriptions';
    public $timestamps = false;
    protected $casts = [
        'due_date' => 'datetime:Y-m-d H:i:s',
    ];
    protected $fillable = ['package_id', 'due_date', 'transaction_id'];
    public function transaction()
    {
        return $this->belongsTo(Transactions::class, 'transaction_id', 'id');
    }

    public function package()
    {
        return $this->belongsTo(SubscriptionPackages::class);
    }

    public static function getExpiredSubscriptionsStats($dateStart, $dateEnd)
    {

        return self::join('transactions', 'transactions.id', '=', 'bought_subscriptions.transaction_id')
            ->join('users', 'transactions.user_id', '=', 'users.id')
            ->whereBetween('bought_subscriptions.due_date', [$dateStart, $dateEnd])
            ->selectRaw("
            SUM(CASE WHEN users.gender = 'male' THEN transactions.price ELSE 0 END) as total_sum_men,
            COUNT(CASE WHEN users.gender = 'male' THEN bought_subscriptions.id ELSE NULL END) as count_men,
            SUM(CASE WHEN users.gender = 'female' THEN transactions.price ELSE 0 END) as total_sum_women,
            COUNT(CASE WHEN users.gender = 'female' THEN bought_subscriptions.id ELSE NULL END) as count_women
        ")
            ->first();
    }
}
