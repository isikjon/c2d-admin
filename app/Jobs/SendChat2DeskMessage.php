<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\Dialog;
use App\Models\MailingMessage;
use App\Services\Chat2DeskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class SendChat2DeskMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    protected int $clientId;
    protected string $message;
    protected array $options;
    protected ?int $mailingMessageId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $clientId,
        string $message,
        array $options = [],
        ?int $mailingMessageId = null
    ) {
        $this->clientId = $clientId;
        $this->message = $message;
        $this->options = $options;
        $this->mailingMessageId = $mailingMessageId;
    }

    /**
     * Execute the job.
     */
    public function handle(Chat2DeskService $chat2desk): void
    {
        try {
            $client = Client::findOrFail($this->clientId);
            
            // Получаем или создаем клиента в Chat2Desk
            $c2dClient = $chat2desk->getOrCreateClient(
                $client->phone,
                $this->options['transport'] ?? null
            );

            // Отправляем сообщение
            $result = $chat2desk->sendMessage(
                $c2dClient['id'],
                $this->message,
                $this->options
            );

            // Обновляем статус сообщения рассылки
            if ($this->mailingMessageId) {
                MailingMessage::where('id', $this->mailingMessageId)
                    ->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'external_id' => $result['data']['id'] ?? null,
                    ]);
            }

            Log::info('Chat2Desk message sent', [
                'client_id' => $this->clientId,
                'c2d_client_id' => $c2dClient['id'],
                'message_id' => $result['data']['id'] ?? null,
            ]);

        } catch (Exception $e) {
            Log::error('Chat2Desk message failed', [
                'client_id' => $this->clientId,
                'error' => $e->getMessage(),
            ]);

            // Обновляем статус на failed
            if ($this->mailingMessageId) {
                MailingMessage::where('id', $this->mailingMessageId)
                    ->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Chat2Desk message job failed permanently', [
            'client_id' => $this->clientId,
            'error' => $exception->getMessage(),
        ]);

        if ($this->mailingMessageId) {
            MailingMessage::where('id', $this->mailingMessageId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $exception->getMessage(),
                ]);
        }
    }
}
