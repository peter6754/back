<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoughtSubscriptions extends Model
{
    use CrudTrait;
    use HasFactory;
    protected $connection = 'mysql_secondary';
    protected $table = 'bought_subscriptions';
    public $timestamps = false;
    protected $casts = [
        'due_date' => 'datetime:Y-m-d H:i:s',
    ];
    protected $fillable = ['package_id', 'due_date', 'transaction_id'];
    public function transaction()
    {
        return $this->belongsTo(Transactions::class, 'transaction_id', 'id');
    }

    public function package()
    {
        return $this->belongsTo(SubscriptionPackages::class);
    }
}
