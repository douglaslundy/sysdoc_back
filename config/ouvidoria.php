<?php

return [
    // Desligado por padrão: o portal público só abre quando o ambiente liga a variável.
    'publica_habilitada' => (bool) env('OUVIDORIA_PUBLICA_HABILITADA', false),

    // Segundos mínimos entre abrir o formulário e enviá-lo (humano leva mais; robô é imediato). 0 desliga.
    'tempo_minimo_segundos' => (int) env('OUVIDORIA_TEMPO_MINIMO', 3),
];
