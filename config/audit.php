<?php

return [
    // Campos que nao contam como "mudanca" (evita linha quando so o timestamp muda).
    'ignore_fields' => ['created_at', 'updated_at', 'remember_token'],

    // Models auditados pelo AuditableObserver generico (created/updated/deleted).
    // Models que ja tem observer proprio, ou auditoria explicita no controller/service,
    // NAO entram aqui: gerariam linha duplicada. Para auditar um model novo, basta listar.
    'models' => [
        \App\Models\ProtocolType::class,
        \App\Models\ProtocolOrganizationalUnit::class,
        \App\Models\ProtocolAlert::class,
        \App\Models\ProtocolConfig::class,
        \App\Models\DocumentType::class,
        // Passageiros de viagem: alimenta o historico do cidadao (tem client_id).
        \App\Models\TripClient::class,
        \App\Models\KanbanTask::class,
        \App\Models\SystemNotice::class,
        \App\Models\NotificationChannelConfig::class,
        \App\Models\PharmacyMedicinePanelSetting::class,
        \App\Models\AlmoxarifadoProduto::class,
        \App\Models\AlmoxarifadoCategoria::class,
        \App\Models\AlmoxarifadoEspecie::class,
        \App\Models\AlmoxarifadoFornecedor::class,
        \App\Models\AlmoxarifadoLocalizacao::class,
        \App\Models\AlmoxarifadoSecretaria::class,
        \App\Models\AlmoxarifadoUnidadeMedida::class,
        \App\Models\AlmoxarifadoConfig::class,
        \App\Models\AlmoxarifadoRequisicao::class,
    ],
];
