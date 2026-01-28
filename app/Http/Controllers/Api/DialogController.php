<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dialog;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DialogController extends Controller
{
    /**
     * @OA\Get(
     *     path="/dialogs",
     *     summary="Список диалогов",
     *     tags={"Dialogs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="campaign_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Список диалогов")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Dialog::whereHas('client', function($q) use ($user) {
            $q->where('organization_id', $user->organization_id);
        })->with(['client', 'campaign']);

        if ($request->has('status')) {
            $query->where('current_status', $request->status);
        }
        if ($request->has('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        $dialogs = $query->orderBy('updated_at', 'desc')->paginate(50);
        return response()->json(['success' => true, 'data' => $dialogs]);
    }

    /**
     * @OA\Get(
     *     path="/dialogs/{id}",
     *     summary="Получить диалог",
     *     tags={"Dialogs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Данные диалога")
     * )
     */
    public function show(Dialog $dialog): JsonResponse
    {
        $dialog->load(['client', 'campaign', 'statuses']);
        return response()->json(['success' => true, 'data' => $dialog]);
    }

    /**
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
     *             @OA\Property(property="status", type="string", example="ready_to_process"),
     *             @OA\Property(property="metadata", type="object")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Статус обновлен")
     * )
     */
    public function updateStatus(Request $request, Dialog $dialog): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(Dialog::ALL_STATUSES)],
            'metadata' => 'nullable|array',
        ]);

        $dialog->updateStatus($validated['status'], 'manual', $validated['metadata'] ?? []);
        return response()->json(['success' => true, 'message' => 'Статус обновлён', 'data' => $dialog->fresh(['statuses'])]);
    }

    /**
     * @OA\Post(
     *     path="/webhooks/ai-agent",
     *     summary="Webhook от ИИ-агента",
     *     tags={"Webhooks"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"chat_id","client_phone","status"},
     *             @OA\Property(property="chat_id", type="string", example="ai_chat_12345"),
     *             @OA\Property(property="client_phone", type="string", example="+79001234567"),
     *             @OA\Property(property="status", type="string", example="presentation"),
     *             @OA\Property(property="status_data", type="object",
     *                 @OA\Property(property="callback_date", type="string", example="2026-01-30"),
     *                 @OA\Property(property="callback_time", type="string", example="14:00"),
     *                 @OA\Property(property="no_response_minutes", type="integer", example=120)
     *             ),
     *             @OA\Property(property="collected_data", type="object",
     *                 @OA\Property(property="name", type="string", example="Иван Иванов"),
     *                 @OA\Property(property="product_type", type="string", example="osago"),
     *                 @OA\Property(property="product_data", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function aiAgentWebhook(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'chat_id' => 'required|string',
            'client_phone' => 'required|string',
            'status' => ['required', 'string', Rule::in(Dialog::ALL_STATUSES)],
            'status_data' => 'nullable|array',
            'status_data.callback_date' => 'nullable|date',
            'status_data.callback_time' => 'nullable|string',
            'status_data.no_response_minutes' => 'nullable|integer',
            'collected_data' => 'nullable|array',
            'collected_data.name' => 'nullable|string',
            'collected_data.product_type' => 'nullable|string|in:mortgage,kasko,osago,property',
            'collected_data.product_data' => 'nullable|array',
        ]);

        $dialog = Dialog::where('external_chat_id', $validated['chat_id'])->first();

        // Поиск или создание клиента
        $client = Client::where('phone', $validated['client_phone'])->first();

        if (!$dialog && $client) {
            $dialog = Dialog::create([
                'client_id' => $client->id,
                'external_chat_id' => $validated['chat_id'],
                'channel' => 'whatsapp',
                'current_status' => Dialog::STATUS_NEW,
                'is_ai_active' => true,
            ]);
        }

        if (!$dialog) {
            return response()->json([
                'success' => false,
                'message' => 'Клиент не найден в системе'
            ], 404);
        }

        // Собираем метаданные для статуса
        $metadata = [];

        // Добавляем status_data
        if (!empty($validated['status_data'])) {
            $metadata['status_data'] = $validated['status_data'];
        }

        // Добавляем collected_data
        if (!empty($validated['collected_data'])) {
            $metadata['collected_data'] = $validated['collected_data'];

            // Обновляем данные клиента если есть имя
            if (!empty($validated['collected_data']['name']) && $client) {
                $nameParts = explode(' ', $validated['collected_data']['name']);
                $updateData = [];
                if (isset($nameParts[0])) $updateData['last_name'] = $nameParts[0];
                if (isset($nameParts[1])) $updateData['first_name'] = $nameParts[1];
                if (isset($nameParts[2])) $updateData['patronymic'] = $nameParts[2];
                if (!empty($updateData)) {
                    $client->update($updateData);
                }
            }
        }

        // Обновляем статус диалога
        $dialog->updateStatus($validated['status'], 'ai_agent', $metadata);
        $dialog->update([
            'is_ai_active' => true,
            'last_message_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Статус обновлён',
            'dialog_id' => $dialog->id
        ]);
    }

    /**
     * @OA\Post(
     *     path="/webhooks/telegram",
     *     summary="Webhook от Telegram",
     *     tags={"Webhooks"},
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function telegramWebhook(Request $request): JsonResponse
    {
        \Log::info('Telegram webhook', $request->all());
        return response()->json(['ok' => true]);
    }

    /**
     * @OA\Get(
     *     path="/dialogs/statuses",
     *     summary="Получить список всех статусов",
     *     tags={"Dialogs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Список статусов")
     * )
     */
    public function getStatuses(): JsonResponse
    {
        $statuses = [];
        foreach (Dialog::ALL_STATUSES as $status) {
            $statuses[] = [
                'value' => $status,
                'label' => Dialog::getStatusLabel($status),
                'is_crm_status' => in_array($status, Dialog::CRM_STATUSES),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $statuses
        ]);
    }
}
