<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TransactionProcess extends Model
{
    use HasFactory;

    // Указываем соединение с базой данных
    protected $connection = 'mysql_secondary';

    // Указываем таблицу
    protected $table = 'transactions_process';

    // Поля, которые можно массово назначать
    protected $fillable = [
        'id',
        'subscription_id',
        'transaction_id',
        'purchased_at',
        'provider',
        'user_id',
        'price',
        'email',
        'type'
    ];

    // Приведение типов
    protected $casts = [
        'price' => 'double',
        'created_at' => 'datetime:Y-m-d H:i:s.v',
        'updated_at' => 'datetime:Y-m-d H:i:s.v',
        'purchased_at' => 'datetime:Y-m-d H:i:s.v',
    ];

    // Отключаем автоинкремент для первичного ключа (так как он уже определен в миграции)
    public $incrementing = false;

    // Тип первичного ключа
    protected $keyType = 'int';

    public function transactionInfo($transactionId)
    {
        return DB::connection('mysql_secondary')
            ->table('transactions_process as t')
            ->select([
                't.type',
                't.user_id',
                't.email as user_email',

                // Данные о сервисном пакете
                'p.type as package_type',
                'p.count as package_count',

                // Данные о подписке
                'sp.subscription_id',
                'sp.term as subscription_term',

                // Данные о подарке
                'g.sender_id as gift_sender_id',
                'g.receiver_id as gift_receiver_id',
                'g.gift_id',

                // Токены устройства получателя подарка (подзапрос)
                DB::connection('mysql_secondary')->raw('(
                        SELECT JSON_ARRAYAGG(dt.token)
                        FROM user_device_tokens dt
                        WHERE dt.user_id = g.receiver_id
                    ) as receiver_device_tokens'
                )
            ])
            ->leftJoin('bought_service_packages as spkg', 'spkg.transaction_id', '=', 't.transaction_id')
            ->leftJoin('service_packages as p', 'spkg.package_id', '=', 'p.id')
            ->leftJoin('bought_subscriptions as sub', 'sub.transaction_id', '=', 't.transaction_id')
            ->leftJoin('subscription_packages as sp', 'sub.package_id', '=', 'sp.id')
            ->leftJoin('user_gifts as g', 'g.transaction_id', '=', 't.transaction_id')
            ->where(function ($query) use ($transactionId) {
                $query->where('t.transaction_id', $transactionId)
                    ->orWhere('t.id', (int)$transactionId);
            })
            ->first();
    }

    /**
     * Отношение к пользователю
     */
    public function user()
    {
        return $this->belongsTo(
            \App\Models\Secondaryuser::class,
            'user_id',
            'id'
        )->setConnection('mysql_secondary');
    }

    /**
     * Генерация transaction_id при создании
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->transaction_id)) {
                $model->transaction_id = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }
}
