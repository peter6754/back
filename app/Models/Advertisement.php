<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Advertisement extends Model
{
    use CrudTrait;

    use HasFactory;

    protected $fillable = [
        'title',
        'link',
        'impressions_limit',
        'impressions_count',
        'start_date',
        'end_date',
        'is_active',
        'order',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'impressions_limit' => 'integer',
        'impressions_count' => 'integer',
        'order' => 'integer',
    ];

    /**
     * @return HasMany
     */
    public function images(): HasMany
    {
        return $this->hasMany(AdvertisementImage::class)->orderBy('order');
    }

    /**
     * @return Advertisement|\Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function primaryImage()
    {
        return $this->hasOne(AdvertisementImage::class)->where('is_primary', true);
    }

    /**
     * @return bool
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->start_date && $this->start_date->greaterThan($now)) {
            return false;
        }

        if ($this->end_date && $this->end_date->lessThan($now)) {
            return false;
        }

        if ($this->impressions_limit > 0 && $this->impressions_count >= $this->impressions_limit) {
            return false;
        }

        return true;
    }

    /**
     * Увеличить счетчик показов
     * @return void
     */
    public function incrementImpressions(): void
    {
        $this->increment('impressions_count');
    }

    /**
     * Проверка, достигнут ли лимит показов
     * @return bool
     */
    public function hasReachedImpressionsLimit(): bool
    {
        return $this->impressions_limit > 0
            && $this->impressions_count >= $this->impressions_limit;
    }

    /**
     * Получить процент выполнения показов
     * @return int
     */
    public function getImpressionsProgressAttribute(): int
    {
        if ($this->impressions_limit == 0) {
            return 0;
        }

        return min(100, round(($this->impressions_count / $this->impressions_limit) * 100));
    }
}
