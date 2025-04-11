<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Secondaryuser;
use App\Models\UserInformation;
use Carbon\Carbon;

class AllocateMonthlySuperboomsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'superbooms:allocate-monthly {--force : Force allocation ignoring the 3-minute period}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allocate superbooms every 3 minutes from last reset for gold and premium subscribers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting monthly superboom allocation...');

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

            // Initialize last_reset if NULL (for existing users after migration)
            if (! $userInfo->superbooms_last_reset) {
                $userInfo->update([
                    'superbooms' => 1,
                    'superbooms_last_reset' => now()->toDateString(),
                ]);
                $allocated++;
                continue;
            }

            // Check if 3 minutes have passed since last reset
            $lastReset = Carbon::parse($userInfo->superbooms_last_reset);
            $minutesSinceReset = $lastReset->diffInMinutes(now(), true);
            $force = $this->option('force');

            if ($force || $minutesSinceReset >= 3) {
                // Начисляем супербум (старые начисленные сгорают, купленные остаются)
                $userInfo->update([
                    'superbooms' => 1,
                    'superbooms_last_reset' => now()->toDateString(),
                ]);
                $allocated++;
                #$this->info("User {$user->id}: allocated 1 superboom (last reset: {$lastReset->format('Y-m-d')}, " . round($daysSinceReset, 2) . " days ago)");
            } else {
                $skipped++;
            }
        }

        $this->info("Processed {$processed} subscribed users");
        $this->info("Allocated superbooms for {$allocated} users");
        $this->info("Skipped {$skipped} users (not enough time passed or no reset date)");
        $this->info('Monthly superboom allocation completed!');

        return 0;
    }
}
