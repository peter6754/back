<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\UserInformation;
use Illuminate\Bus\Queueable;
use App\Models\Secondaryuser;
use App\Models\UserReaction;

class ProcessReaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const ACTION_LIKE = 'like';
    const ACTION_DISLIKE = 'dislike';
    const ACTION_SUPERLIKE = 'superlike';
    const ACTION_ROLLBACK = 'rollback';

    public function __construct(
        public string $actionType,
        public string $userId,
        public array $params
    ) {}

    public function handle(): array
    {
        return match($this->actionType) {
            self::ACTION_LIKE => $this->processLike(),
            self::ACTION_DISLIKE => $this->processDislike(),
            self::ACTION_SUPERLIKE => $this->processSuperlike(),
            self::ACTION_ROLLBACK => $this->processRollback(),
            default => throw new \InvalidArgumentException("Unknown action type: {$this->actionType}"),
        };
    }

    private function processLike(): array
    {
        $reactionExists = $this->checkExistingReaction();
        $superboom = $this->getSuperboomStatus();

        UserReaction::updateOrCreate(
            [
                'user_id' => $this->params['user_id'],
                'reactor_id' => $this->userId,
            ],
            [
                'from_top' => $this->params['from_top'] ?? false,
                'superboom' => $superboom,
                'type' => 'like',
                'date' => now(),
            ]
        );

        return ['is_match' => $reactionExists];
    }

    private function processDislike(): array
    {
        UserReaction::updateOrCreate(
            [
                'user_id' => $this->params['user_id'],
                'reactor_id' => $this->userId,
            ],
            [
                'type' => 'dislike',
                'date' => now()
            ]
        );

        return ['message' => 'Reaction sent successfully'];
    }

    private function processSuperlike(): array
    {
        $reactionExists = $this->checkExistingReaction();
        $superboom = $this->getSuperboomStatus();

        UserReaction::updateOrCreate(
            [
                'user_id' => $this->params['user_id'],
                'reactor_id' => $this->userId,
            ],
            [
                'from_top' => $this->params['from_top'] ?? false,
                'superboom' => $superboom,
                'type' => 'superlike',
                'date' => now()
            ]
        );

        UserInformation::where('user_id', $this->userId)->decrement('superlikes');

        if (!empty($this->params['comment'])) {
            $this->leaveComment($this->params['comment'], $this->userId, $this->params['user_id']);
        }

        return ['is_match' => $reactionExists];
    }

    private function processRollback(): array
    {
        $lastReacted = UserReaction::where('reactor_id', $this->userId)
            ->latest('date')
            ->first(['user_id']);

        if (!$lastReacted || $lastReacted->user_id != $this->params['user_id']) {
            throw new \Exception('Your last reaction doesn\'t match to the given user_id');
        }

        DB::table('user_reactions')
            ->where('reactor_id', $this->userId)
            ->where('user_id', $this->params['user_id'])
            ->orderBy('date', 'desc')
            ->limit(1)
            ->delete();

        return ['message' => 'Rollbacked successfully'];
    }

    private function checkExistingReaction(): bool
    {
        return UserReaction::where('reactor_id', $this->params['user_id'])
            ->where('user_id', $this->userId)
            ->whereIn('type', ['like', 'superlike'])
            ->exists();
    }

    private function getSuperboomStatus(): bool
    {
        $user = Secondaryuser::with(['userInformation'])
            ->select(['id'])
            ->findOrFail($this->params['user_id']);

        return $user->userInformation && $user->userInformation->superboom_due_date >= now();
    }

    private function leaveComment(string $comment, string $authorId, string $recipientId)
    {
        // Реализация добавления комментария
    }

    public function failed(\Throwable $exception)
    {
        Log::error("Reaction processing failed: {$exception->getMessage()}");
    }
}
