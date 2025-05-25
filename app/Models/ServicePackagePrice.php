<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePackagePrice extends Model
{
    use HasFactory;

    protected $table = 'service_package_prices';

    protected $primaryKey = 'package_id';

    public $incrementing = false;

    protected $fillable = [
        'package_id',
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
        return $this->belongsTo(ServicePackages::class, 'package_id');
    }
}
