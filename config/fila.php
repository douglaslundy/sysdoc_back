<?php

return [
    // Secretaria dona da fila pública (painel /showPublicQueue): a lista aberta, sem login, mostra só os itens dela.
    // O QR code (/showqueue/{uuid}) e o log de localização seguem a secretaria do próprio registro. Nunca vem do cliente.
    'secretaria_slug' => env('FILA_SECRETARIA_SLUG', 'saude'),
];
