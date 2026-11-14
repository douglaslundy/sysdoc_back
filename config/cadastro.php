<?php

return [
    // Liga o pedido público de cadastro (rotas /public/cadastro*). Desligado = 404.
    'publico_habilitado' => (bool) env('CADASTRO_PUBLICO_HABILITADO', true),

    // Avisa os admins quando alguém tenta se cadastrar com CPF/e-mail de conta ativa (1 aviso/hora por conta).
    // Só é seguro com o envio fora do tempo da resposta (PHP-FPM); desligado por padrão.
    'alertar_tentativa_conta_ativa' => (bool) env('CADASTRO_ALERTAR_TENTATIVA_CONTA_ATIVA', false),

    // Tempo mínimo (ms) do POST público: criar o pedido custa mais que recusar, e a diferença revelaria
    // se o CPF/e-mail já tem conta. Todos os ramos esperam até este piso antes de responder.
    'tempo_minimo_ms' => (int) env('CADASTRO_TEMPO_MINIMO_MS', 600),
];
