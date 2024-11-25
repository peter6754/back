<?php

use Laravel\Telescope\Telescope;
use App\Http\Controllers\Payments\PaymentsController;
use App\Http\Controllers\Payments\StatusesController;
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


