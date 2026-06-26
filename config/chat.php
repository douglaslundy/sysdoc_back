<?php

return [
    'max_message_length' => (int) env('CHAT_MAX_MESSAGE_LENGTH', 4000),
    'max_attachment_kb' => (int) env('CHAT_MAX_ATTACHMENT_KB', 5120),
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
    'ca_bundle' => env('CHAT_CA_BUNDLE'),
    'http_proxy' => env('CHAT_HTTP_PROXY', ''),
];
