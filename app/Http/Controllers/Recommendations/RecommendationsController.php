<?php

namespace App\Http\Controllers\Recommendations;

use App\Http\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\RecommendationService;

class RecommendationsController extends Controller
{
    use ApiResponseTrait;


    public function getTopProfiles(Request $request)
    {
        // Checking auth user
        $customer = $this->checkingAuth();


        if (!empty($request->get('join'))) {
            $data = RecommendationService::getPotentialMatchesOptimized($customer);
        } else {
            $data = RecommendationService::getPotentialMatches($customer);
        }
        var_dump($data);
    }
}
