<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gifts extends Model
{
    use HasFactory;

    protected $connection = 'mysql_secondary';

    protected $table = 'gifts';

    protected $fillable = [
        'image',
        'gategory_id',
        'message'
    ];

    /**
     * Отношение к ценам пакета (один к одному)
     */
    public function price()
    {
        return $this->hasOne(GiftPrice::class, 'gift_id');
    }
}
