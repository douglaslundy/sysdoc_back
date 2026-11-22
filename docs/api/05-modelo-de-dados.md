# Modelo de dados

Estrutura do banco MySQL do Sysdoc, gerada do schema real (apenas estrutura, sem dados). Serve para entender **de onde vêm** as informações que a API entrega. A API é a única interface suportada para consumir os dados: não acesse o banco diretamente em integrações externas.

- Chave primária: `PK`. Chave estrangeira: `→ tabela.coluna`.
- Exclusão lógica: a maioria das tabelas usa a coluna booleana `active`/`ativo`; algumas usam `deleted_at` (SoftDeletes) — ver a coluna na tabela.
- Dados do e-SUS PEC **não** ficam neste banco: o Monitor APS e o painel e-SUS leem o PostgreSQL do e-SUS em conexão separada, somente leitura.

## Índice por módulo

- **Acesso, secretarias e usuários:** [`users`](#users), [`access_profiles`](#access_profiles), [`profile_page_permissions`](#profile_page_permissions), [`system_pages`](#system_pages), [`page_categories`](#page_categories), [`secretarias`](#secretarias), [`secretaria_pages`](#secretaria_pages), [`user_secretarias`](#user_secretarias), [`user_speciality_permissions`](#user_speciality_permissions), [`user_access_profiles`](#user_access_profiles), [`profile_speciality_permissions`](#profile_speciality_permissions), [`user_equipe_aps`](#user_equipe_aps), [`cadastro_solicitacoes`](#cadastro_solicitacoes), [`sectors`](#sectors), [`specialities`](#specialities), [`states`](#states), [`user_presences`](#user_presences)
- **Auditoria e administração:** [`audit_logs`](#audit_logs), [`logs`](#logs), [`error_logs`](#error_logs), [`banned_ips`](#banned_ips), [`message_logs`](#message_logs), [`notification_channel_configs`](#notification_channel_configs), [`system_settings`](#system_settings), [`system_notices`](#system_notices), [`system_notice_views`](#system_notice_views), [`qrcode_logs`](#qrcode_logs), [`public_queue_logs`](#public_queue_logs), [`painel_publico_config`](#painel_publico_config), [`dashboard_atalhos`](#dashboard_atalhos), [`user_atalhos`](#user_atalhos), [`monitor_aps_configs`](#monitor_aps_configs)
- **Cidadãos:** [`clients`](#clients), [`addresses`](#addresses), [`pessoa_fotos`](#pessoa_fotos), [`sincronizacoes_cidadao`](#sincronizacoes_cidadao), [`sincronizacao_itens`](#sincronizacao_itens), [`painel_esus_presences`](#painel_esus_presences)
- **Atendimento e fila:** [`attendance_tickets`](#attendance_tickets), [`attendance_rooms`](#attendance_rooms), [`attendance_calls`](#attendance_calls), [`attendance_records`](#attendance_records), [`queue`](#queue), [`queue_attachments`](#queue_attachments), [`queue_treatment_plans`](#queue_treatment_plans), [`queue_treatment_sessions`](#queue_treatment_sessions)
- **Laboratório:** [`categoria_exames`](#categoria_exames), [`exames`](#exames), [`exame_campos`](#exame_campos), [`campo_referencias`](#campo_referencias), [`pedidos_exame`](#pedidos_exame), [`pedido_exame_itens`](#pedido_exame_itens), [`resultado_exames`](#resultado_exames), [`resultado_campos`](#resultado_campos), [`medicos_solicitantes`](#medicos_solicitantes), [`lab_configs`](#lab_configs)
- **TFD e viagens:** [`trips`](#trips), [`trip_clients`](#trip_clients), [`trip_schedule_entries`](#trip_schedule_entries), [`vehicles`](#vehicles), [`routes`](#routes)
- **Farmácia:** [`medicine_items`](#medicine_items), [`medicine_daily_statuses`](#medicine_daily_statuses), [`medicine_monthly_acquisitions`](#medicine_monthly_acquisitions), [`medicine_publications`](#medicine_publications), [`pharmacy_acquisition_sources`](#pharmacy_acquisition_sources), [`pharmacy_medicine_panel_settings`](#pharmacy_medicine_panel_settings), [`pharmacy_pharmaceutical_forms`](#pharmacy_pharmaceutical_forms), [`pharmacy_presentations`](#pharmacy_presentations), [`pharmacy_units`](#pharmacy_units)
- **Almoxarifado:** [`almoxarifado_arquivos`](#almoxarifado_arquivos), [`almoxarifado_categorias`](#almoxarifado_categorias), [`almoxarifado_configs`](#almoxarifado_configs), [`almoxarifado_especies`](#almoxarifado_especies), [`almoxarifado_estoques`](#almoxarifado_estoques), [`almoxarifado_fornecedores`](#almoxarifado_fornecedores), [`almoxarifado_localizacoes`](#almoxarifado_localizacoes), [`almoxarifado_movimentacoes`](#almoxarifado_movimentacoes), [`almoxarifado_produtos`](#almoxarifado_produtos), [`almoxarifado_requisicao_itens`](#almoxarifado_requisicao_itens), [`almoxarifado_requisicao_status_historicos`](#almoxarifado_requisicao_status_historicos), [`almoxarifado_requisicoes`](#almoxarifado_requisicoes), [`almoxarifado_secretarias`](#almoxarifado_secretarias), [`almoxarifado_unidades_medida`](#almoxarifado_unidades_medida)
- **Protocolo, documentos e kanban:** [`protocols`](#protocols), [`protocol_types`](#protocol_types), [`protocol_movements`](#protocol_movements), [`protocol_comments`](#protocol_comments), [`protocol_attachments`](#protocol_attachments), [`protocol_alerts`](#protocol_alerts), [`protocol_configs`](#protocol_configs), [`protocol_notifications`](#protocol_notifications), [`protocol_organizational_units`](#protocol_organizational_units), [`protocol_user_units`](#protocol_user_units), [`protocol_views`](#protocol_views), [`documents`](#documents), [`document_versions`](#document_versions), [`document_approvals`](#document_approvals), [`document_configs`](#document_configs), [`document_types`](#document_types), [`letters`](#letters), [`letter_attachments`](#letter_attachments), [`ordinances`](#ordinances), [`ordinance_attachments`](#ordinance_attachments), [`models`](#models), [`kanban_tasks`](#kanban_tasks)
- **Vigilância sanitária:** [`estabelecimentos`](#estabelecimentos), [`estabelecimento_cnaes`](#estabelecimento_cnaes), [`cnaes`](#cnaes), [`alvaras`](#alvaras), [`fiscalizacoes`](#fiscalizacoes), [`fiscalizacao_movimentacoes`](#fiscalizacao_movimentacoes), [`fiscalizacao_attachments`](#fiscalizacao_attachments), [`peticao_motivos`](#peticao_motivos), [`vigilancia_configs`](#vigilancia_configs), [`vigilancia_contatos_whatsapp`](#vigilancia_contatos_whatsapp)
- **Iluminação e ordens de serviço:** [`iluminacao_postes`](#iluminacao_postes), [`iluminacao_poste_fotos`](#iluminacao_poste_fotos), [`iluminacao_poste_movimentacoes`](#iluminacao_poste_movimentacoes), [`ordens_servico`](#ordens_servico), [`os_fotos`](#os_fotos), [`os_historico`](#os_historico), [`os_tipos_servico`](#os_tipos_servico), [`os_configuracoes`](#os_configuracoes)
- **Ouvidoria:** [`ouvidoria_manifestacoes`](#ouvidoria_manifestacoes), [`ouvidoria_historico`](#ouvidoria_historico), [`ouvidoria_anexos`](#ouvidoria_anexos), [`ouvidoria_acessos_leitura`](#ouvidoria_acessos_leitura)
- **Chat:** [`chat_conversations`](#chat_conversations), [`chat_conversation_participants`](#chat_conversation_participants), [`chat_messages`](#chat_messages), [`chat_attachments`](#chat_attachments), [`chat_connections`](#chat_connections), [`chat_usage_daily`](#chat_usage_daily), [`chat_realtime_configs`](#chat_realtime_configs)

## Acesso, secretarias e usuários

### users

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `profile` | varchar(50) | sim |  |
| `chat_access_override` | tinyint(1) | sim |  |
| `name` | varchar(255) | não |  |
| `preferred_name` | varchar(50) | sim |  |
| `phone` | varchar(20) | sim |  |
| `email` | varchar(255) | não | único |
| `cpf` | varchar(255) | não | único |
| `is_driver` | tinyint(1) | não |  |
| `is_rt_psf` | tinyint(1) | não |  |
| `rt_all_teams` | tinyint(1) | não |  |
| `active` | tinyint(1) | não |  |
| `inactive_date` | timestamp | sim |  |
| `password` | varchar(255) | não |  |
| `protocol_allowed_types` | json | sim |  |
| `protocol_allowed_statuses` | json | sim |  |

### access_profiles

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(60) | não | único |
| `slug` | varchar(60) | não | único |
| `descricao` | varchar(200) | sim |  |
| `ativo` | tinyint(1) | não |  |
| `chat_enabled` | tinyint(1) | não |  |
| `almoxarifado_create_enabled` | tinyint(1) | não |  |
| `almoxarifado_approve_enabled` | tinyint(1) | não |  |
| `almoxarifado_deliver_enabled` | tinyint(1) | não |  |
| `client_trips_view_enabled` | tinyint(1) | não |  |
| `client_report_view_enabled` | tinyint(1) | não |  |
| `client_history_view_enabled` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `created_by_id` | bigint unsigned | sim | → users.id |
| `protocol_allowed_types` | json | sim |  |
| `protocol_allowed_statuses` | json | sim |  |
| `protocol_allowed_visibilities` | json | sim |  |
| `protocol_can_change_visibility` | tinyint(1) | não |  |
| `os_encaminhar_enabled` | tinyint(1) | não |  |

### profile_page_permissions

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `access_profile_id` | bigint unsigned | não | → access_profiles.id |
| `system_page_id` | bigint unsigned | não | → system_pages.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### system_pages

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `titulo` | varchar(80) | não |  |
| `path` | varchar(120) | não | único |
| `icone` | varchar(40) | sim |  |
| `category_id` | bigint unsigned | sim | → page_categories.id |
| `ordem` | int | não |  |
| `categoria` | varchar(60) | sim |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### page_categories

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(60) | não | único |
| `icone` | varchar(40) | sim |  |
| `ordem` | int | não |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### secretarias

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(120) | não | único |
| `slug` | varchar(60) | não | único |
| `ativa` | tinyint(1) | não |  |
| `ouvidoria_publica` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### secretaria_pages

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `secretaria_id` | bigint unsigned | não | PK, → secretarias.id |
| `system_page_id` | bigint unsigned | não | PK, → system_pages.id |

### user_secretarias

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `user_id` | bigint unsigned | não | PK, → users.id |
| `secretaria_id` | bigint unsigned | não | PK, → secretarias.id |

### user_speciality_permissions

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `user_id` | bigint unsigned | não | → users.id |
| `speciality_id` | bigint unsigned | não | → specialities.id |
| `can_view` | tinyint(1) | não |  |
| `can_edit` | tinyint(1) | não |  |
| `can_insert` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### user_access_profiles

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `user_id` | bigint unsigned | não | → users.id |
| `access_profile_id` | bigint unsigned | não | → access_profiles.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### profile_speciality_permissions

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `access_profile_id` | bigint unsigned | não | → access_profiles.id |
| `speciality_id` | bigint unsigned | não | → specialities.id |
| `can_view` | tinyint(1) | não |  |
| `can_edit` | tinyint(1) | não |  |
| `can_insert` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### user_equipe_aps

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `user_id` | bigint unsigned | não | → users.id |
| `nu_ine` | varchar(10) | não |  |
| `no_equipe` | varchar(100) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### cadastro_solicitacoes

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(50) | não |  |
| `preferred_name` | varchar(50) | sim |  |
| `email` | varchar(100) | não |  |
| `cpf` | varchar(18) | não |  |
| `phone` | varchar(30) | sim |  |
| `password_hash` | varchar(255) | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |
| `cargo` | varchar(80) | não |  |
| `observacao` | varchar(200) | sim |  |
| `status` | enum('pendente','aprovada','recusada') | não |  |
| `protocol_id` | bigint unsigned | sim |  |
| `user_id` | bigint unsigned | sim |  |
| `motivo_recusa` | text | sim |  |
| `decidido_por_id` | bigint unsigned | sim |  |
| `decidido_em` | timestamp | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### sectors

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `name` | varchar(255) | não |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### specialities

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `id_user` | bigint unsigned | não | → users.id |
| `name` | varchar(50) | não |  |
| `allows_session_scheduling` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### states

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `code` | char(2) | não | único |
| `name` | varchar(50) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### user_presences

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `user_id` | bigint unsigned | não | → users.id |
| `last_seen_at` | timestamp | sim |  |
| `last_path` | varchar(255) | sim |  |
| `status` | varchar(20) | não |  |
| `connection_count` | int unsigned | não |  |
| `connected_at` | timestamp | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

## Auditoria e administração

### audit_logs

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `user_id` | bigint unsigned | sim |  |
| `user_name` | varchar(100) | não |  |
| `action` | varchar(30) | não |  |
| `model_type` | varchar(60) | sim |  |
| `model_id` | bigint unsigned | sim |  |
| `client_id` | bigint unsigned | sim |  |
| `endpoint` | varchar(200) | não |  |
| `method` | varchar(10) | não |  |
| `ip_address` | varchar(45) | não |  |
| `user_agent` | varchar(255) | sim |  |
| `old_values` | json | sim |  |
| `new_values` | json | sim |  |
| `created_at` | timestamp | não |  |
| `ip_origin` | varchar(255) | sim |  |
| `client_info` | json | sim |  |

### logs

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `user_id` | bigint unsigned | sim | → users.id |
| `action` | varchar(255) | não |  |
| `description` | text | sim |  |
| `ip_address` | varchar(255) | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### error_logs

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `user_id` | bigint unsigned | sim | → users.id |
| `type` | varchar(255) | sim |  |
| `file` | varchar(255) | sim |  |
| `line` | int | sim |  |
| `message` | text | não |  |
| `trace` | text | sim |  |
| `context` | json | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `ip_origin` | varchar(255) | sim |  |
| `client_info` | json | sim |  |

### banned_ips

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `ip` | varchar(45) | não | único |
| `origin` | varchar(255) | sim |  |
| `reason` | varchar(255) | sim |  |
| `source` | varchar(20) | não |  |
| `hits` | int unsigned | não |  |
| `expires_at` | timestamp | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `client_info` | json | sim |  |

### message_logs

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `canal` | varchar(20) | não |  |
| `user_id` | bigint unsigned | sim |  |
| `destino` | varchar(190) | não |  |
| `assunto` | varchar(255) | sim |  |
| `mensagem` | text | não |  |
| `status` | varchar(20) | não |  |
| `erro` | text | sim |  |
| `origem` | varchar(120) | sim |  |
| `protocol_id` | bigint unsigned | sim |  |
| `enviada_em` | timestamp | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### notification_channel_configs

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `canal` | varchar(40) | não | único |
| `ativo` | tinyint(1) | não |  |
| `configuracao` | json | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### system_settings

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `key` | varchar(100) | não | PK |
| `value` | varchar(255) | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### system_notices

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `title` | varchar(255) | não |  |
| `subtitle` | varchar(255) | sim |  |
| `body` | text | não |  |
| `image_data` | longtext | sim |  |
| `times_per_day` | smallint unsigned | não |  |
| `interval_minutes` | smallint unsigned | não |  |
| `target_user_id` | bigint unsigned | sim | → users.id |
| `valid_until` | date | sim |  |
| `is_active` | tinyint(1) | não |  |
| `created_by_user_id` | bigint unsigned | sim | → users.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### system_notice_views

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `system_notice_id` | bigint unsigned | não | → system_notices.id |
| `user_id` | bigint unsigned | não | → users.id |
| `shown_at` | timestamp | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### qrcode_logs

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `uuid` | char(36) | não |  |
| `queue_id` | bigint unsigned | sim |  |
| `position` | int | não |  |
| `ip_address` | varchar(255) | sim |  |
| `host_name` | varchar(255) | sim |  |
| `user_agent` | varchar(255) | sim |  |
| `location` | text | sim |  |
| `referer` | varchar(255) | sim |  |
| `accessed_at` | timestamp | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `ip_origin` | varchar(255) | sim |  |
| `client_info` | json | sim |  |

### public_queue_logs

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `ip_address` | varchar(45) | não |  |
| `user_agent` | varchar(255) | sim |  |
| `host_name` | varchar(255) | sim |  |
| `referer` | varchar(255) | sim |  |
| `accessed_at` | timestamp | não |  |
| `ip_origin` | varchar(255) | sim |  |
| `client_info` | json | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### painel_publico_config

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `pagina_url` | varchar(500) | não |  |
| `esquerda_url` | varchar(500) | não |  |
| `direita_url` | varchar(500) | não |  |
| `largura_esquerda` | tinyint unsigned | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### dashboard_atalhos

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `titulo` | varchar(60) | não |  |
| `url` | varchar(500) | não |  |
| `icone` | varchar(40) | não |  |
| `ordem` | int unsigned | não |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### user_atalhos

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `user_id` | bigint unsigned | não | → users.id |
| `titulo` | varchar(80) | não |  |
| `url` | varchar(500) | não |  |
| `icone` | varchar(40) | não |  |
| `ordem` | tinyint unsigned | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### monitor_aps_configs

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `aps_db_host` | varchar(255) | não |  |
| `aps_db_port` | smallint unsigned | não |  |
| `aps_db_database` | varchar(255) | não |  |
| `aps_db_username` | varchar(255) | não |  |
| `aps_db_password` | varchar(500) | não |  |
| `municipio_ibge` | varchar(10) | não |  |
| `municipio_nome` | varchar(255) | não |  |
| `estrato_ied` | tinyint unsigned | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

## Cidadãos

### clients

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `name` | varchar(100) | não |  |
| `mother` | varchar(100) | sim |  |
| `father` | varchar(100) | sim |  |
| `cns` | varchar(15) | sim |  |
| `cpf` | varchar(18) | sim |  |
| `phone` | varchar(20) | sim |  |
| `email` | varchar(100) | sim |  |
| `obs` | varchar(200) | sim |  |
| `born_date` | date | não |  |
| `sexo` | enum('MASCULINE','FEMININE','INDETERMINATE') | não |  |
| `raca_cor` | varchar(30) | sim |  |
| `escolaridade` | varchar(120) | sim |  |
| `nacionalidade` | varchar(120) | sim |  |
| `data_obito` | date | sim |  |
| `st_falecido` | tinyint(1) | não |  |
| `active` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### addresses

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `city` | varchar(50) | não |  |
| `street` | varchar(100) | não |  |
| `number` | varchar(6) | não |  |
| `complement` | varchar(50) | sim |  |
| `zip_code` | varchar(10) | sim |  |
| `district` | varchar(100) | não |  |
| `id_client` | int | não |  |
| `active` | tinyint(1) | não |  |

### pessoa_fotos

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `chave` | varchar(40) | não | único |
| `disk` | varchar(30) | não |  |
| `path` | varchar(255) | não |  |
| `mime_type` | varchar(100) | não |  |
| `size_bytes` | bigint unsigned | não |  |
| `updated_by` | bigint unsigned | sim | → users.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### sincronizacoes_cidadao

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `job_id` | varchar(36) | não | único |
| `status` | enum('pending','analyzing','preview_ready','applying','completed','failed') | não |  |
| `total_esus` | int unsigned | não |  |
| `total_sysdoc` | int unsigned | não |  |
| `total_esus_previsto` | int unsigned | não |  |
| `preview_criados` | int unsigned | não |  |
| `preview_atualizados` | int unsigned | não |  |
| `preview_obitos` | int unsigned | não |  |
| `preview_sem_alteracao` | int unsigned | não |  |
| `result_criados` | int unsigned | sim |  |
| `result_atualizados` | int unsigned | sim |  |
| `result_obitos` | int unsigned | sim |  |
| `result_erros` | int unsigned | sim |  |
| `iniciado_por` | bigint unsigned | não | → users.id |
| `aplicado_por` | bigint unsigned | sim | → users.id |
| `analisado_em` | timestamp | sim |  |
| `aplicado_em` | timestamp | sim |  |
| `erro_mensagem` | text | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### sincronizacao_itens

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `sincronizacao_id` | bigint unsigned | não | → sincronizacoes_cidadao.id |
| `acao` | enum('criar','atualizar','obito') | não |  |
| `cpf` | varchar(18) | sim |  |
| `cns` | varchar(15) | sim |  |
| `nome_esus` | varchar(150) | não |  |
| `client_id` | bigint unsigned | sim |  |
| `payload` | json | não |  |
| `aplicado` | tinyint(1) | não |  |
| `erro` | varchar(255) | sim |  |

### painel_esus_presences

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `cnes` | varchar(20) | não |  |
| `panel_name` | varchar(255) | sim |  |
| `last_seen_at` | timestamp | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

## Atendimento e fila

### attendance_tickets

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `number` | int unsigned | não |  |
| `display_code` | varchar(20) | não |  |
| `sequence_date` | date | não |  |
| `client_id` | bigint unsigned | não | → clients.id |
| `status` | enum('aguardando','chamada','em_atendimento','finalizada','cancelada','nao_compareceu') | não |  |
| `issued_at` | datetime | não |  |
| `called_at` | datetime | sim |  |
| `started_at` | datetime | sim |  |
| `finished_at` | datetime | sim |  |
| `cancelled_at` | datetime | sim |  |
| `no_show_at` | datetime | sim |  |
| `assigned_user_id` | bigint unsigned | sim | → users.id |
| `room_id` | bigint unsigned | sim | → attendance_rooms.id |
| `created_by_user_id` | bigint unsigned | sim | → users.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### attendance_rooms

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `name` | varchar(100) | não |  |
| `description` | varchar(255) | sim |  |
| `active` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### attendance_calls

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `attendance_ticket_id` | bigint unsigned | não | → attendance_tickets.id |
| `client_id` | bigint unsigned | não | → clients.id |
| `user_id` | bigint unsigned | não | → users.id |
| `room_id` | bigint unsigned | não | → attendance_rooms.id |
| `called_at` | datetime | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### attendance_records

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `attendance_ticket_id` | bigint unsigned | não | → attendance_tickets.id |
| `client_id` | bigint unsigned | não | → clients.id |
| `user_id` | bigint unsigned | sim | → users.id |
| `room_id` | bigint unsigned | sim | → attendance_rooms.id |
| `notes` | longtext | sim |  |
| `started_at` | datetime | sim |  |
| `finished_at` | datetime | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### queue

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `uuid` | char(36) | não | único |
| `id_client` | bigint unsigned | não | → clients.id |
| `id_specialities` | bigint unsigned | não | → specialities.id |
| `id_user` | bigint unsigned | não | → users.id |
| `done` | tinyint(1) | não |  |
| `date_of_realized` | date | sim |  |
| `done_at` | datetime | sim |  |
| `done_by` | bigint unsigned | sim | → users.id |
| `urgency` | tinyint(1) | não |  |
| `obs` | varchar(1000) | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### queue_attachments

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `queue_id` | bigint unsigned | não | → queue.id |
| `uploaded_by` | bigint unsigned | sim | → users.id |
| `disk` | varchar(30) | não |  |
| `path` | varchar(255) | não |  |
| `original_name` | varchar(255) | não |  |
| `mime_type` | varchar(100) | não |  |
| `size_bytes` | bigint unsigned | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### queue_treatment_plans

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `queue_id` | bigint unsigned | não | → queue.id |
| `speciality_id` | bigint unsigned | não | → specialities.id |
| `client_id` | bigint unsigned | não | → clients.id |
| `created_by_user_id` | bigint unsigned | não | → users.id |
| `total_sessions` | int unsigned | não |  |
| `weekdays` | json | não |  |
| `started_at` | date | não |  |
| `expected_end_at` | date | não |  |
| `status` | varchar(20) | não |  |
| `cancelled_by_user_id` | bigint unsigned | sim | → users.id |
| `cancelled_reason` | text | sim |  |
| `cancelled_at` | timestamp | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### queue_treatment_sessions

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `treatment_plan_id` | bigint unsigned | não | → queue_treatment_plans.id |
| `scheduled_date` | date | não |  |
| `original_scheduled_date` | date | sim |  |
| `status` | varchar(20) | não |  |
| `reschedule_reason` | text | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

## Laboratório

### categoria_exames

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(80) | não |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### exames

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(100) | não |  |
| `codigo` | varchar(30) | não |  |
| `categoria_exame_id` | bigint unsigned | sim | → categoria_exames.id |
| `descricao` | text | sim |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### exame_campos

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `exame_id` | bigint unsigned | não | → exames.id |
| `nome` | varchar(100) | não |  |
| `descricao` | varchar(200) | sim |  |
| `tipo_valor` | enum('numerico','texto','booleano','selecao') | não |  |
| `unidade` | varchar(30) | sim |  |
| `opcoes_selecao` | json | sim |  |
| `ordem` | int | não |  |
| `obrigatorio` | tinyint(1) | não |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### campo_referencias

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `exame_campo_id` | bigint unsigned | não | → exame_campos.id |
| `perfil` | enum('geral','adulto_m','adulto_f','crianca','idoso','gestante','recem_nascido','adolescente','adolescente_m','adolescente_f','crianca_m','crianca_f','idoso_m','idoso_f','gestante_t1','gestante_t2','gestante_t3') | não |  |
| `sexo` | enum('M','F') | sim |  |
| `idade_min_dias` | int unsigned | sim |  |
| `idade_max_dias` | int unsigned | sim |  |
| `valor_min` | decimal(10,3) | sim |  |
| `valor_max` | decimal(10,3) | sim |  |
| `valor_texto` | varchar(200) | sim |  |
| `descricao` | varchar(200) | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### pedidos_exame

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `client_id` | bigint unsigned | não | → clients.id |
| `criado_por` | bigint unsigned | não | → users.id |
| `data_pedido` | date | não |  |
| `data_coleta` | date | sim |  |
| `status` | enum('solicitado','coletado','em_analise','liberado','cancelado') | não |  |
| `medico_solicitante_id` | bigint unsigned | sim | → medicos_solicitantes.id |
| `observacoes` | text | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `deleted_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### pedido_exame_itens

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `pedido_exame_id` | bigint unsigned | não | → pedidos_exame.id |
| `exame_id` | bigint unsigned | não | → exames.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### resultado_exames

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `pedido_exame_id` | bigint unsigned | não | → pedidos_exame.id |
| `liberado_por` | bigint unsigned | sim | → users.id |
| `protocolo` | varchar(20) | sim | único |
| `senha_hash` | varchar(255) | sim |  |
| `pdf_path` | varchar(255) | sim |  |
| `data_liberacao` | timestamp | sim |  |
| `data_validade` | timestamp | sim |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### resultado_campos

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `resultado_exame_id` | bigint unsigned | não | → resultado_exames.id |
| `exame_campo_id` | bigint unsigned | não | → exame_campos.id |
| `exame_id` | bigint unsigned | não | → exames.id |
| `valor_numerico` | decimal(15,4) | sim |  |
| `valor_texto` | text | sim |  |
| `status_referencia` | enum('normal','baixo','alto','critico','indefinido') | não |  |
| `observacao` | text | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### medicos_solicitantes

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(100) | não |  |
| `crm` | varchar(20) | sim |  |
| `uf_crm` | varchar(2) | sim |  |
| `especialidade` | varchar(80) | sim |  |
| `telefone` | varchar(20) | sim |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### lab_configs

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `email_habilitado` | tinyint(1) | não |  |
| `imprimir_rascunho_exame` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `nome_estabelecimento` | varchar(255) | sim |  |
| `razao_social` | varchar(255) | sim |  |
| `endereco_rua` | varchar(255) | sim |  |
| `endereco_numero` | varchar(255) | sim |  |
| `endereco_bairro` | varchar(255) | sim |  |
| `endereco_cep` | varchar(8) | sim |  |
| `telefone` | varchar(255) | sim |  |
| `cnpj` | varchar(14) | sim |  |
| `email_lab` | varchar(255) | sim |  |
| `rodape1` | text | sim |  |
| `rodape2` | text | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

## TFD e viagens

### trips

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `user_id` | bigint unsigned | sim | → users.id |
| `driver_id` | bigint unsigned | sim | → users.id |
| `vehicle_id` | bigint unsigned | sim | → vehicles.id |
| `route_id` | bigint unsigned | não | → routes.id |
| `departure_time` | time | sim |  |
| `departure_date` | date | sim |  |
| `obs` | varchar(300) | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### trip_clients

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `trip_id` | bigint unsigned | não | → trips.id |
| `client_id` | bigint unsigned | não | → clients.id |
| `person_type` | enum('passenger','companion') | não |  |
| `companion_of` | bigint unsigned | sim |  |
| `phone` | varchar(20) | sim |  |
| `departure_location` | varchar(50) | sim |  |
| `destination_location` | varchar(50) | sim |  |
| `time` | time | não |  |
| `is_confirmed` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### trip_schedule_entries

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |
| `scheduled_date` | date | não |  |
| `client_id` | bigint unsigned | não | → clients.id |
| `person_type` | enum('passenger','companion') | não |  |
| `companion_of` | bigint unsigned | sim |  |
| `phone` | varchar(20) | sim |  |
| `departure_location` | varchar(50) | sim |  |
| `destination_location` | varchar(50) | sim |  |
| `time` | varchar(5) | sim |  |
| `status` | varchar(20) | não |  |
| `obs` | varchar(300) | sim |  |
| `created_by` | bigint unsigned | sim | → users.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### vehicles

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `id_user` | bigint unsigned | sim | → users.id |
| `brand` | varchar(20) | não |  |
| `model` | varchar(30) | não |  |
| `color` | varchar(10) | não |  |
| `year` | int unsigned | sim |  |
| `license_plate` | varchar(7) | não |  |
| `renavan` | varchar(11) | não |  |
| `chassis` | varchar(17) | não |  |
| `capacity` | int | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `active` | tinyint(1) | não |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### routes

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `id_user` | bigint unsigned | sim | → users.id |
| `origin` | varchar(50) | não |  |
| `origin_state` | char(2) | não |  |
| `destination` | varchar(50) | não |  |
| `destination_state` | char(2) | não |  |
| `distance` | int | não |  |
| `active` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

## Farmácia

### medicine_items

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `internal_code` | varchar(255) | não |  |
| `brand_name` | varchar(255) | sim |  |
| `active_ingredient` | varchar(255) | não |  |
| `concentration` | varchar(255) | não |  |
| `pharmaceutical_form` | varchar(255) | não |  |
| `presentation` | varchar(255) | não |  |
| `unit_measure` | varchar(20) | não |  |
| `ean_code` | varchar(255) | sim |  |
| `is_free_distribution` | tinyint(1) | não |  |
| `is_controlled` | tinyint(1) | não |  |
| `is_judicial_order` | tinyint(1) | não |  |
| `is_high_cost` | tinyint(1) | não |  |
| `active` | tinyint(1) | não |  |
| `technical_notes` | text | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `deleted_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### medicine_daily_statuses

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `medicine_item_id` | bigint unsigned | não | → medicine_items.id |
| `reference_date` | date | não |  |
| `availability_status` | enum('available','unavailable') | não |  |
| `available_quantity` | decimal(12,2) | sim |  |
| `restock_forecast_date` | date | sim |  |
| `public_note` | varchar(1000) | sim |  |
| `published_site_at` | timestamp | sim |  |
| `published_panel_at` | timestamp | sim |  |
| `updated_by_user_id` | bigint unsigned | sim | → users.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### medicine_monthly_acquisitions

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `medicine_item_id` | bigint unsigned | não | → medicine_items.id |
| `reference_month` | char(7) | não |  |
| `acquired_quantity` | decimal(12,2) | não |  |
| `unit_measure` | varchar(20) | não |  |
| `source_document` | varchar(255) | sim |  |
| `note` | varchar(1000) | sim |  |
| `published_at` | timestamp | sim |  |
| `updated_by_user_id` | bigint unsigned | sim | → users.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### medicine_publications

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `reference_type` | enum('daily','monthly') | não |  |
| `reference_id` | bigint unsigned | não |  |
| `channel` | enum('site','panel','instagram','facebook','other') | não |  |
| `status` | enum('pending','published','failed') | não |  |
| `payload_summary` | json | sim |  |
| `published_at` | timestamp | sim |  |
| `published_by_user_id` | bigint unsigned | sim | → users.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### pharmacy_acquisition_sources

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `name` | varchar(120) | não |  |
| `active` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### pharmacy_medicine_panel_settings

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `filter_is_free_distribution` | tinyint(1) | não |  |
| `filter_is_controlled` | tinyint(1) | não |  |
| `filter_is_judicial_order` | tinyint(1) | não |  |
| `filter_is_high_cost` | tinyint(1) | não |  |
| `filter_active` | tinyint(1) | não |  |
| `filter_show_all` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### pharmacy_pharmaceutical_forms

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `name` | varchar(120) | não |  |
| `active` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### pharmacy_presentations

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `name` | varchar(120) | não |  |
| `active` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### pharmacy_units

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `code` | varchar(20) | não |  |
| `name` | varchar(80) | não |  |
| `active` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

## Almoxarifado

### almoxarifado_arquivos

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `attachable_type` | varchar(120) | não |  |
| `attachable_id` | bigint unsigned | não |  |
| `nome_original` | varchar(255) | não |  |
| `caminho` | varchar(255) | não |  |
| `mime_type` | varchar(120) | sim |  |
| `tamanho_bytes` | bigint unsigned | sim |  |
| `tipo` | varchar(60) | sim |  |
| `user_id` | bigint unsigned | sim | → users.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### almoxarifado_categorias

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(120) | não |  |
| `observacoes` | text | sim |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### almoxarifado_configs

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `permitir_saida_sem_saldo` | tinyint(1) | não |  |
| `permitir_transferencia_entre_secretarias` | tinyint(1) | não |  |
| `exigir_justificativa_saida` | tinyint(1) | não |  |
| `exigir_localizacao_produto` | tinyint(1) | não |  |
| `notificar_estoque_minimo` | tinyint(1) | não |  |
| `estoque_minimo_alerta_percentual` | int unsigned | não |  |
| `permite_produto_sem_validade` | tinyint(1) | não |  |
| `observacoes` | text | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### almoxarifado_especies

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(120) | não |  |
| `observacoes` | text | sim |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### almoxarifado_estoques

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `almoxarifado_produto_id` | bigint unsigned | não | → almoxarifado_produtos.id |
| `almoxarifado_secretaria_id` | bigint unsigned | sim | → almoxarifado_secretarias.id |
| `quantidade_disponivel` | decimal(14,3) | não |  |
| `quantidade_reservada` | decimal(14,3) | não |  |
| `quantidade_em_separacao` | decimal(14,3) | não |  |
| `quantidade_entregue` | decimal(14,3) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### almoxarifado_fornecedores

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(150) | não |  |
| `documento` | varchar(30) | sim |  |
| `telefone` | varchar(30) | sim |  |
| `email` | varchar(120) | sim |  |
| `contato` | varchar(120) | sim |  |
| `endereco` | varchar(255) | sim |  |
| `observacoes` | text | sim |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### almoxarifado_localizacoes

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(150) | não |  |
| `almoxarifado` | varchar(120) | sim |  |
| `sala` | varchar(80) | sim |  |
| `corredor` | varchar(80) | sim |  |
| `estante` | varchar(80) | sim |  |
| `prateleira` | varchar(80) | sim |  |
| `gaveta` | varchar(80) | sim |  |
| `caixa` | varchar(80) | sim |  |
| `posicao` | varchar(80) | sim |  |
| `observacoes` | text | sim |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### almoxarifado_movimentacoes

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `almoxarifado_produto_id` | bigint unsigned | não | → almoxarifado_produtos.id |
| `almoxarifado_secretaria_origem_id` | bigint unsigned | sim | → almoxarifado_secretarias.id |
| `almoxarifado_secretaria_destino_id` | bigint unsigned | sim | → almoxarifado_secretarias.id |
| `tipo` | varchar(30) | não |  |
| `quantidade` | decimal(14,3) | não |  |
| `saldo_anterior` | decimal(14,3) | sim |  |
| `saldo_posterior` | decimal(14,3) | sim |  |
| `motivo` | varchar(150) | sim |  |
| `observacao` | text | sim |  |
| `documento_tipo` | varchar(60) | sim |  |
| `documento_id` | bigint unsigned | sim |  |
| `user_id` | bigint unsigned | sim | → users.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### almoxarifado_produtos

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(150) | não |  |
| `descricao` | text | sim |  |
| `codigo_interno` | varchar(60) | não |  |
| `codigo_barras` | varchar(80) | sim |  |
| `qr_code` | varchar(255) | sim |  |
| `almoxarifado_categoria_id` | bigint unsigned | sim | → almoxarifado_categorias.id |
| `almoxarifado_especie_id` | bigint unsigned | sim | → almoxarifado_especies.id |
| `almoxarifado_unidade_medida_id` | bigint unsigned | sim | → almoxarifado_unidades_medida.id |
| `almoxarifado_fornecedor_id` | bigint unsigned | sim | → almoxarifado_fornecedores.id |
| `almoxarifado_localizacao_id` | bigint unsigned | sim | → almoxarifado_localizacoes.id |
| `marca` | varchar(120) | sim |  |
| `modelo` | varchar(120) | sim |  |
| `fabricante` | varchar(120) | sim |  |
| `numero_serie` | varchar(120) | sim |  |
| `lote` | varchar(80) | sim |  |
| `validade` | date | sim |  |
| `estoque_minimo` | decimal(14,3) | não |  |
| `estoque_maximo` | decimal(14,3) | sim |  |
| `almoxarifado` | varchar(120) | sim |  |
| `sala` | varchar(80) | sim |  |
| `corredor` | varchar(80) | sim |  |
| `estante` | varchar(80) | sim |  |
| `prateleira` | varchar(80) | sim |  |
| `gaveta` | varchar(80) | sim |  |
| `caixa` | varchar(80) | sim |  |
| `posicao` | varchar(80) | sim |  |
| `observacao_localizacao` | text | sim |  |
| `imagem_url` | varchar(255) | sim |  |
| `ativo` | tinyint(1) | não |  |
| `observacoes` | text | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### almoxarifado_requisicao_itens

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `almoxarifado_requisicao_id` | bigint unsigned | não | → almoxarifado_requisicoes.id |
| `almoxarifado_produto_id` | bigint unsigned | não | → almoxarifado_produtos.id |
| `quantidade_solicitada` | decimal(14,3) | não |  |
| `quantidade_atendida` | decimal(14,3) | não |  |
| `quantidade_entregue` | decimal(14,3) | não |  |
| `observacao` | text | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### almoxarifado_requisicao_status_historicos

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `almoxarifado_requisicao_id` | bigint unsigned | não | → almoxarifado_requisicoes.id |
| `status_anterior` | varchar(30) | sim |  |
| `novo_status` | varchar(30) | não |  |
| `observacao` | text | sim |  |
| `user_id` | bigint unsigned | sim | → users.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### almoxarifado_requisicoes

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `numero` | varchar(40) | não |  |
| `almoxarifado_secretaria_id` | bigint unsigned | não | → almoxarifado_secretarias.id |
| `solicitante` | varchar(150) | não |  |
| `requisitante_user_id` | bigint unsigned | sim | → users.id |
| `data_solicitacao` | date | não |  |
| `status` | varchar(30) | não |  |
| `justificativa` | text | sim |  |
| `observacoes` | text | sim |  |
| `usuario_responsavel_id` | bigint unsigned | sim | → users.id |
| `data_atendimento` | datetime | sim |  |
| `data_entrega` | datetime | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### almoxarifado_secretarias

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(120) | não |  |
| `sigla` | varchar(20) | sim |  |
| `responsavel` | varchar(120) | sim |  |
| `contato` | varchar(120) | sim |  |
| `observacoes` | text | sim |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### almoxarifado_unidades_medida

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(80) | não |  |
| `sigla` | varchar(20) | sim |  |
| `observacoes` | text | sim |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

## Protocolo, documentos e kanban

### protocols

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `numero` | varchar(40) | não | único |
| `assunto` | varchar(200) | não |  |
| `descricao` | text | sim |  |
| `tipo` | varchar(40) | não |  |
| `visibility` | varchar(10) | não |  |
| `status` | varchar(40) | não |  |
| `prioridade` | varchar(20) | não |  |
| `solicitante_tipo` | varchar(20) | não |  |
| `solicitante_nome` | varchar(150) | sim |  |
| `solicitante_documento` | varchar(40) | sim |  |
| `origem_unit_id` | bigint unsigned | sim | → protocol_organizational_units.id |
| `destino_unit_id` | bigint unsigned | sim | → protocol_organizational_units.id |
| `responsavel_atual_id` | bigint unsigned | sim | → users.id |
| `criado_por_id` | bigint unsigned | sim | → users.id |
| `prazo_atendimento` | date | sim |  |
| `recebido_em` | datetime | sim |  |
| `encaminhado_em` | datetime | sim |  |
| `encerrado_em` | datetime | sim |  |
| `reaberto_em` | datetime | sim |  |
| `devolvido_em` | datetime | sim |  |
| `cancelado_em` | datetime | sim |  |
| `justificativa_encerramento` | text | sim |  |
| `justificativa_devolucao` | text | sim |  |
| `justificativa_cancelamento` | text | sim |  |
| `novo` | tinyint(1) | não |  |
| `vencido` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### protocol_types

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `codigo` | varchar(40) | não | único |
| `nome` | varchar(120) | não |  |
| `descricao` | text | sim |  |
| `ordem` | smallint unsigned | não |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### protocol_movements

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `protocol_id` | bigint unsigned | não | → protocols.id |
| `from_unit_id` | bigint unsigned | sim | → protocol_organizational_units.id |
| `to_unit_id` | bigint unsigned | sim | → protocol_organizational_units.id |
| `from_user_id` | bigint unsigned | sim | → users.id |
| `to_user_id` | bigint unsigned | sim | → users.id |
| `acao` | varchar(60) | não |  |
| `status_anterior` | varchar(40) | sim |  |
| `status_novo` | varchar(40) | sim |  |
| `observacao` | text | sim |  |
| `dados` | json | sim |  |
| `user_id` | bigint unsigned | sim | → users.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### protocol_comments

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `protocol_id` | bigint unsigned | não | → protocols.id |
| `user_id` | bigint unsigned | sim | → users.id |
| `tipo` | varchar(30) | não |  |
| `conteudo` | text | não |  |
| `privado` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### protocol_attachments

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `protocol_id` | bigint unsigned | não | → protocols.id |
| `user_id` | bigint unsigned | sim | → users.id |
| `nome_original` | varchar(255) | não |  |
| `caminho` | varchar(255) | não |  |
| `mime_type` | varchar(120) | sim |  |
| `tamanho_bytes` | bigint unsigned | sim |  |
| `descricao` | varchar(255) | sim |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### protocol_alerts

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(150) | não |  |
| `descricao` | text | sim |  |
| `modulo` | varchar(80) | não |  |
| `gatilho` | varchar(80) | não |  |
| `condicoes` | json | sim |  |
| `canais` | json | sim |  |
| `destinatarios` | json | sim |  |
| `template` | text | sim |  |
| `assunto` | varchar(255) | sim |  |
| `ativo` | tinyint(1) | não |  |
| `frequencia` | varchar(60) | sim |  |
| `prevenir_duplicidade` | tinyint(1) | não |  |
| `sistema` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### protocol_configs

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `allow_external_protocols` | tinyint(1) | não |  |
| `allow_reopen` | tinyint(1) | não |  |
| `notify_internal` | tinyint(1) | não |  |
| `notify_email` | tinyint(1) | não |  |
| `notify_whatsapp` | tinyint(1) | não |  |
| `default_priority` | varchar(20) | não |  |
| `default_due_days` | int unsigned | não |  |
| `evolution_base_url` | varchar(255) | sim |  |
| `evolution_api_key` | text | sim |  |
| `evolution_default_session` | varchar(120) | sim |  |
| `evolution_enabled` | tinyint(1) | não |  |
| `whatsapp_base_url` | varchar(255) | sim |  |
| `whatsapp_api_key` | text | sim |  |
| `whatsapp_instance_name` | varchar(120) | sim |  |
| `whatsapp_instance_token` | text | sim |  |
| `whatsapp_ativo` | tinyint(1) | não |  |
| `observacoes` | text | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### protocol_notifications

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `protocol_id` | bigint unsigned | sim | → protocols.id |
| `user_id` | bigint unsigned | sim | → users.id |
| `canal` | varchar(30) | não |  |
| `titulo` | varchar(150) | não |  |
| `mensagem` | text | não |  |
| `status_envio` | varchar(30) | não |  |
| `lida_em` | datetime | sim |  |
| `enviada_em` | datetime | sim |  |
| `erro` | text | sim |  |
| `dados` | json | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### protocol_organizational_units

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `parent_id` | bigint unsigned | sim | → protocol_organizational_units.id |
| `tipo` | varchar(40) | não |  |
| `codigo` | varchar(60) | sim |  |
| `nome` | varchar(150) | não |  |
| `descricao` | text | sim |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### protocol_user_units

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `user_id` | bigint unsigned | não | → users.id |
| `protocol_organizational_unit_id` | bigint unsigned | não | → protocol_organizational_units.id |
| `papel` | varchar(40) | sim |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### protocol_views

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `protocol_id` | bigint unsigned | não | → protocols.id |
| `user_id` | bigint unsigned | sim | → users.id |
| `departamento` | varchar(150) | sim |  |
| `equipe` | varchar(150) | sim |  |
| `session_key` | varchar(64) | sim |  |
| `visualized_at` | datetime | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### documents

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `document_type_id` | bigint unsigned | sim | → document_types.id |
| `titulo` | varchar(200) | não |  |
| `resumo` | text | sim |  |
| `sigilo` | varchar(30) | não |  |
| `status` | varchar(30) | não |  |
| `current_version_number` | int unsigned | não |  |
| `created_by` | bigint unsigned | sim | → users.id |
| `updated_by` | bigint unsigned | sim | → users.id |
| `deleted_by` | bigint unsigned | sim | → users.id |
| `deleted_at` | timestamp | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### document_versions

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `document_id` | bigint unsigned | não | → documents.id |
| `version_number` | int unsigned | não |  |
| `uploaded_by` | bigint unsigned | sim | → users.id |
| `disk` | varchar(30) | não |  |
| `path` | varchar(255) | não |  |
| `original_name` | varchar(255) | não |  |
| `mime_type` | varchar(120) | sim |  |
| `size_bytes` | bigint unsigned | sim |  |
| `checksum` | varchar(128) | sim |  |
| `encrypted` | tinyint(1) | não |  |
| `deleted_at` | timestamp | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### document_approvals

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `document_id` | bigint unsigned | não | → documents.id |
| `action` | varchar(40) | não |  |
| `status` | varchar(30) | não |  |
| `requested_by` | bigint unsigned | sim | → users.id |
| `approved_by` | bigint unsigned | sim | → users.id |
| `rejected_by` | bigint unsigned | sim | → users.id |
| `signer_user_ids` | json | não |  |
| `signed_user_ids` | json | sim |  |
| `signer_count` | int unsigned | não |  |
| `snapshot` | json | sim |  |
| `notes` | text | sim |  |
| `approved_at` | datetime | sim |  |
| `rejected_at` | datetime | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### document_configs

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `triple_signature_enabled` | tinyint(1) | não |  |
| `triple_signature_sigilos` | json | sim |  |
| `signer_user_1_id` | bigint unsigned | sim | → users.id |
| `signer_user_2_id` | bigint unsigned | sim | → users.id |
| `signer_user_3_id` | bigint unsigned | sim | → users.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### document_types

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `codigo` | varchar(60) | não | único |
| `nome` | varchar(150) | não |  |
| `descricao` | text | sim |  |
| `ordem` | int unsigned | não |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### letters

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `id_user` | int | não |  |
| `number` | int | não |  |
| `subject_matter` | varchar(100) | não |  |
| `sender` | varchar(50) | não |  |
| `recipient` | varchar(50) | não |  |
| `summary` | varchar(500) | sim |  |
| `obs` | varchar(3000) | sim |  |
| `fileurl` | varchar(100) | sim |  |
| `dispatched` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### letter_attachments

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `letter_id` | bigint unsigned | não | → letters.id |
| `uploaded_by` | bigint unsigned | sim | → users.id |
| `disk` | varchar(30) | não |  |
| `path` | varchar(255) | não |  |
| `original_name` | varchar(255) | não |  |
| `mime_type` | varchar(100) | não |  |
| `size_bytes` | bigint unsigned | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### ordinances

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `user_id` | bigint unsigned | não | → users.id |
| `number` | int unsigned | não |  |
| `year` | year | não |  |
| `type` | enum('normativa','ordinatoria') | não |  |
| `title` | varchar(255) | não |  |
| `subject` | varchar(255) | não |  |
| `summary` | text | sim |  |
| `content` | longtext | sim |  |
| `legal_basis` | text | sim |  |
| `department` | varchar(150) | não |  |
| `signatory_name` | varchar(150) | não |  |
| `signatory_role` | varchar(150) | sim |  |
| `publication_date` | date | não |  |
| `file_path` | varchar(255) | sim |  |
| `notes` | text | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### ordinance_attachments

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `ordinance_id` | bigint unsigned | não | → ordinances.id |
| `uploaded_by` | bigint unsigned | sim | → users.id |
| `disk` | varchar(30) | não |  |
| `path` | varchar(255) | não |  |
| `original_name` | varchar(255) | não |  |
| `mime_type` | varchar(100) | não |  |
| `size_bytes` | bigint unsigned | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### models

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `id_user` | int | não |  |
| `summary` | text | sim |  |
| `prompt` | longtext | sim |  |
| `model` | longtext | sim |  |
| `subject` | varchar(100) | sim |  |
| `sender` | varchar(50) | sim |  |
| `recipient` | varchar(50) | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### kanban_tasks

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `protocol_id` | bigint unsigned | sim | → protocols.id |
| `created_by_id` | bigint unsigned | sim | → users.id |
| `updated_by_id` | bigint unsigned | sim | → users.id |
| `responsavel_id` | bigint unsigned | sim | → users.id |
| `visibility` | varchar(20) | não |  |
| `titulo` | varchar(200) | não |  |
| `descricao` | text | sim |  |
| `status` | varchar(40) | não |  |
| `prioridade` | varchar(20) | não |  |
| `vencimento` | date | sim |  |
| `concluido_at` | timestamp | sim |  |
| `arquivado_at` | timestamp | sim |  |
| `ordem` | int unsigned | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `unit_id` | bigint unsigned | sim | → protocol_organizational_units.id |
| `fiscalizacao_id` | bigint unsigned | sim | → fiscalizacoes.id |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

## Vigilância sanitária

### estabelecimentos

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome_responsavel` | varchar(255) | não |  |
| `razao_social` | varchar(255) | sim |  |
| `nome_fantasia` | varchar(255) | sim |  |
| `cnpj` | varchar(18) | sim |  |
| `telefone` | varchar(20) | sim |  |
| `nome_estabelecimento` | varchar(255) | não |  |
| `endereco` | varchar(500) | não |  |
| `obs` | text | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `deleted_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### estabelecimento_cnaes

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `estabelecimento_id` | bigint unsigned | não | → estabelecimentos.id |
| `cnae_id` | bigint unsigned | não | → cnaes.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### cnaes

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `codigo` | varchar(10) | não | único |
| `descricao` | varchar(255) | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### alvaras

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `numero_alvara` | varchar(20) | não |  |
| `nivel_risco` | varchar(5) | não |  |
| `estabelecimento_id` | bigint unsigned | não | → estabelecimentos.id |
| `data_alvara` | date | não |  |
| `vencimento_alvara` | date | sim |  |
| `contato` | text | sim |  |
| `status` | varchar(50) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `deleted_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### fiscalizacoes

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `estabelecimento_id` | bigint unsigned | sim | → estabelecimentos.id |
| `fiscal_id` | bigint unsigned | sim | → users.id |
| `data_visita` | date | sim |  |
| `resultado` | varchar(30) | não |  |
| `observacoes` | text | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `deleted_at` | timestamp | sim |  |
| `protocolo` | varchar(20) | sim | único |
| `origem` | varchar(20) | não |  |
| `motivo_id` | bigint unsigned | sim | → peticao_motivos.id |
| `assunto` | varchar(200) | sim |  |
| `descricao_denuncia` | text | sim |  |
| `local_endereco` | varchar(255) | sim |  |
| `estabelecimento_nome_informado` | varchar(200) | sim |  |
| `denunciante_nome` | varchar(150) | sim |  |
| `denunciante_contato` | varchar(150) | sim |  |
| `senha_consulta_hash` | varchar(255) | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### fiscalizacao_movimentacoes

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `fiscalizacao_id` | bigint unsigned | não | → fiscalizacoes.id |
| `user_id` | bigint unsigned | sim | → users.id |
| `acao` | varchar(40) | não |  |
| `descricao` | text | sim |  |
| `dados` | json | sim |  |
| `publico` | tinyint(1) | não |  |
| `created_at` | timestamp | não |  |

### fiscalizacao_attachments

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `fiscalizacao_id` | bigint unsigned | não | → fiscalizacoes.id |
| `uploaded_by` | bigint unsigned | sim | → users.id |
| `origem` | varchar(20) | não |  |
| `disk` | varchar(30) | não |  |
| `path` | varchar(255) | não |  |
| `original_name` | varchar(255) | não |  |
| `mime_type` | varchar(100) | não |  |
| `size_bytes` | bigint unsigned | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### peticao_motivos

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(120) | não |  |
| `descricao` | varchar(255) | sim |  |
| `unit_id` | bigint unsigned | sim | → protocol_organizational_units.id |
| `ativo` | tinyint(1) | não |  |
| `ordem` | int unsigned | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### vigilancia_configs

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `estado` | varchar(2) | não |  |
| `nome_municipio` | varchar(255) | não |  |
| `nome_prefeitura` | varchar(255) | não |  |
| `cnpj_prefeitura` | varchar(14) | não |  |
| `nome_secretaria` | varchar(255) | não |  |
| `cnpj_secretaria` | varchar(14) | não |  |
| `divisao` | varchar(255) | não |  |
| `endereco` | varchar(255) | não |  |
| `cep` | varchar(8) | não |  |
| `telefone` | varchar(255) | não |  |
| `email` | varchar(255) | não |  |
| `nome_responsavel` | varchar(255) | não |  |
| `cargo_responsavel` | varchar(255) | não |  |
| `grant_type` | varchar(255) | não |  |
| `observacoes` | json | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### vigilancia_contatos_whatsapp

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `nome` | varchar(150) | não |  |
| `telefone` | varchar(20) | não |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

## Iluminação e ordens de serviço

### iluminacao_postes

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `protocolo` | varchar(20) | sim | único |
| `client_uuid` | char(36) | sim |  |
| `ponto_referencia` | varchar(160) | não |  |
| `logradouro` | varchar(160) | não |  |
| `numero` | varchar(20) | sim |  |
| `bairro` | varchar(100) | não |  |
| `latitude` | decimal(10,7) | não |  |
| `longitude` | decimal(10,7) | não |  |
| `precisao_m` | decimal(8,2) | sim |  |
| `status` | varchar(20) | não |  |
| `concluido_em` | timestamp | sim |  |
| `cadastrado_por` | bigint unsigned | sim | → users.id |
| `active` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### iluminacao_poste_fotos

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `poste_id` | bigint unsigned | não | → iluminacao_postes.id |
| `movimentacao_id` | bigint unsigned | sim | → iluminacao_poste_movimentacoes.id |
| `momento` | varchar(10) | não |  |
| `uploaded_by` | bigint unsigned | sim | → users.id |
| `disk` | varchar(30) | não |  |
| `path` | varchar(255) | não |  |
| `original_name` | varchar(255) | não |  |
| `mime_type` | varchar(100) | não |  |
| `size_bytes` | bigint unsigned | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### iluminacao_poste_movimentacoes

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `poste_id` | bigint unsigned | não | → iluminacao_postes.id |
| `user_id` | bigint unsigned | sim | → users.id |
| `acao` | varchar(30) | não |  |
| `status_anterior` | varchar(20) | sim |  |
| `status_novo` | varchar(20) | sim |  |
| `motivo` | text | sim |  |
| `dados` | json | sim |  |
| `created_at` | timestamp | não |  |

### ordens_servico

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `numero` | varchar(20) | não | único |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |
| `tipo_servico_id` | bigint unsigned | não | → os_tipos_servico.id |
| `solicitante_id` | bigint unsigned | não | → users.id |
| `tecnico_id` | bigint unsigned | sim | → users.id |
| `local` | varchar(160) | não |  |
| `endereco` | varchar(255) | sim |  |
| `descricao` | text | não |  |
| `problema` | text | sim |  |
| `prioridade` | varchar(10) | não |  |
| `prazo` | date | sim |  |
| `latitude` | decimal(10,7) | sim |  |
| `longitude` | decimal(10,7) | sim |  |
| `contato_nome` | varchar(120) | sim |  |
| `contato_telefone` | varchar(30) | sim |  |
| `status` | varchar(20) | não |  |
| `fechamento_relato` | text | sim |  |
| `materiais` | text | sim |  |
| `tempo_gasto_min` | int unsigned | sim |  |
| `concluida_em` | timestamp | sim |  |
| `prazo_avisado_em` | timestamp | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### os_fotos

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `ordem_servico_id` | bigint unsigned | não | → ordens_servico.id |
| `fase` | varchar(12) | não |  |
| `enviada_por` | bigint unsigned | sim | → users.id |
| `disk` | varchar(20) | não |  |
| `path` | varchar(255) | não |  |
| `original_name` | varchar(255) | sim |  |
| `mime_type` | varchar(100) | sim |  |
| `size_bytes` | bigint unsigned | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### os_historico

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `ordem_servico_id` | bigint unsigned | não | → ordens_servico.id |
| `user_id` | bigint unsigned | sim | → users.id |
| `acao` | varchar(30) | não |  |
| `status_de` | varchar(20) | sim |  |
| `status_para` | varchar(20) | sim |  |
| `observacao` | text | sim |  |
| `created_at` | timestamp | não |  |

### os_tipos_servico

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |
| `nome` | varchar(120) | não |  |
| `ativo` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### os_configuracoes

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `secretaria_id` | bigint unsigned | sim | → secretarias.id |
| `solicitante_aponta_tecnico` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

## Chat

### ouvidoria_manifestacoes

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `protocolo` | varchar(20) | não | único |
| `senha_hash` | varchar(255) | não |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |
| `tipo` | varchar(20) | não |  |
| `assunto` | varchar(160) | não |  |
| `descricao` | text | não |  |
| `status` | varchar(20) | não |  |
| `anonimo` | tinyint(1) | não |  |
| `nome` | varchar(120) | sim |  |
| `email` | varchar(160) | sim |  |
| `phone` | varchar(30) | sim |  |
| `cpf` | text | sim |  |
| `responsavel_id` | bigint unsigned | sim | → users.id |
| `resposta` | text | sim |  |
| `respondida_por_id` | bigint unsigned | sim | → users.id |
| `respondida_em` | timestamp | sim |  |
| `prazo_em` | timestamp | sim |  |
| `prazo_avisado_em` | timestamp | sim |  |
| `tentativas_falhas` | tinyint unsigned | não |  |
| `bloqueado_ate` | timestamp | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

`cpf` é guardado cifrado. `senha_hash` e os controles de bloqueio nunca saem pela API.

### ouvidoria_historico

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `manifestacao_id` | bigint unsigned | não | → ouvidoria_manifestacoes.id |
| `user_id` | bigint unsigned | sim | → users.id |
| `acao` | varchar(30) | não |  |
| `status_de` | varchar(20) | sim |  |
| `status_para` | varchar(20) | sim |  |
| `secretaria_de` | bigint unsigned | sim | → secretarias.id |
| `secretaria_para` | bigint unsigned | sim | → secretarias.id |
| `observacao` | text | sim |  |
| `interno` | tinyint(1) | não |  |
| `created_at` | timestamp | sim |  |

`user_id` nulo indica ação do cidadão.

### ouvidoria_anexos

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `manifestacao_id` | bigint unsigned | não | → ouvidoria_manifestacoes.id |
| `disk` | varchar(20) | não |  |
| `path` | varchar(255) | não |  |
| `original_name` | varchar(255) | sim |  |
| `mime_type` | varchar(100) | sim |  |
| `size_bytes` | bigint unsigned | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### ouvidoria_acessos_leitura

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `manifestacao_id` | bigint unsigned | não | PK, → ouvidoria_manifestacoes.id |
| `secretaria_id` | bigint unsigned | não | PK, → secretarias.id |

Secretarias de origem que continuam vendo, só em leitura, uma manifestação encaminhada.

### chat_conversations

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `type` | varchar(20) | não |  |
| `created_by` | bigint unsigned | não | → users.id |
| `last_message_at` | timestamp | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `secretaria_id` | bigint unsigned | não | → secretarias.id |

### chat_conversation_participants

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `conversation_id` | bigint unsigned | não | → chat_conversations.id |
| `user_id` | bigint unsigned | não | → users.id |
| `joined_at` | timestamp | não |  |
| `deleted_at` | timestamp | sim |  |
| `last_read_at` | timestamp | sim |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### chat_messages

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `conversation_id` | bigint unsigned | não | → chat_conversations.id |
| `sender_id` | bigint unsigned | não | → users.id |
| `body` | text | sim |  |
| `message_type` | varchar(20) | não |  |
| `status` | varchar(20) | não |  |
| `delivered_at` | timestamp | sim |  |
| `read_at` | timestamp | sim |  |
| `deleted_at` | timestamp | sim |  |
| `deleted_by` | bigint unsigned | sim | → users.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### chat_attachments

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `message_id` | bigint unsigned | não | → chat_messages.id |
| `original_name` | varchar(255) | não |  |
| `stored_name` | varchar(255) | não |  |
| `mime_type` | varchar(120) | não |  |
| `file_size` | bigint unsigned | não |  |
| `storage_path` | varchar(255) | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### chat_connections

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `user_id` | bigint unsigned | não | → users.id |
| `connection_id` | char(36) | não | único |
| `status` | varchar(20) | não |  |
| `last_seen_at` | timestamp | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### chat_usage_daily

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `usage_date` | date | não | único |
| `messages_sent` | bigint unsigned | não |  |
| `events_published` | bigint unsigned | não |  |
| `connection_events` | bigint unsigned | não |  |
| `peak_connections` | bigint unsigned | não |  |
| `attachments_sent` | bigint unsigned | não |  |
| `attachment_bytes` | bigint unsigned | não |  |
| `failed_events` | bigint unsigned | não |  |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |

### chat_realtime_configs

| Coluna | Tipo | Nulo | Chave |
|---|---|---|---|
| `id` | bigint unsigned | não | PK |
| `engine` | varchar(20) | não |  |
| `active` | tinyint(1) | não |  |
| `app_id` | text | sim |  |
| `app_key` | text | sim |  |
| `app_secret` | text | sim |  |
| `cluster` | varchar(40) | sim |  |
| `host` | varchar(255) | sim |  |
| `port` | smallint unsigned | sim |  |
| `scheme` | varchar(10) | não |  |
| `use_tls` | tinyint(1) | não |  |
| `rate_limit_decay_minutes` | tinyint unsigned | não |  |
| `rate_limit_global` | smallint unsigned | não |  |
| `rate_limit_sync` | smallint unsigned | não |  |
| `rate_limit_messages` | smallint unsigned | não |  |
| `rate_limit_typing` | smallint unsigned | não |  |
| `rate_limit_presence` | smallint unsigned | não |  |
| `auto_open_on_message` | tinyint(1) | não |  |
| `play_sound_on_message` | tinyint(1) | não |  |
| `updated_by` | bigint unsigned | sim | → users.id |
| `created_at` | timestamp | sim |  |
| `updated_at` | timestamp | sim |  |
| `chat_enabled` | tinyint(1) | não |  |

