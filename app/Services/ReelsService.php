<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReelsService
{
    public function __construct(private SeaweedFsService $seaweedFsService)
    {
    }

    /**
     * Add new reel - upload video to SeaweedFS
     *
     * @param UploadedFile $video
     * @param string $userId
     * @return array
     */
    public function addReel(UploadedFile $video, string $userId): array
    {
        try {
            // Upload video to SeaweedFS
            $videoContent = file_get_contents($video->getRealPath());
            $videoFid = $this->seaweedFsService->uploadToStorage($videoContent, $video->getClientOriginalName());

            $reelId = (string) Str::uuid();

            DB::insert("
                INSERT INTO reels (id, user_id, path, is_temporary, share_count)
                VALUES (?, ?, ?, ?, ?)
            ", [$reelId, $userId, $videoFid, 1, 0]);

            return [
                'id' => $reelId,
                'path' => $videoFid,
                'message' => 'Video uploaded successfully'
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to upload video: '.$e->getMessage());
        }
    }

    /**
     * Get reels feed for user with complex filtering
     *
     * @param string $userId
     * @param string $sharedId
     * @return array
     */
    public function getReels(string $userId, string $sharedId = ''): array
    {
        // Get user data with settings
        $users = DB::select("
            SELECT
                u.phone,
                u.lat,
                u.`long`,
                us.age_range,
                us.search_radius,
                us.recommendations,
                us.is_global_search
            FROM users u
            LEFT JOIN user_settings us ON u.id = us.user_id
            LEFT JOIN user_information ui ON u.id = ui.user_id
            WHERE u.id = ?
            LIMIT 1
        ", [$userId]);

        if (empty($users)) {
            throw new \Exception('User not found');
        }

        $user = $users[0];

        // Original SQL from NestJS
        $reels = DB::select("
            WITH users_in_my_radius AS (
                SELECT u.id FROM users u
                WHERE ST_Distance_Sphere(
                    point(?, ?),
                    point(u.long, u.lat)
                ) / 1000 <= ?
                AND u.id != ?
            ),
            users_blocked_by_me AS (
                SELECT phone FROM blocked_contacts
                WHERE user_id = ?
            ),
            users_who_blocked_me AS (
                SELECT user_id FROM blocked_contacts
                WHERE phone = ?
            ),
            my_preferences AS (
                SELECT gender FROM user_preferences
                WHERE user_id = ?
            ),
            reels_liked_by_me AS (
                SELECT reel_id FROM reel_likes WHERE user_id = ?
            ),
            reels_viewed_by_me AS (
                SELECT reel_id FROM reel_views WHERE user_id = ?
            )

            SELECT
                preview_screenshot,
                CAST(r.id IN (SELECT * FROM reels_liked_by_me) AS CHAR) liked_by_me,
                r.share_count,
                (SELECT image FROM user_images WHERE user_id = u.id LIMIT 1) user_image,
                u.name,
                u.age,
                r.id,
                r.path,
                r.user_id,
                CAST((SELECT COUNT(*) FROM reel_comments WHERE reel_id = r.id) AS CHAR) comments_count
            FROM reels r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE IF(r.id = ?, 1, (r.id NOT IN (SELECT * FROM reels_liked_by_me) AND ? NOT IN (SELECT user_id FROM reel_views WHERE reel_id = r.id)))
            AND u.id != ?
            AND u.lat IS NOT NULL
            AND u.long IS NOT NULL
            AND NOT r.is_temporary
            AND u.phone NOT IN (SELECT * FROM users_blocked_by_me)
            AND u.id NOT IN (SELECT * FROM users_who_blocked_me)
            ORDER BY
                (r.id = ?) DESC,
                (u.id IN (SELECT * FROM users_in_my_radius)) DESC,
                (u.age BETWEEN ? AND ?) DESC,
                (u.gender IN (SELECT * FROM my_preferences)) DESC
            LIMIT 5
        ", [
            $user->long, $user->lat,                    // ST_Distance_Sphere params
            $user->search_radius,                       // search radius
            $userId,                                     // exclude self from radius
            $userId,                                     // blocked_contacts user_id
            $user->phone,                               // users_who_blocked_me
            $userId,                                     // my_preferences
            $userId,                                     // reels_liked_by_me
            $userId,                                     // reels_viewed_by_me
            $sharedId,                                  // shared reel check 1
            $userId,                                     // exclude from views check
            $userId,                                     // exclude self from results
            $sharedId,                                  // shared reel sort priority
            ...explode('-', $user->age_range),          // age range min-max
        ]);

        if (empty($reels)) {
            return [];
        }

        // Get all reel IDs
        $reelIds = array_map(fn ($r) => $r->id, $reels);
        $reelIdsPlaceholders = implode(',', array_fill(0, count($reelIds), '?'));

        // Get all likes for these reels in one query - pure SQL
        $likesData = DB::select("
            SELECT
                rl.reel_id,
                u.id as user_id,
                u.name as username,
                u.age as user_age,
                (SELECT image FROM user_images WHERE user_id = u.id LIMIT 1) as user_image
            FROM reel_likes rl
            JOIN users u ON rl.user_id = u.id
            WHERE rl.reel_id IN ($reelIdsPlaceholders)
        ", $reelIds);

        // Group likes by reel_id
        $likesByReel = [];
        foreach ($likesData as $like) {
            if (! isset($likesByReel[$like->reel_id])) {
                $likesByReel[$like->reel_id] = [];
            }
            $likesByReel[$like->reel_id][] = [
                'user_id' => $like->user_id,
                'user_image' => $like->user_image,
                'username' => $like->username,
                'user_age' => $like->user_age,
            ];
        }

        // Get all comments for reels
        $commentsData = DB::select("
            SELECT
                rc.reel_id,
                rc.comment,
                u.id as user_id,
                u.name as user_name,
                (SELECT image FROM user_images WHERE user_id = u.id LIMIT 1) as user_image
            FROM reel_comments rc
            JOIN users u ON rc.user_id = u.id
            WHERE rc.reel_id IN ($reelIdsPlaceholders)
            LIMIT 25
        ", $reelIds);

        // Group comments by reel_id limit 5 per reel
        $commentsByReel = [];
        foreach ($commentsData as $comment) {
            if (! isset($commentsByReel[$comment->reel_id])) {
                $commentsByReel[$comment->reel_id] = [];
            }
            if (count($commentsByReel[$comment->reel_id]) < 5) {
                $commentsByReel[$comment->reel_id][] = [
                    'user' => [
                        'id' => $comment->user_id,
                        'image' => $comment->user_image,
                        'name' => $comment->user_name,
                    ],
                    'comment' => $comment->comment,
                ];
            }
        }

        // Process reels data
        return array_map(function ($reel) use ($likesByReel, $commentsByReel) {
            return [
                'id' => $reel->id,
                'path' => $reel->path,
                'share_count' => $reel->share_count,
                'liked_by_me' => $reel->liked_by_me === '1',
                'user' => [
                    'id' => $reel->user_id,
                    'image' => $reel->user_image,
                    'name' => $reel->name,
                    'age' => $reel->age,
                ],
                'preview_screenshot' => $reel->preview_screenshot,
                'comments_count' => (int) $reel->comments_count,
                'likes' => $likesByReel[$reel->id] ?? [],
                'comments' => $commentsByReel[$reel->id] ?? [],
            ];
        }, $reels);
    }

    /**
     * Get user's own reels
     *
     * @param string $userId
     * @param string $selectedId
     * @return array
     */
    public function getUserReels(string $userId, string $selectedId = ''): array
    {
        // Original SQL query from NestJS
        $reels = DB::select("
            WITH reels_liked_by_me AS (
                SELECT reel_id FROM reel_likes WHERE user_id = ?
            )

            SELECT
                preview_screenshot,
                CAST(r.id IN (SELECT * FROM reels_liked_by_me) AS CHAR) liked_by_me,
                r.share_count,
                (SELECT image FROM user_images WHERE user_id = u.id LIMIT 1) user_image,
                u.name,
                u.age,
                r.id,
                r.path,
                r.user_id,
                CAST((SELECT COUNT(*) FROM reel_comments WHERE reel_id = r.id LIMIT 5) AS CHAR) comments_count
            FROM reels r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE u.id = ?
            ORDER BY (r.id = ?) DESC
        ", [$userId, $userId, $selectedId]);

        if (empty($reels)) {
            return [];
        }

        // Get all reel IDs
        $reelIds = array_map(fn ($r) => $r->id, $reels);
        $reelIdsPlaceholders = implode(',', array_fill(0, count($reelIds), '?'));

        // Get all likes for these reels in one query
        $likesData = DB::select("
            SELECT
                rl.reel_id,
                u.id as user_id,
                u.name as username,
                u.age as user_age,
                (SELECT image FROM user_images WHERE user_id = u.id LIMIT 1) as user_image
            FROM reel_likes rl
            JOIN users u ON rl.user_id = u.id
            WHERE rl.reel_id IN ($reelIdsPlaceholders)
        ", $reelIds);

        // Group likes by reel_id
        $likesByReel = [];
        foreach ($likesData as $like) {
            if (! isset($likesByReel[$like->reel_id])) {
                $likesByReel[$like->reel_id] = [];
            }
            $likesByReel[$like->reel_id][] = [
                'id' => $like->user_id,
                'image' => $like->user_image,
                'name' => $like->username,
                'age' => $like->user_age,
            ];
        }

        // Get all comments for these reels in one query
        $commentsData = DB::select("
            SELECT
                rc.reel_id,
                rc.comment,
                u.id as user_id,
                u.name as user_name,
                (SELECT image FROM user_images WHERE user_id = u.id LIMIT 1) as user_image
            FROM reel_comments rc
            JOIN users u ON rc.user_id = u.id
            WHERE rc.reel_id IN ($reelIdsPlaceholders)
        ", $reelIds);

        // Group comments by reel_id (limit 5 per reel)
        $commentsByReel = [];
        foreach ($commentsData as $comment) {
            if (! isset($commentsByReel[$comment->reel_id])) {
                $commentsByReel[$comment->reel_id] = [];
            }
            if (count($commentsByReel[$comment->reel_id]) < 5) {
                $commentsByReel[$comment->reel_id][] = [
                    'user' => [
                        'id' => $comment->user_id,
                        'image' => $comment->user_image,
                        'name' => $comment->user_name,
                    ],
                    'comment' => $comment->comment,
                ];
            }
        }

        // Process reels data
        return array_map(function ($reel) use ($likesByReel, $commentsByReel) {
            return [
                'id' => $reel->id,
                'path' => $reel->path,
                'share_count' => $reel->share_count,
                'comments_count' => (int) $reel->comments_count,
                'liked_by_me' => $reel->liked_by_me === '1',
                'preview_screenshot' => $reel->preview_screenshot,
                'likes' => $likesByReel[$reel->id] ?? [],
                'comments' => $commentsByReel[$reel->id] ?? [],
            ];
        }, $reels);
    }

    /**
     * Get comments for a reel
     *
     * @param string $reelId
     * @return array
     */
    public function getComments(string $reelId): array
    {
        $comments = DB::table('reel_comments')
            ->join('users', 'reel_comments.user_id', '=', 'users.id')
            ->where('reel_comments.reel_id', $reelId)
            ->select('reel_comments.comment', 'users.id', 'users.name')
            ->limit(5)
            ->get()
            ->map(function ($comment) {
                $userImage = DB::table('user_images')
                    ->where('user_id', $comment->id)
                    ->limit(1)
                    ->value('image');

                return [
                    'user' => [
                        'id' => $comment->id,
                        'image' => $userImage,
                        'name' => $comment->name,
                    ],
                    'comment' => $comment->comment,
                ];
            });

        return ['items' => $comments->toArray()];
    }

    /**
     * Get likes for a specific reel
     *
     * @param string $reelId
     * @return array
     */
    private function getReelLikes(string $reelId): array
    {
        return DB::table('reel_likes')
            ->join('users', 'reel_likes.user_id', '=', 'users.id')
            ->where('reel_likes.reel_id', $reelId)
            ->select('users.id', 'users.name', 'users.age')
            ->get()
            ->map(function ($like) {
                $userImage = DB::table('user_images')
                    ->where('user_id', $like->id)
                    ->value('image');

                return [
                    'user_id' => $like->id,
                    'user_image' => $userImage,
                    'username' => $like->name,
                    'user_age' => $like->age,
                ];
            })
            ->toArray();
    }

    /**
     * Get comments for a specific reel (private helper)
     *
     * @param string $reelId
     * @return array
     */
    private function getReelComments(string $reelId): array
    {
        return DB::table('reel_comments')
            ->join('users', 'reel_comments.user_id', '=', 'users.id')
            ->where('reel_comments.reel_id', $reelId)
            ->select('reel_comments.comment', 'users.id', 'users.name')
            ->limit(5)
            ->get()
            ->map(function ($comment) {
                $userImage = DB::table('user_images')
                    ->where('user_id', $comment->id)
                    ->limit(1)
                    ->value('image');

                return [
                    'user' => [
                        'id' => $comment->id,
                        'image' => $userImage,
                        'name' => $comment->name,
                    ],
                    'comment' => $comment->comment,
                ];
            })
            ->toArray();
    }

    /**
     * Add comment to a reel - pure SQL
     *
     * @param string $userId
     * @param string $reelId
     * @param string $comment
     * @return array
     */
    public function addComment(string $userId, string $reelId, string $comment): array
    {
        try {
            DB::insert("
                INSERT INTO reel_comments (reel_id, user_id, comment)
                VALUES (?, ?, ?)
            ", [$reelId, $userId, $comment]);

            return [
                'message' => 'Request has ended successfully'
            ];
        } catch (\Exception $e) {
            throw new \Exception('Item not found');
        }
    }

    /**
     * Mark reel as viewed - upsert using pure SQL
     *
     * @param string $reelId
     * @param string $userId
     * @return array
     */
    public function viewTheReel(string $reelId, string $userId): array
    {
        try {
            DB::insert("
                INSERT INTO reel_views (reel_id, user_id)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)
            ", [$reelId, $userId]);

            return [
                'message' => 'Request has ended successfully'
            ];
        } catch (\Exception $e) {
            throw new \Exception('Item not found');
        }
    }

    /**
     * Like a reel
     *
     * @param string $reelId
     * @param string $userId
     * @return array
     */
    public function likeTheReel(string $reelId, string $userId): array
    {
        try {
            // Find the reel
            $reel = DB::selectOne("
                SELECT id, user_id FROM reels WHERE id = ?
            ", [$reelId]);

            if (! $reel) {
                throw new \Exception('Reel not found');
            }

            // Check if user_reaction exists
            $userReaction = DB::selectOne("
                SELECT * FROM user_reactions
                WHERE reactor_id = ?
                AND user_id = ?
                AND type IN ('superlike', 'like')
            ", [$reel->user_id, $userId]);

            // If user_reaction doesn't exist, create user_reaction
            if (! $userReaction) {
                DB::insert("
                    INSERT INTO user_reactions (user_id, reactor_id, type, from_reels, date)
                    VALUES (?, ?, 'like', 1, NOW())
                    ON DUPLICATE KEY UPDATE date = NOW()
                ", [$reel->user_id, $userId]);
            }

            // Create reel like INSERT IGNORE if like exists
            DB::insert("
                INSERT IGNORE INTO reel_likes (reel_id, user_id)
                VALUES (?, ?)
            ", [$reelId, $userId]);

            return [
                'message' => 'Like has been sent successfully',
                'is_match' => ! ! $userReaction
            ];
        } catch (\Exception $e) {
            throw new \Exception('Item not found: '.$e->getMessage());
        }
    }
}
