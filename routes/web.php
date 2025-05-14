<?php

use App\Http\Controllers\Payments\PaymentsController;
use App\Http\Controllers\Payments\StatusesController;
use Illuminate\Support\Facades\Route;

// Payments system routes
Route::prefix('payment')->group(function () {

    // Services init payment
    Route::get('service-package', [PaymentsController::class, 'servicePackage'])->name('payment.service-package');
    Route::post('unsubscription', [PaymentsController::class, 'unsubscription'])->name('payment.unsubscription');
    Route::post('subscription', [PaymentsController::class, 'subscription'])->name('payment.subscription');
    Route::get('gift', [PaymentsController::class, 'gift'])->name('payment.gift');

    // Service checking
    Route::get('status/{id}', [PaymentsController::class, 'status'])->name('payment.status');

    // Statuses
    Route::get('{provider}/result', [StatusesController::class, 'resultCallback'])->name('statuses.callback');
    Route::get('{provider}/success', [StatusesController::class, 'success'])->name('statuses.success');
    Route::get('{provider}/fail', [StatusesController::class, 'fail'])->name('statuses.fail');

    // ToDo: Посмотреть позже, а нужно ли
    Route::get('recurring', [PaymentsController::class, 'recurring'])->name('payment.recurring');
});


// Default routes
Route::get('/', function () {
    return view('welcome');
});


