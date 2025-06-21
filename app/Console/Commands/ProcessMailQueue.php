<?php

namespace App\Console\Commands;

use App\Services\MailService;
use Illuminate\Console\Command;

class ProcessMailQueue extends Command
{
    protected $signature = 'mail:process-queue {--limit=50 : Maximum number of emails to process}';
    protected $description = 'Process pending emails in the mail queue';

    protected $mailService;

    public function __construct(MailService $mailService)
    {
        parent::__construct();
        $this->mailService = $mailService;
    }

    public function handle()
    {
        $limit = $this->option('limit');

        $this->info("Processing mail queue (limit: {$limit})...");

        $result = $this->mailService->processQueue($limit);

        $this->info("Processed: {$result['processed']} emails");
        $this->info("Sent: {$result['sent']} emails");

        if ($result['failed'] > 0) {
            $this->warn("Failed: {$result['failed']} emails");
        }

        // Показать статистику очереди
        $stats = $this->mailService->getQueueStats();
        $this->table(
            ['Status', 'Count'],
            [
                ['Pending', $stats['pending']],
                ['Sent', $stats['sent']],
                ['Failed', $stats['failed']],
                ['Total', $stats['total']]
            ]
        );

        return 0;
    }
}
