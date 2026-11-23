<?php

return [
    // Segundos mínimos entre abrir o formulário e enviá-lo (humano leva mais; robô é imediato). 0 desliga.
    'tempo_minimo_segundos' => (int) env('OUVIDORIA_TEMPO_MINIMO', 3),
];
