<?php

return [
    // Liga o pedido público de cadastro (rotas /public/cadastro*). Desligado = 404.
    'publico_habilitado' => (bool) env('CADASTRO_PUBLICO_HABILITADO', false),
];
