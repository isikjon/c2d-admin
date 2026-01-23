<?php

namespace App\OpenApi;

/**
 * @OA\Info(
 *     title="C2D Admin API",
 *     version="1.0.0",
 *     description="API для админки ИИ-агента и рассылки сообщений"
 * )
 * @OA\Server(url="/api", description="API Server")
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Post(
 *     path="/login",
 *     summary="Авторизация пользователя",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","password"},
 *             @OA\Property(property="email", type="string", format="email", example="admin@c2d.ru"),
 *             @OA\Property(property="password", type="string", example="Admin2026!")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Успешная авторизация"),
 *     @OA\Response(response=422, description="Неверные учетные данные")
 * )
 *
 * @OA\Post(
 *     path="/logout",
 *     summary="Выход из системы",
 *     tags={"Auth"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Успешный выход")
 * )
 *
 * @OA\Get(
 *     path="/me",
 *     summary="Получить текущего пользователя",
 *     tags={"Auth"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Данные пользователя")
 * )
 *
 * @OA\Get(
 *     path="/campaigns",
 *     summary="Список рекламных кампаний",
 *     tags={"Campaigns"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="status", in="query", description="Фильтр по статусу", @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="Список кампаний")
 * )
 *
 * @OA\Post(
 *     path="/campaigns",
 *     summary="Создать рекламную кампанию",
 *     tags={"Campaigns"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name"},
 *             @OA\Property(property="name", type="string", example="Новогодняя акция"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="product_id", type="integer"),
 *             @OA\Property(property="trigger_action", type="string"),
 *             @OA\Property(property="target_url", type="string"),
 *             @OA\Property(property="max_retries", type="integer", example=3)
 *         )
 *     ),
 *     @OA\Response(response=201, description="Кампания создана")
 * )
 *
 * @OA\Get(
 *     path="/campaigns/{id}",
 *     summary="Получить кампанию по ID",
 *     tags={"Campaigns"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Данные кампании")
 * )
 *
 * @OA\Put(
 *     path="/campaigns/{id}",
 *     summary="Обновить кампанию",
 *     tags={"Campaigns"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(required=true, @OA\JsonContent()),
 *     @OA\Response(response=200, description="Кампания обновлена")
 * )
 *
 * @OA\Delete(
 *     path="/campaigns/{id}",
 *     summary="Удалить кампанию",
 *     tags={"Campaigns"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Кампания удалена")
 * )
 *
 * @OA\Post(
 *     path="/campaigns/{id}/launch",
 *     summary="Запустить кампанию",
 *     tags={"Campaigns"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Кампания запущена")
 * )
 *
 * @OA\Post(
 *     path="/campaigns/{id}/pause",
 *     summary="Приостановить кампанию",
 *     tags={"Campaigns"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Кампания приостановлена")
 * )
 *
 * @OA\Get(
 *     path="/clients",
 *     summary="Список клиентов",
 *     tags={"Clients"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="search", in="query", description="Поиск по телефону/имени/email", @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="Список клиентов")
 * )
 *
 * @OA\Post(
 *     path="/clients",
 *     summary="Создать клиента",
 *     tags={"Clients"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"phone"},
 *             @OA\Property(property="phone", type="string", example="+79001234567"),
 *             @OA\Property(property="first_name", type="string"),
 *             @OA\Property(property="last_name", type="string"),
 *             @OA\Property(property="email", type="string")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Клиент создан")
 * )
 *
 * @OA\Get(
 *     path="/clients/{id}",
 *     summary="Получить клиента по ID",
 *     tags={"Clients"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Данные клиента")
 * )
 *
 * @OA\Put(
 *     path="/clients/{id}",
 *     summary="Обновить клиента",
 *     tags={"Clients"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Клиент обновлен")
 * )
 *
 * @OA\Delete(
 *     path="/clients/{id}",
 *     summary="Удалить клиента",
 *     tags={"Clients"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Клиент удален")
 * )
 *
 * @OA\Post(
 *     path="/clients/import",
 *     summary="Импорт клиентов из JSON",
 *     tags={"Clients"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"clients"},
 *             @OA\Property(property="clients", type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="phone", type="string", example="+79001234567"),
 *                     @OA\Property(property="first_name", type="string"),
 *                     @OA\Property(property="last_name", type="string")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=200, description="Импорт завершен")
 * )
 *
 * @OA\Get(
 *     path="/clients/export",
 *     summary="Экспорт клиентов",
 *     tags={"Clients"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="JSON со списком клиентов")
 * )
 *
 * @OA\Get(
 *     path="/dialogs",
 *     summary="Список диалогов",
 *     tags={"Dialogs"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="campaign_id", in="query", @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Список диалогов")
 * )
 *
 * @OA\Get(
 *     path="/dialogs/{id}",
 *     summary="Получить диалог по ID",
 *     tags={"Dialogs"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Данные диалога с историей статусов")
 * )
 *
 * @OA\Post(
 *     path="/dialogs/{id}/status",
 *     summary="Обновить статус диалога",
 *     tags={"Dialogs"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"status"},
 *             @OA\Property(property="status", type="string", example="ready_to_buy"),
 *             @OA\Property(property="metadata", type="object")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Статус обновлен")
 * )
 *
 * @OA\Get(
 *     path="/statistics/campaigns",
 *     summary="Статистика по кампаниям",
 *     tags={"Statistics"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Статистика кампаний")
 * )
 *
 * @OA\Get(
 *     path="/statistics/dialogs",
 *     summary="Статистика по диалогам",
 *     tags={"Statistics"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Статистика диалогов по статусам")
 * )
 *
 * @OA\Get(
 *     path="/statistics/export",
 *     summary="Экспорт полной статистики",
 *     tags={"Statistics"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="JSON с полной статистикой")
 * )
 *
 * @OA\Post(
 *     path="/webhooks/ai-agent",
 *     summary="Webhook от ИИ-агента",
 *     tags={"Webhooks"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"chat_id","status"},
 *             @OA\Property(property="chat_id", type="string", example="chat_123"),
 *             @OA\Property(property="status", type="string", example="presentation"),
 *             @OA\Property(property="client_phone", type="string", example="+79001234567"),
 *             @OA\Property(property="collected_data", type="object")
 *         )
 *     ),
 *     @OA\Response(response=200, description="OK")
 * )
 *
 * @OA\Post(
 *     path="/webhooks/telegram",
 *     summary="Webhook от Telegram",
 *     tags={"Webhooks"},
 *     @OA\Response(response=200, description="OK")
 * )
 */
class OpenApiSpec
{
}
