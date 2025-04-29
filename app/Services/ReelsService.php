<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReelsService
{
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
        $user = DB::table('users')
            ->leftJoin('user_settings', 'users.id', '=', 'user_settings.user_id')
            ->leftJoin('user_informations', 'users.id', '=', 'user_informations.user_id')
            ->where('users.id', $userId)
            ->select(
                'users.phone',
                'users.lat',
                'users.long',
                'user_settings.age_range',
                'user_settings.search_radius',
                'user_settings.recommendations',
                'user_settings.is_global_search'
            )
            ->first();

        if (!$user) {
            throw new \Exception('User not found');
        }

        // Original SQL query from NestJS
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

        // Process reels data
        return array_map(function ($reel) {
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
                'likes' => $this->getReelLikes($reel->id),
                'comments' => $this->getReelComments($reel->id),
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

        // Process reels data
        return array_map(function ($reel) {
            return [
                'id' => $reel->id,
                'path' => $reel->path,
                'share_count' => $reel->share_count,
                'comments_count' => (int) $reel->comments_count,
                'liked_by_me' => $reel->liked_by_me === '1',
                'preview_screenshot' => $reel->preview_screenshot,
                'likes' => $this->getReelLikes($reel->id),
                'comments' => $this->getReelComments($reel->id),
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
}
