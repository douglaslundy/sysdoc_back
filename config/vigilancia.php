<?php

return [
    // Secretaria dona da Vigilância Sanitária: recebe as denúncias públicas (sem login) e responde à consulta
    // de protocolo. Nunca vem do cliente. Padrão: Saúde.
    'secretaria_slug' => env('VIGILANCIA_SECRETARIA_SLUG', 'saude'),
];
