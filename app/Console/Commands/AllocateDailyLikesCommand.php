<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AllocateDailyLikesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'likes:allocate-daily {--force : Force allocation ignoring the 4-minute period}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allocate likes (30) every 4 minutes for male users without active subscription';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting likes allocation (every 4 minutes)...');

        $force = $this->option('force');
        $now = now()->toDateTimeString();
        $fourMinutesAgo = now()->subMinutes(4)->toDateTimeString();

        $this->info('Step 1: Updating existing records...');

        $dateCondition = $force
            ? '1=1'
            : '(ui.daily_likes_last_reset IS NULL OR ui.daily_likes_last_reset < ?)';

        $bindings = $force ? [$fourMinutesAgo] : [$fourMinutesAgo, $fourMinutesAgo];

        $updated = DB::update("
            UPDATE user_information ui
            INNER JOIN users u ON u.id = ui.user_id
            LEFT JOIN (
                SELECT t.user_id, MAX(bs.due_date) as max_due_date
                FROM transactions t
                INNER JOIN bought_subscriptions bs ON bs.transaction_id = t.id
                WHERE t.status = 'succeeded' AND bs.due_date > ?
                GROUP BY t.user_id
            ) active_subs ON active_subs.user_id = u.id
            SET
                ui.daily_likes = 30,
                ui.daily_likes_last_reset = ?
            WHERE u.gender = 'male'
              AND u.mode = 'authenticated'
              AND active_subs.user_id IS NULL
              AND {$dateCondition}
        ", array_merge([$now, $now], $bindings));

        $this->info("Updated {$updated} existing records");

        $this->info('Step 2: Creating records for users without user_information...');

        $created = DB::insert("
            INSERT INTO user_information (user_id, daily_likes, daily_likes_last_reset)
            SELECT u.id, 30, ?
            FROM users u
            LEFT JOIN user_information ui ON ui.user_id = u.id
            LEFT JOIN (
                SELECT t.user_id, MAX(bs.due_date) as max_due_date
                FROM transactions t
                INNER JOIN bought_subscriptions bs ON bs.transaction_id = t.id
                WHERE t.status = 'succeeded' AND bs.due_date > ?
                GROUP BY t.user_id
            ) active_subs ON active_subs.user_id = u.id
            WHERE u.gender = 'male'
              AND u.mode = 'authenticated'
              AND ui.user_id IS NULL
              AND active_subs.user_id IS NULL
        ", [$now, $now]);

        $this->info("Created {$created} new records");

        $total = $updated + $created;
        $this->info("Likes allocation completed (every 4 minutes)!");
        $this->info("Total processed: {$total} users");

        return 0;
    }
}
