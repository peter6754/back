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
    protected $signature = 'superlikes:allocate-weekly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allocate weekly superlikes for gold and premium subscribers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting weekly superlike allocation...');

        $weekStart = Carbon::now()->startOfWeek();
        $allocated = 0;
        $processed = 0;

        // Get all users with Gold and Premium subscriptions only
        $subscribedUsers = Secondaryuser::whereHas('activeSubscription', function ($query) {
            $query->whereHas('package.subscription', function ($subQuery) {
                $subQuery->whereIn('type', ['Tinderone Gold', 'Tinderone Premium']);
            });
        })->with('userInformation')->get();

        foreach ($subscribedUsers as $user) {
            $processed++;

            $userInfo = $user->userInformation ?? UserInformation::create(['user_id' => $user->id]);

            $previousSuperlikes = $userInfo->superlikes ?? 0;

            $userInfo->allocateWeeklySuperlikes();

            $userInfo->refresh();

            if ($userInfo->superlikes != $previousSuperlikes) {
                $allocated++;
            }
        }

        $this->info("Processed {$processed} subscribed users");
        $this->info("Allocated superlikes for {$allocated} users");
        $this->info('Weekly superlike allocation completed!');

        return 0;
    }
}
