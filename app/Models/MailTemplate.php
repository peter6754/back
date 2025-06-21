<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class MailTemplate extends Model
{
    use CrudTrait;
    protected $fillable = [
        'name',
        'subject',
        'html_body',
        'text_body',
        'variables',
        'is_active'
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean'
    ];

    public function mailQueue()
    {
        return $this->hasMany(MailQueue::class, 'template_id');
    }

    /**
     * Заменить переменные в тексте
     */
    public function replaceVariables($text, array $variables = [])
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }
        return $text;
    }

    /**
     * Получить обработанный HTML
     */
    public function getProcessedHtml(array $variables = [])
    {
        return $this->replaceVariables($this->html_body, $variables);
    }

    /**
     * Получить обработанный TEXT
     */
    public function getProcessedText(array $variables = [])
    {
        return $this->text_body ? $this->replaceVariables($this->text_body, $variables) : null;
    }

    /**
     * Получить обработанный subject
     */
    public function getProcessedSubject(array $variables = [])
    {
        return $this->replaceVariables($this->subject, $variables);
    }
}
