<?php

use App\Http\Controllers\Application\PricesController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\Payments\PaymentsController;
use App\Http\Controllers\Payments\StatusesController;
use App\Http\Controllers\Recommendations\RecommendationsController;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use OpenApi\Generator;

// Recommendations routes
Route::prefix('recommendations')->middleware('auth')->group(function () {
    // Get recommendations
    Route::get('/', [RecommendationsController::class, 'getRecommendations']);

    // Get top profiles
    Route::get('top-profiles', [RecommendationsController::class, 'getTopProfiles']);

    // Superlike action
    Route::post('superlike', [RecommendationsController::class, 'superlike']);

    // Rollback action
    Route::post('rollback', [RecommendationsController::class, 'rollback']);

    // Delete matched
    Route::delete('match/{id}', [RecommendationsController::class, 'match']);

    // Dislike action
    Route::post('dislike', [RecommendationsController::class, 'dislike']);

    // Like action
    Route::post('like', [RecommendationsController::class, 'like']);
});

// Payments system routes
Route::prefix('payment')->group(function () {

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

// Prices routes
Route::prefix('application')->middleware('auth')->group(function () {
    Route::prefix('prices')->group(function () {
        Route::get('subscriptions/{id}', [PricesController::class, 'getSubscriptions'])
            ->name('prices.subscription');
        Route::get('service-package', [PricesController::class, 'getPackages'])
            ->name('prices.service-package');
        Route::get('gifts/categories', [PricesController::class, 'getGiftCategories'])
            ->name('prices.gift.categories');
        Route::get('gifts/{id}', [PricesController::class, 'getGifts'])
            ->name('prices.gifts');
    });
});

// Chat/Conversations API routes
Route::prefix('api/conversations')->middleware('auth')->group(function () {
    // получить все чаты текущего юзера
    Route::get('/', [ChatController::class, 'getConversations']);

    // Получить сообщения в чате
    Route::get('/messages/{chat_id}', [ChatController::class, 'getMessages']);

    // Загрузить медиа файл в чат
    Route::post('/{chat_id}/media', [ChatController::class, 'uploadMedia']);

    // Отметить все сообщения в чате как прочитанные
    Route::post('/read-messages/{chat_id}', [ChatController::class, 'markMessagesAsRead']);

    // Получить непрочитанные сообщения в чате
    Route::get('/{chat_id}/unread-messages-status', [ChatController::class, 'getUnreadMessagesStatus']);
    // Создать чат с пользователем
    Route::post('/{user_id}', [ChatController::class, 'createConversation']);

    // Удалить чат с пользователем
    Route::delete('/{chat_id}', [ChatController::class, 'deleteConversation']);

    // Закрепить/открепить чат
    Route::post('/pin/{chat_id}', [ChatController::class, 'togglePinConversation']);
});

// Chat message routes (WebSocket-enabled)
Route::prefix('chat')->middleware('auth')->group(function () {
    Route::post('/send-message', [ChatController::class, 'sendMessage']);
    Route::post('/send-media', [ChatController::class, 'sendMedia']);
    Route::post('/send-gift', [ChatController::class, 'sendGift']);
});

// Auth routes
Route::prefix('auth')->group(function () {
    // Phone login
    Route::post('login', [AuthController::class, 'login'])->name('auth.verify');
    Route::post('verify-login', [AuthController::class, 'verify'])->name('auth.login');

    // Telegram
    Route::post('telegram', [AuthController::class, 'telegram'])->name('auth.telegram');

    // Social
    Route::get('social/list', [AuthController::class, 'socialLinks'])->name('auth.social.list');
    Route::any('social/{provider}/callback', [AuthController::class, 'socialCallback']);
    Route::get('social/{provider}', function ($provider) {
        if (
            ! empty(config("services.{$provider}.client_id")) ||
            ! empty(config("services.{$provider}.redirect"))
        ) {
            return Socialite::driver($provider)->redirectUrl(
                url(config("services.{$provider}.redirect"))
            )->redirect();
        }
        abort(404);
    });
});

// Default routes
Route::get('swagger', function () {
    $getGenerator = Generator::scan([
        base_path().'/App/Http/Controllers',
    ]);

    return response($getGenerator->toYaml());
});
Route::get('/', function () {
    return view('welcome');
});
