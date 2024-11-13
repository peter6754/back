<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftPrice extends Model
{
    use HasFactory;

    protected $connection = 'mysql_secondary';

    protected $table = 'gift_prices';

    protected $primaryKey = 'gift_id';

    public $incrementing = false;

    protected $fillable = [
        'gift_id',
        'male',
        'female',
        'm_f',
        'm_m',
        'f_f'
    ];

    public $timestamps = false;

    protected $casts = [
        'male' => 'decimal:8',
        'female' => 'decimal:8',
        'm_f' => 'decimal:8',
        'm_m' => 'decimal:8',
        'f_f' => 'decimal:8',
    ];

    /**
     * Отношение к пакету услуг
     */
    public function package()
    {
        return $this->belongsTo(Gifts::class, 'gift_id');
    }
}
