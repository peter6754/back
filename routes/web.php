<?php

use App\Http\Controllers\Payments\RobokassaController;
use Illuminate\Support\Facades\Route;

// Robokassa routes
Route::prefix('payment/robokassa')->group(function () {
    Route::get('success', [RobokassaController::class, 'success'])->name('robokassa.success');
    Route::get('result', [RobokassaController::class, 'result'])->name('robokassa.result');
    Route::get('fail', [RobokassaController::class, 'fail'])->name('robokassa.fail');
});

// Payment routes
Route::prefix('payment')->group(function () {
    Route::get('success', [PaymentsController::class, 'success'])->name('payment.success');
    Route::get('result', [PaymentsController::class, 'result'])->name('robokassa.result');
    Route::get('fail', [PaymentsController::class, 'fail'])->name('robokassa.fail');
});

// Default routes
Route::get('/', function () {
    return view('welcome');
});


