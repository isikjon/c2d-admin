<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="C2D Admin API",
 *     version="1.0.0",
 *     description="API для админки ИИ-агента и рассылки сообщений",
 *     @OA\Contact(
 *         email="admin@c2d.ru"
 *     )
 * )
 * @OA\Server(
 *     url="/api",
 *     description="API Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * @OA\Tag(name="Auth", description="Авторизация")
 * @OA\Tag(name="Campaigns", description="Рекламные кампании")
 * @OA\Tag(name="Clients", description="Клиенты")
 * @OA\Tag(name="Dialogs", description="Диалоги")
 * @OA\Tag(name="Statistics", description="Статистика")
 * @OA\Tag(name="Webhooks", description="Вебхуки")
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
