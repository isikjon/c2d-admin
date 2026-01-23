<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Dialog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/statistics/campaigns",
     *     summary="Статистика по кампаниям",
     *     tags={"Statistics"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Статистика кампаний")
     * )
     */
    public function campaigns(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = Campaign::where('organization_id', $user->organization_id)
            ->withCount([
                'dialogs',
                'dialogs as dialogs_with_response' => fn($q) => $q->whereIn('current_status', ['replied', 'ai_connected', 'communication']),
                'dialogs as dialogs_purchased' => fn($q) => $q->where('current_status', 'purchased'),
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'status' => $c->status,
                'total_dialogs' => $c->dialogs_count,
                'with_response' => $c->dialogs_with_response,
                'purchased' => $c->dialogs_purchased,
                'created_at' => $c->created_at,
            ]);

        return response()->json(['success' => true, 'data' => $stats]);
    }

    /**
     * @OA\Get(
     *     path="/statistics/dialogs",
     *     summary="Статистика по диалогам",
     *     tags={"Statistics"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Статистика диалогов по статусам")
     * )
     */
    public function dialogs(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = Dialog::whereHas('client', fn($q) => $q->where('organization_id', $user->organization_id))
            ->select('current_status', DB::raw('count(*) as count'))
            ->groupBy('current_status')
            ->get();

        return response()->json(['success' => true, 'data' => $stats]);
    }

    /**
     * @OA\Get(
     *     path="/statistics/export",
     *     summary="Экспорт статистики",
     *     tags={"Statistics"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Полная статистика")
     * )
     */
    public function export(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = [
            'campaigns' => Campaign::where('organization_id', $user->organization_id)->withCount('dialogs')->get(),
            'dialogs_by_status' => Dialog::whereHas('client', fn($q) => $q->where('organization_id', $user->organization_id))
                ->select('current_status', DB::raw('count(*) as count'))
                ->groupBy('current_status')
                ->get(),
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }
}
