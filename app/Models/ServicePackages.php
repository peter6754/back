<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePackages extends Model
{
    use HasFactory;

    protected $table = 'service_packages';

    protected $fillable = [
        'type',
        'count',
        'stock',
        'is_bestseller'
    ];

    protected $casts = [
        'is_bestseller' => 'boolean',
    ];

    /**
     * Отношение к ценам пакета (один к одному)
     */
    public function price()
    {
        return $this->hasOne(ServicePackagePrice::class, 'package_id');
    }
}
