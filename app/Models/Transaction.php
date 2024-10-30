<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /**
     * The database associated with the model.
     *
     * @var string
     */
    protected $connection = 'mysql_secondary';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'user_id',
        'price',
        'type',
        'purchased_at',
        'status'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'double',
        'purchased_at' => 'datetime:Y-m-d H:i:s.v',
    ];

    /**
     * The possible values for the type enum.
     *
     * @var array
     */
    public const TYPES = [
        'service_package',
        'subscription_package',
        'gift'
    ];

    /**
     * The possible values for the status enum.
     *
     * @var array
     */
    public const STATUSES = [
        'pending',
        'succeeded',
        'canceled'
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
