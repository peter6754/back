<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use CrudTrait;
    use HasApiTokens, HasFactory, Notifiable;


    protected $keyType = 'string'; // Указываем тип ключа
    public $incrementing = false; // Отключаем автоинкремент
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id', 'name', 'username', 'phone', 'email', 'birth_date', 'lat', 'long',
        'age', 'gender', 'sexual_orientation', 'mode', 'registration_date',
        'last_check', 'is_online',
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'registration_date' => 'datetime',
        'last_check' => 'datetime',
        'lat' => 'decimal:8',
        'long' => 'decimal:8',
        'is_online' => 'boolean',
    ];
}
