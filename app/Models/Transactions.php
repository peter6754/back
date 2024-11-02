<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    use HasFactory;

    protected $connection = 'mysql_secondary';
    protected $table = 'transactions';
    public $timestamps = false;
    protected $fillable = [
        'id',
        'user_id',
        'price',
        'type',
        'purchased_at',
        'status'
    ];

    protected $casts = [
        'purchased_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $genders = ['m_f', 'm_m', 'f_f'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return int|mixed
     */
    public static function getTodayTransactionsSumForMen()
    {

        $todayStart = Carbon::now('Europe/Moscow')->startOfDay()->setTimezone('UTC');

        return self::join('users', 'transactions.user_id', '=', 'users.id')
            ->where('users.gender', 'male')
            ->where('transactions.status', 'succeeded')
            ->where('transactions.type', 'subscription_package')
            ->where('transactions.purchased_at', '>=', $todayStart)
            ->selectRaw('SUM(transactions.price) as sum, COUNT(*) as count')
            ->first();
    }

    /**
     * @return int|mixed
     */
    public static function getTodayTransactionsSumForWomen()
    {
        $todayStart = Carbon::now('Europe/Moscow')->startOfDay()->setTimezone('UTC');

        return self::join('users', 'transactions.user_id', '=', 'users.id')
            ->where('users.gender', 'female')
            ->where('transactions.status', 'succeeded')
            ->where('transactions.purchased_at', '>=', $todayStart)
            ->selectRaw('SUM(transactions.price) as sum, COUNT(*) as count')
            ->first();
    }

    /**
     * @return int|mixed
     */
    public static function getYesterdayTransactionsSumForMen()
    {
        $yesterday = Carbon::now('Europe/Moscow')->subDay()->startOfDay()->setTimezone('UTC');
        $todayStart = Carbon::now('Europe/Moscow')->startOfDay()->setTimezone('UTC');

        return self::join('users', 'transactions.user_id', '=', 'users.id')
            ->where('users.gender', 'male')
            ->where('transactions.status', 'succeeded')
            ->whereBetween('transactions.purchased_at', [$yesterday, $todayStart])
            ->selectRaw('SUM(transactions.price) as sum, COUNT(*) as count')
            ->first();
    }

    /**
     * @return int|mixed
     */
    public static function getYesterdayTransactionsSumForWomen()
    {
        $yesterday = Carbon::now('Europe/Moscow')->subDay()->startOfDay()->setTimezone('UTC');
        $todayStart = Carbon::now('Europe/Moscow')->startOfDay()->setTimezone('UTC');

        return self::join('users', 'transactions.user_id', '=', 'users.id')
            ->where('users.gender', 'female')
            ->where('transactions.status', 'succeeded')
            ->whereBetween('transactions.purchased_at', [$yesterday, $todayStart])
            ->selectRaw('SUM(transactions.price) as sum, COUNT(*) as count')
            ->first();
    }

    /**
     * @param array $genders
     * @return int|mixed
     */
    public static function getTodayTransactionsSumForGenders(array $genders)
    {
        $todayStart = Carbon::now('Europe/Moscow')->startOfDay()->setTimezone('UTC');

        return self::join('users', 'transactions.user_id', '=', 'users.id')
            ->whereIn('users.gender', $genders)
            ->where('transactions.status', 'succeeded')
            ->where('transactions.purchased_at', '>=', $todayStart)
            ->selectRaw('SUM(transactions.price) as sum, COUNT(*) as count')
            ->first();
    }

    /**
     * @param array $genders
     * @return int|mixed
     */
    public static function getYesterdayTransactionsSumForGenders(array $genders)
    {
        $yesterday = Carbon::now('Europe/Moscow')->subDay()->startOfDay()->setTimezone('UTC');
        $todayStart = Carbon::now('Europe/Moscow')->startOfDay()->setTimezone('UTC');

        return self::join('users', 'transactions.user_id', '=', 'users.id')
            ->whereIn('users.gender', $genders)
            ->where('transactions.status', 'succeeded')
            ->whereBetween('transactions.purchased_at', [$yesterday, $todayStart])
            ->selectRaw('SUM(transactions.price) as sum, COUNT(*) as count')
            ->first();
    }

    public static function createPayment($price, $product, $customerId)
    {

    }
}
