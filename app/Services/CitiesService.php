<?php

namespace App\Services;

use App\Models\UserCities;
use Illuminate\Database\Eloquent\Collection;

class CitiesService
{
    /**
     * Get unique cities with optional search filtering
     *
     * @param string|null $searchQuery Search query to filter cities by name
     * @return Collection
     */
    public function getCities(?string $searchQuery = null): Collection
    {
        $query = UserCities::select('formatted_address')->distinct();

        if ($searchQuery) {
            $query->where('formatted_address', 'LIKE', $searchQuery . '%');
        }

        return $query->get();
    }
}