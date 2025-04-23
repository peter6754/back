<?php

use App\Http\Controllers\Admin\SecondaryuserCrudController;
use App\Http\Controllers\Admin\TranslationCrudController;
use App\Http\Controllers\Admin\UserBanCrudController;
use App\Http\Controllers\Admin\UserPhotosPageController;
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
    Route::crud('transaction-process', 'TransactionProcessCrudController');
    Route::crud('mail-queue', 'MailQueueCrudController');
    Route::crud('mail-template', 'MailTemplateCrudController');
    Route::crud('city-analytics', 'CityAnalyticsCrudController');
    Route::crud('bot-analytics', 'BotAnalyticsCrudController');


// Роуты для управления фотографиями пользователей
    Route::prefix('users/{user}')->group(function () {
        Route::get('photos', [UserPhotosPageController::class, 'index'])
            ->name('admin.users.photos.index');
        Route::post('photos', [UserPhotosPageController::class, 'store'])
            ->name('admin.users.photos.store');
        Route::patch('photos/set-main', [UserPhotosPageController::class, 'setMain'])
            ->name('admin.users.photos.set-main');
        Route::delete('photos/delete', [UserPhotosPageController::class, 'destroy'])
            ->name('admin.users.photos.destroy');
    });

    // Дополнительные маршруты
    Route::get('mail-template/{id}/preview', 'MailTemplateCrudController@preview')
        ->name('mail-template.preview');
    Route::get('mail-queue/{id}/resend', 'MailQueueCrudController@resend')
        ->name('mail-queue.resend');

    // Отправка писем
    Route::get('send-mail', 'SendMailController@showForm')
        ->name('send-mail.form');
    Route::post('send-mail', 'SendMailController@send')
        ->name('send-mail.send');
    Route::get('send-mail/template/{id}', 'SendMailController@getTemplate')
        ->name('send-mail.template');

    Route::crud('translation', 'TranslationCrudController');
    Route::post('translation/export', [TranslationCrudController::class, 'exportTranslations'])
        ->name('translation.export');
    Route::crud('user-ban', 'UserBanCrudController');
    Route::get('user-ban/{id}/unban', [UserBanCrudController::class, 'unban'])->name('user-ban.unban');
    Route::post('secondaryuser/unban/{id}', [SecondaryuserCrudController::class, 'unban'])->name('secondaryuser.unban');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
