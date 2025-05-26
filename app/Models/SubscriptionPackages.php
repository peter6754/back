<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPackages extends Model
{
    use HasFactory;
    protected $table = 'subscription_packages';

    protected $fillable = ['id', 'subscription_id', 'term'];
    public function getDescriptionAttribute()
    {
        $translations = [
            'one_month' => '1 месяц',
            'six_months' => '6 месяцев',
            'year' => '1 год',
        ];

        $term = isset($translations[$this->term]) ? $translations[$this->term] : $this->term;

        return $this->subscription->type . ' (' . $term . ')';
    }

    public function price()
    {
        return $this->hasOne(SubscriptionPackagePrices::class, 'package_id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscriptions::class, 'subscription_id', 'id'); // Указываем явное соответствие ключей
    }
}
