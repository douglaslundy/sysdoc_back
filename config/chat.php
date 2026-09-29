<?php

return [
    'max_message_length' => (int) env('CHAT_MAX_MESSAGE_LENGTH', 4000),
    'max_attachment_kb' => (int) env('CHAT_MAX_ATTACHMENT_KB', 30720),
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'txt', 'pdf'],
    'allowed_mimes' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'text/plain',
        'application/pdf',
    ],
    'pusher_daily_message_limit' => (int) env('PUSHER_DAILY_MESSAGE_LIMIT', 200000),
    'pusher_connection_limit' => (int) env('PUSHER_CONNECTION_LIMIT', 100),
    // null = automatico (adia o envio ao tempo real para depois da resposta HTTP na web).
    'defer_broadcast' => env('CHAT_DEFER_BROADCAST'),
    // Credenciais de fallback do servico de tempo real (usadas quando nao ha config salva no banco).
    'pusher' => [
        'app_id' => env('PUSHER_APP_ID'),
        'app_key' => env('PUSHER_APP_KEY'),
        'app_secret' => env('PUSHER_APP_SECRET'),
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'host' => env('PUSHER_HOST'),
        'port' => env('PUSHER_PORT'),
        'scheme' => env('PUSHER_SCHEME'),
    ],
    'ca_bundle' => env('CHAT_CA_BUNDLE'),
    'http_proxy' => env('CHAT_HTTP_PROXY', ''),
];
