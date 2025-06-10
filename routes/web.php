<?php

use App\Http\Controllers\Recommendations\RecommendationsController;
use App\Http\Controllers\Payments\StatusesController;
use App\Http\Controllers\Payments\PaymentsController;
use App\Http\Controllers\Auth\AuthController;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Route;
use OpenApi\Generator;

// Recommendations routes
Route::prefix('recommendations')->middleware(['auth', 'cors'])->group(function () {
    // Get recommendations
    Route::get('/', [RecommendationsController::class, 'getRecommendations']);

    // Get top profiles
    Route::get('top-profiles', [RecommendationsController::class, 'getTopProfiles']);

    // Delete matched user
    Route::delete('match/{id}', [RecommendationsController::class, 'deleteMatchedUser']);

    // Like action
    Route::post('like', [RecommendationsController::class, 'like']);

    // Dislike action
    Route::post('dislike', [RecommendationsController::class, 'dislike']);

    // Rollback action
    Route::post('rollback', [RecommendationsController::class, 'rollback']);

    // Superlike action
    Route::post('superlike', [RecommendationsController::class, 'superlike']);
});

// Payments system routes
Route::prefix('payment')->middleware(['cors'])->group(function () {

    // Services init payment
    Route::post('service-package', [PaymentsController::class, 'servicePackage'])
        ->name('payment.service-package')
        ->middleware('auth');
    Route::post('subscription', [PaymentsController::class, 'subscription'])
        ->name('payment.subscription')
        ->middleware('auth');
    Route::post('gift', [PaymentsController::class, 'gift'])
        ->name('payment.userGift')
        ->middleware('auth');

    // Service checking
    Route::get('status/{id}', [PaymentsController::class, 'status'])
        ->name('payment.checkStatus')
        ->middleware('auth');

    // Statuses
    Route::get('{provider}/result/{event?}', [StatusesController::class, 'resultCallback'])
        ->name('statuses.callback');
    Route::get('{provider}/success', [StatusesController::class, 'success'])
        ->name('statuses.success');
    Route::get('{provider}/fail', [StatusesController::class, 'fail'])
        ->name('statuses.fail');
});

// Auth routes
Route::prefix('auth')->middleware(['cors'])->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('auth.verify');
    Route::post('verify-login', [AuthController::class, 'verify'])->name('auth.login');

    Route::any('social/{provider}/callback', [AuthController::class, 'socialCallback']);
    Route::get('social/{provider}', function ($provider) {
        if (!empty(config("services.{$provider}.client_id"))) {
            return Socialite::driver($provider)->redirect();
        }
        abort(404);
    });
});

// Default routes
Route::get('swagger', function () {
    $getGenerator = Generator::scan([
        base_path() . "/App/Http/Controllers",
    ]);
    return response($getGenerator->toYaml());
});
Route::get('/', function () {
    return view('welcome');
});


