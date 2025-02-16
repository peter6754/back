<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    use HasFactory;
    protected $connection = 'mysql_secondary';
    protected $table = 'transactions';
    public $timestamps = false;
    protected $fillable = [
        'id',
        'user_id',
        'price',
        'type',
        'purchased_at',
        'status'
    ];

    protected $casts = [
        'purchased_at' => 'datetime:Y-m-d H:i:s',
    ];
}
