<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /**
     * @OA\Get(
     *     path="/campaigns",
     *     summary="Список рекламных кампаний",
     *     tags={"Campaigns"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", description="Фильтр по статусу", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Список кампаний")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Campaign::where('organization_id', $user->organization_id)
            ->with(['product', 'user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $campaigns = $query->orderBy('created_at', 'desc')->paginate(20);
        return response()->json(['success' => true, 'data' => $campaigns]);
    }

    /**
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
     *             @OA\Property(property="max_retries", type="integer", example=3),
     *             @OA\Property(property="send_time_from", type="string", example="09:00"),
     *             @OA\Property(property="send_time_to", type="string", example="20:00"),
     *             @OA\Property(property="send_days", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Кампания создана")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'product_id' => 'nullable|exists:products,id',
            'description' => 'nullable|string',
            'trigger_action' => 'nullable|string',
            'target_url' => 'nullable|string',
            'max_retries' => 'nullable|integer|min:0|max:10',
            'retry_interval_minutes' => 'nullable|integer|min:1',
            'send_time_from' => 'nullable|date_format:H:i',
            'send_time_to' => 'nullable|date_format:H:i',
            'send_days' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        $user = $request->user();
        $campaign = Campaign::create([
            ...$validated,
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Кампания создана',
            'data' => $campaign
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/campaigns/{id}",
     *     summary="Получить кампанию",
     *     tags={"Campaigns"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Данные кампании")
     * )
     */
    public function show(Request $request, Campaign $campaign): JsonResponse
    {
        $campaign->load(['product', 'user', 'mailings']);
        return response()->json(['success' => true, 'data' => $campaign]);
    }

    /**
     * @OA\Put(
     *     path="/campaigns/{id}",
     *     summary="Обновить кампанию",
     *     tags={"Campaigns"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent()),
     *     @OA\Response(response=200, description="Кампания обновлена")
     * )
     */
    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'trigger_action' => 'nullable|string',
            'target_url' => 'nullable|string',
            'max_retries' => 'nullable|integer|min:0|max:10',
            'send_time_from' => 'nullable|date_format:H:i',
            'send_time_to' => 'nullable|date_format:H:i',
            'send_days' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        $campaign->update($validated);
        return response()->json(['success' => true, 'message' => 'Кампания обновлена', 'data' => $campaign]);
    }

    /**
     * @OA\Delete(
     *     path="/campaigns/{id}",
     *     summary="Удалить кампанию",
     *     tags={"Campaigns"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Кампания удалена")
     * )
     */
    public function destroy(Campaign $campaign): JsonResponse
    {
        $campaign->delete();
        return response()->json(['success' => true, 'message' => 'Кампания удалена']);
    }

    /**
     * @OA\Post(
     *     path="/campaigns/{id}/launch",
     *     summary="Запустить кампанию",
     *     tags={"Campaigns"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Кампания запущена")
     * )
     */
    public function launch(Request $request, Campaign $campaign): JsonResponse
    {
        $campaign->update(['status' => 'active', 'started_at' => now()]);
        return response()->json(['success' => true, 'message' => 'Кампания запущена', 'data' => $campaign]);
    }

    /**
     * @OA\Post(
     *     path="/campaigns/{id}/pause",
     *     summary="Приостановить кампанию",
     *     tags={"Campaigns"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Кампания приостановлена")
     * )
     */
    public function pause(Request $request, Campaign $campaign): JsonResponse
    {
        $campaign->update(['status' => 'paused']);
        return response()->json(['success' => true, 'message' => 'Кампания приостановлена']);
    }
}
