<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Secondaryuser;
use App\Models\UserInformation;
use Carbon\Carbon;

class AllocateWeeklySuperlikesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'superlikes:allocate-weekly {--force : Force allocation ignoring the 7-day period}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allocate superlikes every 7 days from last reset for gold and premium subscribers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting weekly superlike allocation...');

        $allocated = 0;
        $processed = 0;
        $skipped = 0;

        // Get all users with Gold and Premium subscriptions only
        $subscribedUsers = Secondaryuser::whereHas('activeSubscription', function ($query) {
            $query->whereHas('package.subscription', function ($subQuery) {
                $subQuery->whereIn('type', ['Tinderone Gold', 'Tinderone Premium']);
            });
        })->with('userInformation')->get();

        foreach ($subscribedUsers as $user) {
            $processed++;

            $userInfo = $user->userInformation;

            // Skip if no user information
            if (! $userInfo) {
                $skipped++;
                continue;
            }

            // Check if 7 days have passed since last reset
            $lastReset = Carbon::parse($userInfo->superlikes_last_reset);
            $daysSinceReset = $lastReset->diffInDays(now(), true);
            $force = $this->option('force');

            if ($force || $daysSinceReset >= 7) {
                // Начисляем суперлайки (старые начисленные сгорают, купленные остаются)
                $userInfo->update([
                    'superlikes' => 5,
                    'superlikes_last_reset' => now()->toDateString(),
                ]);
                $allocated++;
                #$this->info("User {$user->id}: allocated 5 superlikes (last reset: {$lastReset->format('Y-m-d')}, " . round($daysSinceReset, 2) . " days ago)");
            } else {
                $skipped++;
            }
        }

        $this->info("Processed {$processed} subscribed users");
        $this->info("Allocated superlikes for {$allocated} users");
        $this->info("Skipped {$skipped} users (not enough time passed or no reset date)");
        $this->info('Weekly superlike allocation completed!');

        return 0;
    }
}
