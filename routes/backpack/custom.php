<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.
Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => ['web', backpack_middleware()],
    'namespace'  => 'Backpack\PermissionManager\app\Http\Controllers',
], function () {
    Route::crud('permission', 'PermissionCrudController');
    Route::crud('role', 'RoleCrudController');
});

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::get('statistics', function () {
        return view(backpack_view('statistics'));
    })->name('backpack.statistics');
    Route::crud('secondaryuser', 'SecondaryuserCrudController');
    Route::crud('user', 'UserCrudController');
    Route::crud('bought-subscriptions', 'BoughtSubscriptionsCrudController');
    Route::crud('verification-requests', 'VerificationRequestsCrudController');
    Route::crud('in-queue-for-delete-user', 'InQueueForDeleteUserCrudController');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
