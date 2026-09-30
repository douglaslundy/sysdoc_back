<?php

return [
    // Máximo de linhas por página que o cliente pode pedir (?per_page=).
    'max_per_page' => (int) env('PAGINATION_MAX_PER_PAGE', 100),

    // Teto de linhas quando a listagem é chamada sem ?page= (formato antigo, lista simples).
    'legacy_cap' => (int) env('PAGINATION_LEGACY_CAP', 500),
];
