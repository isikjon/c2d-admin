<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Exception;

class Chat2DeskService
{
    protected string $apiUrl;
    protected string $apiToken;
    protected int $timeout;

    public function __construct()
    {
        $this->apiUrl = config('chat2desk.api_url');
        $this->apiToken = config('chat2desk.api_token');
        $this->timeout = config('chat2desk.timeout', 30);
    }

    /**
     * Базовый HTTP клиент с авторизацией
     */
    protected function client()
    {
        return Http::withHeaders([
            'Authorization' => $this->apiToken,
            'Content-Type' => 'application/json',
        ])->timeout($this->timeout);
    }

    /**
     * GET запрос к API
     */
    protected function get(string $endpoint, array $params = []): array
    {
        try {
            $response = $this->client()->get("{$this->apiUrl}/{$endpoint}", $params);
            return $this->handleResponse($response, $endpoint);
        } catch (Exception $e) {
            Log::error("Chat2Desk GET {$endpoint} error", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * POST запрос к API
     */
    protected function post(string $endpoint, array $data = [], array $params = []): array
    {
        try {
            $url = "{$this->apiUrl}/{$endpoint}";
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            
            $response = $this->client()->post($url, $data);
            return $this->handleResponse($response, $endpoint);
        } catch (Exception $e) {
            Log::error("Chat2Desk POST {$endpoint} error", ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        }
    }

    /**
     * PUT запрос к API
     */
    protected function put(string $endpoint, array $data = []): array
    {
        try {
            $response = $this->client()->put("{$this->apiUrl}/{$endpoint}", $data);
            return $this->handleResponse($response, $endpoint);
        } catch (Exception $e) {
            Log::error("Chat2Desk PUT {$endpoint} error", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Обработка ответа API
     */
    protected function handleResponse(Response $response, string $endpoint): array
    {
        $data = $response->json();

        if ($response->failed()) {
            Log::error("Chat2Desk API error", [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $data,
            ]);
            
            throw new Exception(
                $data['error'] ?? "Chat2Desk API error: {$response->status()}",
                $response->status()
            );
        }

        return $data ?? [];
    }

    // ==================== КЛИЕНТЫ ====================

    /**
     * Получить список клиентов
     */
    public function getClients(array $filters = []): array
    {
        return $this->get('clients', $filters);
    }

    /**
     * Получить клиента по ID
     */
    public function getClient(int $clientId): array
    {
        return $this->get("clients/{$clientId}");
    }

    /**
     * Найти клиента по телефону
     */
    public function findClientByPhone(string $phone): ?array
    {
        $result = $this->get('clients', ['phone' => $phone]);
        return $result['data'][0] ?? null;
    }

    /**
     * Создать нового клиента
     */
    public function createClient(string $phone, string $transport = null, int $channelId = null): array
    {
        return $this->post('clients', [
            'phone' => $phone,
            'transport' => $transport ?? config('chat2desk.default_transport'),
            'channel_id' => $channelId ?? config('chat2desk.default_channel_id'),
        ]);
    }

    /**
     * Обновить клиента
     */
    public function updateClient(int $clientId, array $data): array
    {
        return $this->put("clients/{$clientId}", $data);
    }

    /**
     * Получить или создать клиента по телефону
     */
    public function getOrCreateClient(string $phone, string $transport = null): array
    {
        $client = $this->findClientByPhone($phone);
        
        if ($client) {
            return $client;
        }

        $result = $this->createClient($phone, $transport);
        return $result['data'] ?? $result;
    }

    // ==================== СООБЩЕНИЯ ====================

    /**
     * Отправить сообщение клиенту по client_id
     */
    public function sendMessage(int $clientId, string $text, array $options = []): array
    {
        $params = [
            'client_id' => $clientId,
            'text' => $text,
        ];

        if (isset($options['transport'])) {
            $params['transport'] = $options['transport'];
        }

        $body = [];
        
        // Inline кнопки
        if (isset($options['inline_buttons'])) {
            $body['inline_buttons'] = $options['inline_buttons'];
        }
        
        // Клавиатура
        if (isset($options['keyboard'])) {
            $body['keyboard'] = $options['keyboard'];
        }

        return $this->post('messages', $body, $params);
    }

    /**
     * Отправить сообщение по телефону (создаст клиента если не существует)
     */
    public function sendMessageByPhone(string $phone, string $text, array $options = []): array
    {
        $client = $this->getOrCreateClient($phone, $options['transport'] ?? null);
        $clientId = $client['id'];
        
        return $this->sendMessage($clientId, $text, $options);
    }

    /**
     * Получить сообщения
     */
    public function getMessages(array $filters = []): array
    {
        return $this->get('messages', $filters);
    }

    /**
     * Получить сообщение по ID
     */
    public function getMessage(int $messageId): array
    {
        return $this->get("messages/{$messageId}");
    }

    // ==================== ДИАЛОГИ ====================

    /**
     * Получить список диалогов Chat2Desk
     */
    public function getDialogs(array $filters = []): array
    {
        return $this->get('dialogs', $filters);
    }

    /**
     * Получить диалог по ID
     */
    public function getDialog(int $dialogId): array
    {
        return $this->get("dialogs/{$dialogId}");
    }

    /**
     * Закрыть диалог
     */
    public function closeDialog(int $dialogId): array
    {
        return $this->put("dialogs/{$dialogId}", ['state' => 'closed']);
    }

    // ==================== WEBHOOKS ====================

    /**
     * Получить список webhooks
     */
    public function getWebhooks(): array
    {
        return $this->get('webhooks');
    }

    /**
     * Создать webhook
     */
    public function createWebhook(string $url, string $name, array $events = null): array
    {
        return $this->post('webhooks', [
            'url' => $url,
            'name' => $name,
            'events' => $events ?? config('chat2desk.webhook_events'),
        ]);
    }

    /**
     * Обновить webhook
     */
    public function updateWebhook(int $webhookId, array $data): array
    {
        return $this->put("webhooks/{$webhookId}", $data);
    }

    /**
     * Удалить webhook (url = null)
     */
    public function deleteWebhook(int $webhookId): array
    {
        return $this->put("webhooks/{$webhookId}", ['url' => null]);
    }

    // ==================== КАНАЛЫ ====================

    /**
     * Получить список каналов
     */
    public function getChannels(): array
    {
        return $this->get('channels');
    }

    /**
     * Получить доступные транспорты для клиента
     */
    public function getClientTransports(int $clientId): array
    {
        return $this->get("clients/{$clientId}/transport");
    }

    // ==================== ИНФОРМАЦИЯ ====================

    /**
     * Получить информацию об API
     */
    public function getApiInfo(): array
    {
        return $this->get('companies/api_info');
    }

    /**
     * Проверить подключение к API
     */
    public function testConnection(): bool
    {
        try {
            $this->getApiInfo();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
