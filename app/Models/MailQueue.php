<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class MailQueue extends Model
{
    use CrudTrait;
    protected $table = 'mail_queue';

    protected $fillable = [
        'to_email',
        'to_name',
        'subject',
        'html_body',
        'text_body',
        'from_email',
        'from_name',
        'reply_to',
        'attachments',
        'variables',
        'status',
        'attempts',
        'send_after',
        'sent_at',
        'error_message',
        'template_id'
    ];

    protected $casts = [
        'attachments' => 'array',
        'variables' => 'array',
        'send_after' => 'datetime',
        'sent_at' => 'datetime'
    ];

    public function template()
    {
        return $this->belongsTo(MailTemplate::class, 'template_id');
    }

    /**
     * Scope для готовых к отправке писем
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('send_after')
                    ->orWhere('send_after', '<=', now());
            });
    }

    /**
     * Отметить как отправленное
     */
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);
    }

    /**
     * Отметить как неудачное
     */
    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'attempts' => $this->attempts + 1
        ]);
    }
}
