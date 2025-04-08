<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Secondaryuser;
use App\Models\UserInformation;
use Carbon\Carbon;

class AllocateWeeklySuperboomsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'superbooms:allocate-weekly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allocate superbooms every 7 days from last reset for gold and premium subscribers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting superboom allocation...');

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

            // Принудительно начисляем супербумы и обновляем дату
            $userInfo->update([
                'superbooms' => 1,
                'superbooms_last_reset' => now()->toDateString(),
            ]);

            $allocated++;
        }

        $this->info("Processed {$processed} subscribed users");
        $this->info("Allocated superbooms for {$allocated} users");
        $this->info('Superboom allocation completed!');

        return 0;
    }
}
