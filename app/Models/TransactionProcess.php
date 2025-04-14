<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Services\Payments\PaymentsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionProcess extends Model
{
    use CrudTrait;
    use HasFactory;

    // Указываем таблицу
    protected $table = 'transactions_process';

    // Поля, которые можно массово назначать
    protected $fillable = [
        'id',
        'subscription_id',
        'transaction_id',
        'subscriber_id',
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

    /**
     * @param $idWhere
     * @param  bool  $recurrent
     * @return array
     */
    public function transactionInfo($idWhere, bool $recurrent = false): array
    {
        try {
            $getTransaction = DB::table('transactions_process as t')
                ->select([
                    't.transaction_id as transaction_id',
                    't.id as increment_id',
                    't.type',
                    't.user_id',
                    't.email as user_email',

                    // Данные або (если есть)
                    't.subscription_id as service_subscription_id',
                    't.subscriber_id as service_subscriber_id',

                    // Данные о сервисном пакете
                    'p.type as package_type',
                    'p.count as package_count',

                    // Данные о подписке
                    'sp.id as subscription_id',
                    'sp.term as subscription_term',

                    // Данные о подарке
                    'g.sender_id as gift_sender_id',
                    'g.receiver_id as gift_receiver_id',
                    'g.gift_id',

                    // Токены устройства получателя подарка (подзапрос)
                    DB::raw('(
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
                ->orderBy('t.created_at', 'desc');

            if ($recurrent === true) {
                $getTransaction->where(function ($query) use ($idWhere) {
                    $query->where('t.type', PaymentsService::ORDER_PRODUCT_SUBSCRIPTION)
//                        ->where('t.status', PaymentsService::ORDER_STATUS_COMPLETE)
                        ->where('t.email', $idWhere);
                });
            } else {
                $getTransaction->where(function ($query) use ($idWhere) {
                    $query->where('t.transaction_id', $idWhere)
                        ->orWhere('t.id', (int) $idWhere);
                });
            }

            return (array) $getTransaction->firstOrFail();
        } catch (\Exception $e) {
            return [];
        }
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
        );
    }

    /**
     * Получение транзакций пользователя для API с пагинацией
     * @param $user
     * @param  int  $page
     * @param  int  $perPage
     * @return array
     */
    public function getUserTransactions($user, int $page = 1, int $perPage = 7): array
    {
        $transactions = self::where('user_id', $user['id'])
            ->with(['boughtSubscription.package']) // Загружаем вложенные отношения
            ->orderBy('purchased_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'type' => $transaction->type,
                    'type_label' => $this->getTypeLabel($transaction->type),
                    'provider' => $transaction->provider,
                    'amount' => (float) $transaction->price,
                    'currency' => 'USD',
                    'date' => $transaction->purchased_at?->toISOString(),
                    'date_display' => $transaction->purchased_at?->format('M j, Y H:i'),
                    'subscription' => $transaction->boughtSubscription ? [
                        'package_name' => $transaction->boughtSubscription->package->name ?? 'Unknown',
                        'term' => $transaction->boughtSubscription->term,
                    ] : null,
                ];
            }),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'total_pages' => $transactions->lastPage(),
                'has_more' => $transactions->hasMorePages(),
                'next_page' => $transactions->nextPageUrl(),
                'prev_page' => $transactions->previousPageUrl(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ],
            'summary' => [
                'total_amount' => $transactions->sum('price'),
                'subscription_count' => $transactions->where('type',
                    PaymentsService::ORDER_PRODUCT_SUBSCRIPTION)->count(),
                'package_count' => $transactions->where('type', PaymentsService::ORDER_PRODUCT_SERVICE)->count(),
                'gift_count' => $transactions->where('type', PaymentsService::ORDER_PRODUCT_GIFT)->count(),
            ]
        ];
    }

    /**
     * Получение читаемого названия типа транзакции
     */
    protected function getTypeLabel(?string $type): string
    {
        $types = [
            PaymentsService::ORDER_PRODUCT_SUBSCRIPTION => 'Subscription',
            PaymentsService::ORDER_PRODUCT_SERVICE => 'Service Package',
            PaymentsService::ORDER_PRODUCT_GIFT => 'Gift',
            // Добавьте другие типы по необходимости
        ];

        return $types[$type] ?? 'Unknown';
    }

    /**
     * Получение читаемого названия статуса
     */
    protected function getStatusLabel(string $status): string
    {
        $statuses = [
            'succeeded' => 'Completed',
            'completed' => 'Completed',
            'pending' => 'Pending',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            'canceled' => 'Canceled',
        ];

        return $statuses[$status] ?? ucfirst($status);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function boughtSubscription()
    {
        return $this->hasOne(BoughtSubscriptions::class, 'transaction_id', 'transaction_id');
    }

    /**
     * @return array
     */
    public static function getTodaySubscriptionsStats(): array
    {
        $result = self::where('status', 'succeeded')
            ->where('type', PaymentsService::ORDER_PRODUCT_SUBSCRIPTION)
            ->where('price', '>', 0)
            ->whereDate('purchased_at', now()->toDateString())
            ->selectRaw('COUNT(*) as count, SUM(price) as total')
            ->first();

        return [
            'total' => (float) $result->total,
            'count' => (int) $result->count
        ];
    }

    /**
     * Генерация transaction_id при создании
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->transaction_id)) {
                $model->transaction_id = Str::uuid()->toString();
            }
        });
    }
}