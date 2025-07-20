<?php

namespace App\DTO;

use Spatie\LaravelData\Data;
use Illuminate\Support\Facades\Request;

class RecommendationsDto extends Data
{
    public function __construct(
        public ?string $min_photo_count,
        public ?string $interest_id,
        public ?string $is_verified,
        public ?string $has_info,


        public ?string $user_id,
    )
    {
    }

    /**
     * @param  $request
     * @param array $validator
     * @return array
     */
    public static function forRecommendations($request, array $validator = []): array
    {
        // Validator rules
        $request->validate(array_merge([
            'min_photo_count' => 'string|nullable',
            'interest_id' => 'string|nullable',
            'is_verified' => 'string|nullable',
            'has_info' => 'string|nullable',
        ], $validator));

        // Return
        return [
            'min_photo_count' => $request->input('min_photo_count'),
            'interest_id' => $request->input('interest_id'),
            'is_verified' => $request->input('is_verified'),
            'has_info' => $request->input('has_info')
        ];
    }

    /**
     * @param $request
     * @param array $validator
     * @return array
     */
    public static function forActions($request, array $validator = []): array
    {
        // Validator rules
        $request->validate(array_merge([
            'user_id' => 'required|string'
        ], $validator));

        // Return
        return [
            'user_id' => $request->input('user_id')
        ];
    }
}
