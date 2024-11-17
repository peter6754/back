<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="TinderOne API",
 *      description="Payment service documentaion",
 *      @OA\Contact(
 *          email="enternetacum@yandex.ru"
 *      ),
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     bearerFormat="JWT",
 *     scheme="bearer",
 *     type="http"
 * )
 */
abstract class Controller
{
    //
}
