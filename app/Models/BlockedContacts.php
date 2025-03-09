<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedContacts extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'blocked_contacts';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'phone',
        'name',
        'date',
    ];

    /**
     * Get the user that owns the blocked contact.
     */
    public function user()
    {
        return $this->belongsTo(SecondaryUser::class, 'user_id');
    }
}
