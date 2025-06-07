<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="TinderOne API",
 *      description="Payment service documentaion",
 *      @OA\Contact(
 *          email="enternetacum@yandex.ru"
 *      ),
 *  )
 * @OA\Tag(
 *      name="Авторизация / Регистрация",
 *      description="Модуль авторизации в приложении"
 *  )
 * @OA\Tag(
 *       name="Рекомендации",
 *       description="Списки рекомендаций и прочего"
 *  )
 * @OA\Tag(
 *      name="Платежи",
 *      description="Управление платежами"
 *  )
 */
abstract class Controller
{
    //
}
