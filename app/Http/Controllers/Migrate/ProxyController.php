<?php

namespace App\Http\Controllers\Migrate;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProxyController extends Controller
{
    /**
     * @var string
     */
    protected string $proxyBaseUrl;

    /**
     *
     */
    public function __construct()
    {
        $this->proxyBaseUrl = rtrim(config('proxy.target_url'), '/');
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        // Получаем полный путь запроса
        $path = $request->path();
        $method = strtolower($request->method());

        // Формируем URL для проксирования
        $targetUrl = $this->proxyBaseUrl . '/' . $path;

        // Перенаправляем запрос с сохранением всех заголовков и параметров
        try {
            $response = Http::withHeaders($request->headers->all())
                ->withQueryParameters($request->query())
                ->{$method}(
                    $targetUrl,
                    $method === 'get' ? [] : $request->all()
                );

            // Возвращаем ответ от целевого сервиса
            return response(
                $response->body(),
                $response->status(),
//                $response->headers()
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Proxy error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
