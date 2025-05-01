<?php

use App\Http\Controllers\Payments\RobokassaController;
use Illuminate\Support\Facades\Route;


// Custom Routes
Route::get('/payment/success', [RobokassaController::class, 'success'])
    ->name('robokassa.success');
Route::get('/payment/fail', [RobokassaController::class, 'fail'])
    ->name('robokassa.fail');
Route::get('/payment/result', [RobokassaController::class, 'result'])
    ->name('robokassa.result');

// Default routes
Route::get('/', function () {
    return view('welcome');
});


