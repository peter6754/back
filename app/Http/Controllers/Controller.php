<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *      version="1.0.1",
 *      title="TinderOne API",
 *      description="Payment service documentaion",
 *      @OA\Contact(
 *          email="enternetacum@yandex.ru"
 *      ),
 *  )
 * @OA\Tag(
 *      name="Customer Authorization",
 *  )
 * @OA\Tag(
 *       name="Recommendations",
 *  )
 * @OA\Tag(
 *      name="Payments",
 *  )
 */
abstract class Controller
{
    //
}
