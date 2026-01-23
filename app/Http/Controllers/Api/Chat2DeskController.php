<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Dialog;
use App\Models\Campaign;
use App\Services\Chat2DeskService;
use App\Jobs\SendChat2DeskMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class Chat2DeskController extends Controller
{
    protected Chat2DeskService $chat2desk;

    public function __construct(Chat2DeskService $chat2desk)
    {
        $this->chat2desk = $chat2desk;
    }

    /**
     * Проверить подключение к Chat2Desk
     */
    public function testConnection(): JsonResponse
    {
        try {
            $info = $this->chat2desk->getApiInfo();
            
            return response()->json([
                'success' => true,
                'message' => 'Подключение к Chat2Desk успешно',
                'data' => $info,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка подключения к Chat2Desk',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить список каналов Chat2Desk
     */
    public function getChannels(): JsonResponse
    {
        try {
            $channels = $this->chat2desk->getChannels();
            
            return response()->json([
                'success' => true,
                'data' => $channels,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить список webhooks
     */
    public function getWebhooks(): JsonResponse
    {
        try {
            $webhooks = $this->chat2desk->getWebhooks();
            
            return response()->json([
                'success' => true,
                'data' => $webhooks,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Создать/обновить webhook для нашего сервера
     */
    public function setupWebhook(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'string|max:255',
        ]);

        try {
            $webhookUrl = url('/api/webhooks/chat2desk');
            $name = $request->input('name', 'C2D Admin Webhook');
            
            $result = $this->chat2desk->createWebhook($webhookUrl, $name);
            
            return response()->json([
                'success' => true,
                'message' => 'Webhook создан',
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Отправить сообщение клиенту
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'message' => 'required|string|max:4096',
            'transport' => 'nullable|string|in:telegram,whatsapp,viber,vk',
        ]);

        try {
            $client = Client::findOrFail($request->client_id);
            
            // Отправляем через очередь
            SendChat2DeskMessage::dispatch(
                $client->id,
                $request->message,
                ['transport' => $request->transport]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Сообщение поставлено в очередь на отправку',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Отправить сообщение по телефону напрямую
     */
    public function sendMessageByPhone(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string|max:4096',
            'transport' => 'nullable|string|in:telegram,whatsapp,viber,vk',
        ]);

        try {
            $result = $this->chat2desk->sendMessageByPhone(
                $request->phone,
                $request->message,
                ['transport' => $request->transport]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Сообщение отправлено',
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook от Chat2Desk - обработка входящих событий
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $hookType = $payload['hook_type'] ?? null;

        Log::info('Chat2Desk webhook received', [
            'hook_type' => $hookType,
            'payload' => $payload,
        ]);

        try {
            switch ($hookType) {
                case 'inbox':
                    $this->handleInboxMessage($payload);
                    break;
                    
                case 'outbox':
                    $this->handleOutboxMessage($payload);
                    break;
                    
                case 'outbox_status':
                    $this->handleOutboxStatus($payload);
                    break;
                    
                case 'new_client':
                    $this->handleNewClient($payload);
                    break;
                    
                case 'close_dialog':
                    $this->handleCloseDialog($payload);
                    break;
                    
                case 'new_request':
                    $this->handleNewRequest($payload);
                    break;
                    
                default:
                    Log::info('Chat2Desk unhandled hook_type', ['hook_type' => $hookType]);
            }

            return response()->json(['status' => 'ok']);
            
        } catch (Exception $e) {
            Log::error('Chat2Desk webhook error', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            
            // Возвращаем 200, чтобы Chat2Desk не повторял запрос
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Обработка входящего сообщения от клиента
     */
    protected function handleInboxMessage(array $payload): void
    {
        $c2dClientId = $payload['client_id'] ?? null;
        $c2dDialogId = $payload['dialog_id'] ?? null;
        $messageText = $payload['text'] ?? '';
        $transport = $payload['transport'] ?? 'telegram';
        $clientData = $payload['client'] ?? [];

        if (!$c2dClientId) {
            return;
        }

        // Найдем или создадим клиента в нашей системе
        $phone = $clientData['phone'] ?? $clientData['client_phone'] ?? null;
        
        if ($phone) {
            $client = Client::where('phone', $phone)
                ->orWhere('chat2desk_id', $c2dClientId)
                ->first();

            if (!$client) {
                $client = Client::create([
                    'phone' => $phone,
                    'first_name' => $clientData['name'] ?? null,
                    'chat2desk_id' => $c2dClientId,
                    'organization_id' => 1, // Default org
                ]);
            } else {
                $client->update(['chat2desk_id' => $c2dClientId]);
            }

            // Обновим или создадим диалог
            if ($c2dDialogId && $client) {
                $dialog = Dialog::where('external_id', $c2dDialogId)->first();
                
                if (!$dialog) {
                    $dialog = Dialog::create([
                        'client_id' => $client->id,
                        'external_id' => $c2dDialogId,
                        'channel' => $transport,
                        'current_status' => 'in_progress',
                        'messages_count' => 1,
                        'last_message_at' => now(),
                    ]);
                } else {
                    $dialog->increment('messages_count');
                    $dialog->update(['last_message_at' => now()]);
                }
            }
        }

        Log::info('Chat2Desk inbox processed', [
            'c2d_client_id' => $c2dClientId,
            'text' => substr($messageText, 0, 100),
        ]);
    }

    /**
     * Обработка исходящего сообщения
     */
    protected function handleOutboxMessage(array $payload): void
    {
        $c2dDialogId = $payload['dialog_id'] ?? null;
        
        if ($c2dDialogId) {
            $dialog = Dialog::where('external_id', $c2dDialogId)->first();
            if ($dialog) {
                $dialog->increment('messages_count');
                $dialog->update(['last_message_at' => now()]);
            }
        }
    }

    /**
     * Обработка статуса доставки сообщения
     */
    protected function handleOutboxStatus(array $payload): void
    {
        $data = $payload['data'] ?? [];
        $status = $data['gateway_status'] ?? null;
        $messageId = $data['id'] ?? null;

        Log::info('Chat2Desk outbox status', [
            'message_id' => $messageId,
            'status' => $status,
        ]);

        // Можно обновить статус в mailing_messages если нужно
    }

    /**
     * Обработка нового клиента
     */
    protected function handleNewClient(array $payload): void
    {
        $c2dClientId = $payload['id'] ?? null;
        $phone = $payload['phone'] ?? $payload['client_phone'] ?? null;
        $name = $payload['name'] ?? null;

        if ($phone && $c2dClientId) {
            Client::updateOrCreate(
                ['phone' => $phone],
                [
                    'chat2desk_id' => $c2dClientId,
                    'first_name' => $name,
                    'organization_id' => 1,
                ]
            );
        }
    }

    /**
     * Обработка закрытия диалога
     */
    protected function handleCloseDialog(array $payload): void
    {
        $c2dDialogId = $payload['dialog_id'] ?? null;
        
        if ($c2dDialogId) {
            Dialog::where('external_id', $c2dDialogId)
                ->update([
                    'current_status' => 'closed',
                    'closed_at' => now(),
                ]);
        }
    }

    /**
     * Обработка нового запроса (request)
     */
    protected function handleNewRequest(array $payload): void
    {
        $c2dDialogId = $payload['dialog_id'] ?? null;
        $c2dClientId = $payload['client_id'] ?? null;
        
        Log::info('Chat2Desk new request', [
            'dialog_id' => $c2dDialogId,
            'client_id' => $c2dClientId,
        ]);
    }
}
