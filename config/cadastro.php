<?php

return [
    // Liga o pedido público de cadastro (rotas /public/cadastro*). Desligado = 404.
    'publico_habilitado' => (bool) env('CADASTRO_PUBLICO_HABILITADO', false),

    // Avisa os admins quando alguém tenta se cadastrar com CPF/e-mail de conta ativa (1 aviso/hora por conta).
    // Só é seguro com o envio fora do tempo da resposta (PHP-FPM); desligado por padrão.
    'alertar_tentativa_conta_ativa' => (bool) env('CADASTRO_ALERTAR_TENTATIVA_CONTA_ATIVA', false),
];
