<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionService extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'subscription_services';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'description',
        'image',
        'subscription_id'
    ];

    /**
     * Get the subscription that owns the service.
     */
    public function subscription()
    {
        return $this->belongsTo(Subscriptions::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array
     */
    protected function casts()
    {
        return [
            'description' => 'string',
            'image' => 'string',
        ];
    }
}
