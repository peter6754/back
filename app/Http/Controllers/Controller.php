<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *       version="1.0.1",
 *       title="TinderOne API",
 *       description="Payment service documentaion",
 *       @OA\Contact(
 *           email="enternetacum@yandex.ru"
 *       ),
 *   )
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Local development server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * @OA\Tag(
 *       name="Customer Authorization",
 *   )
 * @OA\Tag(
 *        name="Recommendations",
 *   )
 * @OA\Tag(
 *        name="Payments",
 *  )
 *  @OA\Tag(
 *       name="App Settings"
 *   )
 * @OA\Tag(
 *        name="User Settings"
 *    )
 */
abstract class Controller
{
    //
}
