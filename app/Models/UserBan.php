<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class UserBan extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'is_permanent',
        'banned_until',
        'reason',
    ];

    protected $casts = [
        'is_permanent' => 'boolean',
        'banned_until' => 'datetime',
    ];
    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    protected $keyType = 'string';

    /**
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Secondaryuser::class, 'user_id');
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        if ($this->is_permanent) {
            return true;
        }

        return $this->banned_until && $this->banned_until->isFuture();
    }

    /**
     * Scope для активных банов
     *
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('is_permanent', true)
                ->orWhere(function ($q2) {
                    $q2->where('is_permanent', false)
                        ->where('banned_until', '>', Carbon::now());
                });
        });
    }
}
