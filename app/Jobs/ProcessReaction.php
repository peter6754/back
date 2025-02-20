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
        public array  $params
    )
    {
    }

    /**
     * @return bool[]|string[]
     * @throws \Throwable
     */
    public function handle(): array
    {
        return match ($this->actionType) {
            self::ACTION_LIKE => $this->processLike(),
            self::ACTION_DISLIKE => $this->processDislike(),
            self::ACTION_SUPERLIKE => $this->processSuperlike(),
            self::ACTION_ROLLBACK => $this->processRollback(),
            default => throw new \InvalidArgumentException("Unknown action type: {$this->actionType}"),
        };
    }

    /**
     * @return bool[]
     * @throws \Throwable
     */
    private function processLike(): array
    {
        $reactionExists = $this->checkExistingReaction();
        $superboom = $this->getSuperboomStatus();

        $this->updateOrCreateReaction([
            'user_id' => $this->params['user_id'],
            'reactor_id' => $this->userId,
        ], [
            'from_top' => $this->params['from_top'] ?? false,
            'superboom' => $superboom,
            'type' => 'like',
            'date' => now(),
        ]);

        return ['is_match' => $reactionExists];
    }

    /**
     * @return string[]
     * @throws \Throwable
     */
    private function processDislike(): array
    {
        $this->updateOrCreateReaction([
            'user_id' => $this->params['user_id'],
            'reactor_id' => $this->userId,
        ], [
            'type' => 'dislike',
            'date' => now()
        ]);

        return ['message' => 'Reaction sent successfully'];
    }

    /**
     * @return bool[]
     * @throws \Throwable
     */
    private function processSuperlike(): array
    {
        $reactionExists = $this->checkExistingReaction();
        $superboom = $this->getSuperboomStatus();

        $this->updateOrCreateReaction([
            'user_id' => $this->params['user_id'],
            'reactor_id' => $this->userId,
        ], [
            'from_top' => $this->params['from_top'] ?? false,
            'superboom' => $superboom,
            'type' => 'superlike',
            'date' => now()
        ]);

        UserInformation::where('user_id', $this->userId)->decrement('superlikes');

        if (!empty($this->params['comment'])) {
            $this->leaveComment($this->params['comment'], $this->userId, $this->params['user_id']);
        }

        return ['is_match' => $reactionExists];
    }

    /**
     * @return string[]
     * @throws \Exception
     */
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

    /**
     * @return bool
     */
    private function checkExistingReaction(): bool
    {
        return UserReaction::where('reactor_id', $this->params['user_id'])
            ->where('user_id', $this->userId)
            ->whereIn('type', ['like', 'superlike'])
            ->exists();
    }

    /**
     * @return bool
     */
    private function getSuperboomStatus(): bool
    {
        $user = Secondaryuser::with(['userInformation'])
            ->select(['id'])
            ->findOrFail($this->params['user_id']);

        return $user->userInformation && $user->userInformation->superboom_due_date >= now();
    }

    /**
     * @param string $comment
     * @param string $authorId
     * @param string $recipientId
     * @return void
     */
    private function leaveComment(string $comment, string $authorId, string $recipientId)
    {
        // Реализация добавления комментария
    }

    /**
     * @param array $attributes
     * @param array $values
     * @return UserReaction
     * @throws \Throwable
     */
    private function updateOrCreateReaction(array $attributes, array $values): UserReaction
    {
        // Сначала пытаемся обновить
        if (UserReaction::where($attributes)->exists()) {
            UserReaction::where($attributes)->update($values);
            return UserReaction::where($attributes)->first();
        }

        // Если нет - создаем
        return UserReaction::create(array_merge($attributes, [
            'superboom' => false,
            'from_top' => false,
            'is_notified' => false,
            'from_reels' => false,
        ], $values));
    }

    /**
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::channel('recommendations')->error("Reaction processing failed: {$exception->getMessage()}");
    }
}
