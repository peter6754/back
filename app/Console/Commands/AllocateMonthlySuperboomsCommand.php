<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Secondaryuser;
use App\Models\UserInformation;

class AllocateMonthlySuperboomsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'superbooms:allocate-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allocate monthly superbooms for gold and premium subscribers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting monthly superboom allocation...');

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

            $previousSuperbooms = $userInfo->superbooms ?? 0;

            $this->allocateMonthlySuperbooms($userInfo);

            $userInfo->refresh();

            if ($userInfo->superbooms != $previousSuperbooms) {
                $allocated++;
            }
        }

        $this->info("Processed {$processed} subscribed users");
        $this->info("Allocated superbooms for {$allocated} users");
        $this->info('Monthly superboom allocation completed!');

        return 0;
    }

    /**
     * Allocate monthly superbooms for subscribed users
     */
    private function allocateMonthlySuperbooms(UserInformation $userInfo): void
    {
        $user = $userInfo->user;
        
        // Check if user has Gold or Premium subscription
        if (!$user || !$user->activeSubscription) {
            return;
        }

        $subscriptionType = $user->activeSubscription->package->subscription->type ?? '';
        $isEligible = in_array($subscriptionType, ['Tinderone Gold', 'Tinderone Premium']);
        
        if (!$isEligible) {
            return;
        }

        $currentMonth = now()->startOfMonth()->toDateString();

        // Only allocate if not already done this month
        if ($userInfo->superbooms_last_reset !== $currentMonth) {
            $userInfo->update([
                'superbooms' => 1,
                'superbooms_last_reset' => $currentMonth,
            ]);
        }
    }
}
