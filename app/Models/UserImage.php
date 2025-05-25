<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserImage extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(BackpackUser::class, 'user_id');
    }

    public function getImageUrlAttribute()
    {
        list($volumeId, $fileId) = explode(',', $this->image);
        return "http://api.tinderone.ru/files/{$volumeId},{$fileId}";
    }
}
