<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
