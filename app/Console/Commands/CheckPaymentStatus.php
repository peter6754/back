<?php

namespace App\Console\Commands;

use App\Services\Payments\PaymentsService;
use Illuminate\Console\Command;

class CheckPaymentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:check-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check status of all pending payments';

    /**
     * Execute the console command.
     */
    public function handle(PaymentsService $payments)
    {
        $this->info('Checking payment statuses...');

        $results = $payments->checkPendingPayments();

        $this->info("Completed payments statuses.");
    }
}
