<?php

use App\Http\Controllers\Application\PricesController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\Migrate\ProxyController;
use App\Http\Controllers\Payments\PaymentsController;
use App\Http\Controllers\Payments\StatusesController;
use App\Http\Controllers\Recommendations\RecommendationsController;
use App\Http\Controllers\Users\InfoController;
use App\Http\Controllers\Users\ReferenceDataController;
use App\Http\Controllers\Users\SettingsController;
use App\Http\Controllers\Users\UserPhotosController;
use App\Http\Controllers\Users\UsersController;
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
    Route::get('/{chat_id}/messages', [ChatController::class, 'getMessages']);

    // Отправить сообщение в чат текст и файлы
    Route::post('/send-messages', [ChatController::class, 'sendMessage']);

    // Загрузить медиа файл в чат
    Route::post('/{chat_id}/media', [ChatController::class, 'uploadMedia']);

    // Отметить все сообщения в чате как прочитанные
    Route::post('/read-messages/{chat_id}', [ChatController::class, 'markMessagesAsRead']);

    // Создать чат с пользователем
    Route::post('/', [ChatController::class, 'createConversation']);

    // Удалить чат с пользователем
    Route::delete('/{chat_id}', [ChatController::class, 'deleteConversation']);

    // Закрепить/открепить чат
    Route::post('/pin/{chat_id}', [ChatController::class, 'togglePinConversation']);

    // Получить социальные сети пользователя
    Route::get('/social-accounts', action: [ChatController::class, 'getUserSocialAccounts']);

    // Отправить социальные контакты в чат
    Route::get('/send-social-contacts', [ChatController::class, 'sendSocialContacts']);
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

// Users routes
Route::prefix('users')->middleware('auth')->group(function () {
    // Users Profile
    Route::get('info/{id}', [InfoController::class, 'getUser']);

    // User info in registration
    Route::post('/infoRegistration', [UsersController::class, 'updateUserInfoRegistration']);

    Route::get('packagesInfo', [UsersController::class, 'getUserPackages']);

    // update users coordinates
    Route::post('coordinates', [UsersController::class, 'updateCoordinates']);

    // My Profile
    Route::get('profile', [UsersController::class, 'getAccountInformation']);
    Route::put('profile', [UsersController::class, 'updateAccountInformation']);
    Route::get('likes', [UsersController::class, 'getUserLikes']);

    // Photos route
    Route::prefix('photos')->group(function () {
        // CRUD photos
        Route::delete('/', [UserPhotosController::class, 'deletePhoto']);
        Route::post('/', [UserPhotosController::class, 'addPhotos']);
        Route::get('/', [UserPhotosController::class, 'getPhotos']);

        // Main photo route
        Route::patch('main', [UserPhotosController::class, 'setMainPhoto']);
        Route::get('main', [UserPhotosController::class, 'getMainPhoto']);
    });

    // Settings
    Route::prefix('settings')->group(function () {
        // Tokens for push notifications
        Route::delete('token', [SettingsController::class, 'deleteToken']);
        Route::post('token', [SettingsController::class, 'addToken']);

        // Filters
        Route::put('filter', [SettingsController::class, 'setFilter']);
        Route::get('filter', [SettingsController::class, 'getFilter']);

        // Cities filter
        Route::get('cities', [SettingsController::class, 'allCities']);

        // Other
        Route::get('connected-accounts', [SettingsController::class, 'getConnectedAccounts']);
        Route::get('blocked-contacts', [SettingsController::class, 'getBlockedContacts']);
        Route::post('blocked-contacts', [SettingsController::class, 'createBlockedContact']);
        Route::delete('blocked-contacts', [SettingsController::class, 'deleteBlockedContact']);
    });

    // Справочные данные
    Route::prefix('reference-data')->group(function () {
        Route::get('interests', [ReferenceDataController::class, 'getInterests']);
        Route::get('relationship-preferences', [ReferenceDataController::class, 'getRelationshipPreferences']);
        Route::get('genders', [ReferenceDataController::class, 'getGenders']);
        Route::get('zodiac-signs', [ReferenceDataController::class, 'getZodiacSigns']);
        Route::get('family-statuses', [ReferenceDataController::class, 'getFamilyStatuses']);
        Route::get('education', [ReferenceDataController::class, 'getEducationOptions']);
        Route::get('family', [ReferenceDataController::class, 'getFamilyPlans']);
        Route::get('communication', [ReferenceDataController::class, 'getCommunicationOptions']);
        Route::get('love-languages', [ReferenceDataController::class, 'getLoveLanguages']);
        Route::get('pets', [ReferenceDataController::class, 'getPets']);
        Route::get('alcohol', [ReferenceDataController::class, 'getAlcohol']);
        Route::get('smoking', [ReferenceDataController::class, 'getSmoking']);
        Route::get('sport', [ReferenceDataController::class, 'getSport']);
        Route::get('food', [ReferenceDataController::class, 'getFood']);
        Route::get('social-network', [ReferenceDataController::class, 'getSocialNetwork']);
        Route::get('sleep', [ReferenceDataController::class, 'getSleep']);
        Route::get('orientations', [ReferenceDataController::class, 'getOrientations']);
        Route::get('clubs', [ReferenceDataController::class, 'getClubs']);
    });
});

Route::prefix('users')->group(function () {
    Route::get('email-exist', [UsersController::class, 'getEmailExistenceStatus']);
});

// Image admin view and edit
Route::get('admin/image-proxy/{imageId}', [
    App\Http\Controllers\Admin\ImageProxyController::class, 'show',
])->where('imageId', '.*')->middleware(['web']);

// Default routes
Route::get('swagger', function () {
    $getGenerator = Generator::scan([
        base_path().'/app/Http/Controllers',
    ]);

    return response($getGenerator->toYaml());
});

Route::get('/', function () {
    return view('welcome');
});

// Fallback route - будет срабатывать, если ни один другой маршрут не совпал
Route::any('{any}', [ProxyController::class, 'handle'])
    ->where('any', '.*')
    ->middleware('proxy');
