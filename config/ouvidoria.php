<?php

return [
    // Desligado por padrão: o portal público só abre quando o ambiente liga a variável.
    'publica_habilitada' => (bool) env('OUVIDORIA_PUBLICA_HABILITADA', false),
];
