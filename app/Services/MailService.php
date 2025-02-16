<?php

namespace App\Services;

use App\Models\MailTemplate;
use App\Models\MailQueue;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class MailService
{
    /**
     * Добавить письмо в очередь по шаблону
     */
    public function queueFromTemplate(
        string $templateName,
        string $toEmail,
        array  $variables = [],
        string $toName = null,
        Carbon $sendAfter = null,
        array  $options = []
    )
    {
        $template = MailTemplate::where('name', $templateName)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            throw new \Exception("Template '{$templateName}' not found or inactive");
        }

        return $this->queueMail([
            'to_email' => $toEmail,
            'to_name' => $toName,
            'subject' => $template->getProcessedSubject($variables),
            'html_body' => $template->getProcessedHtml($variables),
            'text_body' => $template->getProcessedText($variables),
            'template_id' => $template->id,
            'variables' => $variables,
            'send_after' => $sendAfter,
            'from_email' => $options['from_email'] ?? null,
            'from_name' => $options['from_name'] ?? null,
            'reply_to' => $options['reply_to'] ?? null,
            'attachments' => $options['attachments'] ?? null,
        ]);
    }

    /**
     * Добавить произвольное письмо в очередь
     */
    public function queueMail(array $data)
    {
        return MailQueue::create(array_merge([
            'status' => 'pending',
            'attempts' => 0
        ], $data));
    }

    /**
     * Быстрая отправка письма (сразу в очередь и попытка отправить)
     */
    public function sendNow(
        string $templateName,
        string $toEmail,
        array  $variables = [],
        string $toName = null,
        array  $options = []
    )
    {
        $mailQueue = $this->queueFromTemplate(
            $templateName,
            $toEmail,
            $variables,
            $toName,
            null,
            $options
        );

        return $this->processSingleMail($mailQueue);
    }

    /**
     * Обработать письма из очереди
     */
    public function processQueue(int $limit = 50)
    {
        $mails = MailQueue::readyToSend()
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($mails as $mail) {
            if ($this->processSingleMail($mail)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'processed' => $mails->count(),
            'sent' => $sent,
            'failed' => $failed
        ];
    }

    /**
     * Обработать одно письмо
     */
    protected function processSingleMail(MailQueue $mailQueue)
    {
        try {
            Mail::send([], [], function ($message) use ($mailQueue) {
                $message->to($mailQueue->to_email, $mailQueue->to_name)
                    ->subject($mailQueue->subject);

                if ($mailQueue->from_email) {
                    $message->from($mailQueue->from_email, $mailQueue->from_name);
                }

                if ($mailQueue->reply_to) {
                    $message->replyTo($mailQueue->reply_to);
                }

                // HTML контент
                if ($mailQueue->html_body) {
                    $message->html($mailQueue->html_body);
                }

                // Текстовая версия
                if ($mailQueue->text_body) {
                    $message->text($mailQueue->text_body);
                }

                // Прикрепления
                if ($mailQueue->attachments) {
                    foreach ($mailQueue->attachments as $attachment) {
                        if (is_array($attachment)) {
                            $message->attach(
                                $attachment['path'],
                                [
                                    'as' => $attachment['name'] ?? null,
                                    'mime' => $attachment['mime'] ?? null
                                ]
                            );
                        } else {
                            $message->attach($attachment);
                        }
                    }
                }
            });

            $mailQueue->markAsSent();
            return true;

        } catch (\Exception $e) {
            $mailQueue->markAsFailed($e->getMessage());
            \Log::error('Mail sending failed', [
                'mail_id' => $mailQueue->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Получить статистику очереди
     */
    public function getQueueStats()
    {
        return [
            'pending' => MailQueue::where('status', 'pending')->count(),
            'sent' => MailQueue::where('status', 'sent')->count(),
            'failed' => MailQueue::where('status', 'failed')->count(),
            'total' => MailQueue::count()
        ];
    }

    /**
     * Очистить старые письма
     */
    public function cleanOldMails(int $daysOld = 30)
    {
        return MailQueue::where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }
}
