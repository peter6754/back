<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserImage extends Model
{
    use HasFactory;

    public $incrementing = true;
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'image', // FID от SeaweedFS вида "44,068ddbc3e7fb38"
        'is_main',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'string',
            'is_main' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(BackpackUser::class, 'user_id');
    }

    /**
     * @return string
     */
    public function getFid(): string
    {
        return $this->image;
    }

    /**
     * @param int $userId
     * @param string $fid
     * @param bool $isMain
     * @return self
     */
    public static function createForUser(int $userId, string $fid, bool $isMain = false): self
    {
        return self::create([
            'user_id' => $userId,
            'image' => $fid,
            'is_main' => $isMain,
        ]);
    }

    /**
     * @param int $userId
     * @return self|null
     */
    public static function findMainForUser(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->where('is_main', true)
            ->first();
    }

    /**
     * @return void
     */
    public function setAsMain(): void
    {
        // Сначала убираем флаг is_main у всех фото пользователя
        self::where('user_id', $this->user_id)
            ->update(['is_main' => false]);

        // Затем устанавливаем текущее фото как главное
        $this->update(['is_main' => true]);
    }

    /**
     * @return bool
     */
    public function isMain(): bool
    {
        return $this->is_main;
    }

    /**
     * @param int $userId
     * @param string $fid
     * @return self|null
     */
    public static function findByFidForUser(int $userId, string $fid): ?self
    {
        return self::where('user_id', $userId)
            ->where('image', $fid)
            ->first();
    }

    public function getImageUrlAttribute()
    {
        list($volumeId, $fileId) = explode(',', $this->image);
        return "http://api.tinderone.ru/files/{$volumeId},{$fileId}";
    }
}
