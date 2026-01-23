<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dialog;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     *             @OA\Property(property="status", type="string", example="ready_to_buy"),
     *             @OA\Property(property="metadata", type="object")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Статус обновлен")
     * )
     */
    public function updateStatus(Request $request, Dialog $dialog): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string',
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
     *             required={"chat_id","status"},
     *             @OA\Property(property="chat_id", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="client_phone", type="string"),
     *             @OA\Property(property="collected_data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function aiAgentWebhook(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'chat_id' => 'required|string',
            'status' => 'required|string',
            'client_phone' => 'nullable|string',
            'collected_data' => 'nullable|array',
        ]);

        $dialog = Dialog::where('external_chat_id', $validated['chat_id'])->first();

        if (!$dialog && isset($validated['client_phone'])) {
            $client = Client::where('phone', $validated['client_phone'])->first();
            if ($client) {
                $dialog = Dialog::create([
                    'client_id' => $client->id,
                    'external_chat_id' => $validated['chat_id'],
                    'channel' => 'whatsapp',
                    'current_status' => 'new',
                    'is_ai_active' => true,
                ]);
            }
        }

        if ($dialog) {
            $dialog->updateStatus($validated['status'], 'ai_agent', $validated['collected_data'] ?? []);
            $dialog->update(['is_ai_active' => true, 'last_message_at' => now()]);
        }

        return response()->json(['success' => true, 'dialog_id' => $dialog?->id]);
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
}
