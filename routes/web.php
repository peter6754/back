<?php

use App\Http\Controllers\Payments\RobokassaController;
use App\Http\Controllers\Payments\PaymentsController;
use Illuminate\Support\Facades\Route;



// Payments system routes
Route::prefix('payment')->group(function () {
    Route::get('service-package', [PaymentsController::class, 'servicePackage'])->name('payment.service-package');
    Route::get('subscription', [PaymentsController::class, 'subscription'])->name('payment.subscription');
    Route::get('recurring', [PaymentsController::class, 'recurring'])->name('payment.recurring');
    Route::get('status/{id}', [PaymentsController::class, 'status'])->name('payment.status');
    Route::get('gift', [PaymentsController::class, 'gift'])->name('payment.gift');

    Route::prefix('robokassa')->group(function () {
        Route::get('success', [RobokassaController::class, 'success'])->name('robokassa.success');
        Route::get('result', [RobokassaController::class, 'result'])->name('robokassa.result');
        Route::get('fail', [RobokassaController::class, 'fail'])->name('robokassa.fail');
    });
});


// Default routes
Route::get('/', function () {
    return view('welcome');
});


