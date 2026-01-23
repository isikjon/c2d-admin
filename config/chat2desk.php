<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Chat2Desk API Configuration
    |--------------------------------------------------------------------------
    */

    'api_url' => env('CHAT2DESK_API_URL', 'https://api.chat2desk.com/v1'),
    
    'api_token' => env('CHAT2DESK_API_TOKEN', ''),
    
    // ID канала по умолчанию (Telegram, WhatsApp и т.д.)
    'default_channel_id' => env('CHAT2DESK_CHANNEL_ID', null),
    
    // Транспорт по умолчанию: telegram, whatsapp, viber и т.д.
    'default_transport' => env('CHAT2DESK_TRANSPORT', 'telegram'),
    
    // Таймаут запросов в секундах
    'timeout' => env('CHAT2DESK_TIMEOUT', 30),
    
    // Лимит сообщений в секунду (15-20 по документации)
    'rate_limit' => env('CHAT2DESK_RATE_LIMIT', 15),
    
    // Webhook события для подписки
    'webhook_events' => [
        'inbox',           // входящее сообщение от клиента
        'outbox',          // исходящее сообщение
        'outbox_status',   // статус доставки
        'new_client',      // новый клиент
        'close_dialog',    // закрытие диалога
        'new_request',     // новый запрос
        'client_updated',  // обновление клиента
    ],
];
