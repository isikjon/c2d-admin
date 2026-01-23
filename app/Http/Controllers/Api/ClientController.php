<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * @OA\Get(
     *     path="/clients",
     *     summary="Список клиентов",
     *     tags={"Clients"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", description="Поиск", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Список клиентов")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Client::where('organization_id', $user->organization_id);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $clients = $query->orderBy('created_at', 'desc')->paginate(50);
        return response()->json(['success' => true, 'data' => $clients]);
    }

    /**
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
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'extra_data' => 'nullable|array',
        ]);

        $user = $request->user();
        $client = Client::create([
            ...$validated,
            'organization_id' => $user->organization_id,
        ]);

        return response()->json(['success' => true, 'data' => $client], 201);
    }

    /**
     * @OA\Get(
     *     path="/clients/{id}",
     *     summary="Получить клиента",
     *     tags={"Clients"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Данные клиента")
     * )
     */
    public function show(Client $client): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $client]);
    }

    /**
     * @OA\Put(
     *     path="/clients/{id}",
     *     summary="Обновить клиента",
     *     tags={"Clients"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Клиент обновлен")
     * )
     */
    public function update(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'extra_data' => 'nullable|array',
        ]);

        $client->update($validated);
        return response()->json(['success' => true, 'data' => $client]);
    }

    /**
     * @OA\Delete(
     *     path="/clients/{id}",
     *     summary="Удалить клиента",
     *     tags={"Clients"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Клиент удален")
     * )
     */
    public function destroy(Client $client): JsonResponse
    {
        $client->delete();
        return response()->json(['success' => true, 'message' => 'Клиент удалён']);
    }

    /**
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
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'clients' => 'required|array',
            'clients.*.phone' => 'required|string',
        ]);

        $user = $request->user();
        $imported = 0;
        $errors = [];

        foreach ($request->clients as $index => $clientData) {
            try {
                Client::updateOrCreate(
                    ['organization_id' => $user->organization_id, 'phone' => $clientData['phone']],
                    [
                        'first_name' => $clientData['first_name'] ?? null,
                        'last_name' => $clientData['last_name'] ?? null,
                        'patronymic' => $clientData['patronymic'] ?? null,
                        'email' => $clientData['email'] ?? null,
                        'discount' => $clientData['discount'] ?? null,
                        'extra_data' => $clientData['extra_data'] ?? null,
                    ]
                );
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Строка {$index}: {$e->getMessage()}";
            }
        }

        return response()->json(['success' => true, 'message' => "Импортировано: {$imported}", 'errors' => $errors]);
    }

    /**
     * @OA\Get(
     *     path="/clients/export",
     *     summary="Экспорт клиентов",
     *     tags={"Clients"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Список клиентов для экспорта")
     * )
     */
    public function export(Request $request): JsonResponse
    {
        $user = $request->user();
        $clients = Client::where('organization_id', $user->organization_id)->get();
        return response()->json(['success' => true, 'data' => $clients]);
    }
}
