<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationRequests extends Model
{
    use CrudTrait;
    use HasFactory;
    protected $connection = 'mysql_secondary';
    protected $table = 'verification_requests';
    protected $keyType = 'string';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'user_id', 'image', 'status', 'rejection_reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getImageUrlAttribute()
    {

        if (!$this->image) {
            return null;
        }
        list($volumeId, $fileId) = explode(',', $this->image);
        return "http://api.tinderone.ru/files/{$volumeId},{$fileId}";
    }

}
