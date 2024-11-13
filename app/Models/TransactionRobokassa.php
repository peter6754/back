<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TransactionRobokassa extends Model
{
    use HasFactory;

    protected $connection = 'mysql_secondary';

    /**
     * Название таблицы в базе данных
     */
    protected $table = 'transactions_robokassa';

    /**
     * Первичный ключ таблицы
     */
    protected $primaryKey = 'invId';

    /**
     * Указывает, что первичный ключ - автоинкрементный
     */
    public $incrementing = true;

    /**
     * Тип первичного ключа
     */
    protected $keyType = 'int';

    /**
     * Поля, которые можно массово назначать
     */
    protected $fillable = [
        'id' // только 'id', так как 'invId' автоинкрементный
    ];

    /**
     * Отключение временных меток, если их нет в таблице
     */
    public $timestamps = false;

    /**
     * Формат атрибутов для сериализации
     */
    protected $casts = [
        'invId' => 'integer',
        'id' => 'string'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }
}
