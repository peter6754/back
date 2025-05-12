<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvertisementImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'advertisement_id',
        'fid',
        'original_name',
        'order',
        'is_primary',
    ];

    protected $casts = [
        'advertisement_id' => 'integer',
        'order' => 'integer',
        'is_primary' => 'boolean',
    ];

    /**
     * Связь с рекламой
     */
    public function advertisement(): BelongsTo
    {
        return $this->belongsTo(Advertisement::class);
    }

    /**
     * Получить полный URL изображения из SeaweedFS
     */
    public function getUrlAttribute(): string
    {
        $seaweedUrl = config('services.seaweedfs.url', env('SEAWEED_FS_URL'));
        return "{$seaweedUrl}/{$this->fid}";
    }

    /**
     * Установить изображение как основное
     */
    public function setAsPrimary(): void
    {
        self::where('advertisement_id', $this->advertisement_id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
    }

}
