<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConnectedAccount extends Model
{
    protected $table = 'connected_accounts';

    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'email',
        'provider',
        'name'
    ];

    protected $casts = [
        'provider' => 'string',
    ];

    public $timestamps = false;

    /**
     * Отношение к пользователю
     */
    public function user()
    {
        return $this->belongsTo(Secondaryuser::class, 'user_id', 'id');
    }

    /**
     * Получить список поддерживаемых провайдеров
     */
    public static function getSupportedProviders(): array
    {
        return ['google', 'facebook', 'vkontakte', 'apple'];
    }
}
