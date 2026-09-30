<?php

/*
|--------------------------------------------------------------------------
| Permissão de páginas por rota (autenticadas)
|--------------------------------------------------------------------------
| Cada regra diz QUAIS PÁGINAS do sistema (Perfis > páginas liberadas) podem chamar um
| endpoint. A primeira regra que casar com o URI vale; rotas sem regra seguem como antes
| (somente login) — por isso as que têm checagem própria no controller/FormRequest ficam de fora.
|
|  prefix   URI depois de "api/" (casa o próprio prefixo e tudo abaixo dele)
|  methods  (opcional) só estes métodos HTTP
|  except   (opcional) URIs (modelo da rota) que ficam FORA desta regra
|  pages    páginas que liberam; "/modulo*" libera qualquer página abaixo de /modulo
|  admin    true = somente administrador
|
| Admin sempre passa. Para liberar um endpoint a outra tela, acrescente a página aqui.
*/
return [
    'rules' => [
        // ---- módulos inteiros ----
        ['prefix' => 'laboratorio', 'pages' => ['/laboratorio*', '/auditoria']],
        ['prefix' => 'attendance', 'pages' => ['/attendance*']],
        ['prefix' => 'almoxarifado', 'pages' => ['/almoxarifado*']],
        ['prefix' => 'pharmacy', 'pages' => ['/pharmacy*', '/auditoria']],
        ['prefix' => 'medicines', 'pages' => ['/pharmacy*']],
        ['prefix' => 'letters', 'pages' => ['/letters']],
        ['prefix' => 'ordinances', 'pages' => ['/ordinance']],
        ['prefix' => 'models', 'pages' => ['/models']],
        ['prefix' => 'kanban', 'pages' => ['/kanban']],
        ['prefix' => 'qrcode-logs', 'pages' => ['/qrcodelogs']],
        ['prefix' => 'queue-treatment-plans', 'pages' => ['/queue*']],
        ['prefix' => 'queue-treatment-sessions', 'pages' => ['/queue*']],
        ['prefix' => 'conformidade-cidadao', 'pages' => ['/conformidade-cidadao']],

        // ---- avisos do sistema: todo usuário lê os ativos e registra a visualização ----
        ['prefix' => 'system-notices', 'except' => ['system-notices/active', 'system-notices/{id}/views'], 'pages' => ['/avisos']],

        // ---- protocolo: núcleo protegido; listas de apoio dos formulários seguem abertas ----
        [
            'prefix' => 'protocolos',
            'except' => [
                'protocolos/tipos',
                'protocolos/unidades-organizacionais',
                'protocolos/usuarios-elegiveis',
                'protocolos/contexto-novo',
            ],
            'pages' => ['/protocolo*', '/kanban'],
        ],

        // ---- vigilância sanitária (leituras; escritas já têm grupo próprio) ----
        ['prefix' => 'fiscalizacoes', 'methods' => ['GET'], 'pages' => ['/fiscalizacoes']],
        ['prefix' => 'estabelecimentos', 'methods' => ['GET'], 'pages' => ['/estabelecimentos', '/alvaras', '/fiscalizacoes', '/auditoria']],
        ['prefix' => 'cnaes', 'methods' => ['GET'], 'pages' => ['/estabelecimentos', '/alvaras', '/fiscalizacoes']],
        ['prefix' => 'alvaras', 'methods' => ['GET'], 'pages' => ['/alvaras', '/auditoria']],

        // ---- viagens (TFD) ----
        ['prefix' => 'trips', 'pages' => ['/trips', '/clients']],
        ['prefix' => 'trip-clients', 'pages' => ['/trips']],
        ['prefix' => 'confirm-trip-client', 'pages' => ['/trips']],
        ['prefix' => 'unconfirm-trip-client', 'pages' => ['/trips']],
        ['prefix' => 'vehicles', 'methods' => ['GET'], 'pages' => ['/vehicles', '/trips']],
        ['prefix' => 'vehicles', 'pages' => ['/vehicles']],
        ['prefix' => 'routes', 'methods' => ['GET'], 'pages' => ['/routes', '/trips']],
        ['prefix' => 'routes', 'pages' => ['/routes']],

        // ---- cadastros de apoio: a lista é usada por vários módulos; só a escrita é restrita ----
        ['prefix' => 'specialities', 'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'], 'pages' => ['/specialities']],

        // ---- legado (setores): somente administrador ----
        ['prefix' => 'sector', 'admin' => true],
        ['prefix' => 'sectors', 'admin' => true],
    ],
];
