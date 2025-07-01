<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscriptions extends Model
{
    use HasFactory;
    protected $table = 'subscriptions';
    protected $fillable = ['type'];

    public function services()
    {
        return $this->hasMany(SubscriptionService::class, 'subscription_id');
    }

    public function packages()
    {
        return $this->hasMany(SubscriptionPackages::class, 'subscription_id');
    }
}
