<?php

// Valores vindos do .env. O codigo da aplicacao le SEMPRE via config(): chamadas
// diretas a env() retornam null quando `php artisan config:cache` esta ativo.
return [
    'db' => [
        'host' => env('APS_DB_HOST'),
        'port' => env('APS_DB_PORT'),
        'database' => env('APS_DB_DATABASE'),
        'username' => env('APS_DB_USERNAME'),
        'password' => env('APS_DB_PASSWORD'),
    ],
    'municipio_ibge' => env('MONITOR_APS_MUNICIPIO_IBGE'),
    'municipio_nome' => env('MONITOR_APS_MUNICIPIO_NOME'),
    'estrato_ied' => env('MONITOR_APS_ESTRATO_IED'),
];
