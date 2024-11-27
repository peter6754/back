<?php

use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Payments\StatusesController;
use App\Http\Controllers\Payments\PaymentsController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use OpenApi\Generator;

// Payments system routes
Route::prefix('payment')->group(function () {

    // Services init payment
    Route::post('service-package', [PaymentsController::class, 'servicePackage'])->name('payment.service-package');
    Route::post('subscription', [PaymentsController::class, 'subscription'])->name('payment.subscription');
    Route::post('gift', [PaymentsController::class, 'gift'])->name('payment.gift');

    // Service checking
    Route::get('status/{id}', [PaymentsController::class, 'status'])->name('payment.status');

    // Statuses\
    Route::get('{provider}/result/{event?}', [StatusesController::class, 'resultCallback'])->name('statuses.callback');
    Route::get('{provider}/success', [StatusesController::class, 'success'])->name('statuses.success');
    Route::get('{provider}/fail', [StatusesController::class, 'fail'])->name('statuses.fail');
});

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('auth.verify');
    Route::post('verify-login', [AuthController::class, 'verify'])->name('auth.login');

    Route::post('social/{provider}/callback', [AuthController::class, 'socialCallback']);
    Route::get('social/{provider}/callback', [AuthController::class, 'socialCallback']);
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


