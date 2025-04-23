<?php

namespace App\Console\Commands;

use App\Models\UserBan;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UnbanExpiredUsers extends Command
{

    protected $signature = 'users:unban-expired';
    protected $description = 'Automatically remove expired user bans';

    public function handle()
    {
        $this->info('Searching for expired bans...');

        $expiredBans = UserBan::where('is_permanent', false)
            ->where('banned_until', '<=', Carbon::now())
            ->get();

        if ($expiredBans->isEmpty()) {
            $this->info('No expired bans found.');
            return Command::FAILURE;
        }

        $count = 0;

        foreach ($expiredBans as $ban) {
            $user = $ban->user;

            if ($user && $user->mode === 'banned') {
                $user->unban();
                $this->info("Auto-unbanned user: {$user->name} (ID: {$user->id})");
                $count++;
            }

            $ban->delete();
        }

        $this->info("Total unbanned users {$count}");

        return Command::SUCCESS;
    }
}
