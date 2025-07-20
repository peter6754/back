<?php

namespace App\DTO;

use Spatie\LaravelData\Data;

class GetRecommendationsDto extends Data
{
    public function __construct(
        public ?string $interest_id,
        public ?string $min_photo_count,
        public ?string $is_verified,
        public ?string $has_info,
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            interest_id: $request->input('interest_id'),
            min_photo_count: $request->input('min_photo_count'),
            is_verified: $request->input('is_verified'),
            has_info: $request->input('has_info'),
        );
    }
}
