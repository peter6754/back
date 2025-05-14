<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPackagePrices extends Model
{
    use HasFactory;

    protected $table = 'subcription_package_prices';

    protected $fillable = [
        'package_id',
        'male',
        'female',
        'm_f',
        'm_m',
        'f_f',
    ];

    protected $casts = [
        'male' => 'decimal:8',
        'female' => 'decimal:8',
        'm_f' => 'decimal:8',
        'm_m' => 'decimal:8',
        'f_f' => 'decimal:8',
    ];

    /**
     * Отношение к пакету подписки
     */
    public function package()
    {
        return $this->belongsTo(SubscriptionPackages::class, 'package_id');
    }
}
