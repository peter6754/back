<?php


namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Secondaryuser;
use Illuminate\Bus\Queueable;
use App\Models\UserReaction;

class ProcessRecommendations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $userId,
        public array  $params
    )
    {
    }

    public function handle()
    {
        $user = Secondaryuser::select(['id'])
            ->with(['userInformation:id,user_id,superboom_due_date'])
            ->findOrFail($this->params['user_id']);

        $superboom = $user->userInformation && $user->userInformation->superboom_due_date >= now();

        UserReaction::updateOrCreate(
            [
                'user_id' => $this->params['user_id'],
                'reactor_id' => $this->userId,
            ],
            [
                'from_top' => $this->params['from_top'],
                'superboom' => $superboom,
                'type' => 'like',
                'date' => now()
            ]
        );

        // Здесь можно добавить логику отправки уведомлений и т.д.
    }
}
