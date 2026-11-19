# Administração e auditoria

Configurações (e-mail, WhatsApp, painel público, atalhos), trilha de auditoria, log de erros, bloqueio de IPs, mensagens enviadas e avisos do sistema.

> Gerado a partir das rotas reais da aplicação (`php artisan route:list`). Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs.

| Método | URL | Ação |
|---|---|---|
| `GET` | `/api/admin/backup/download` | Download |
| `GET` | `/api/admin/dashboard-atalhos` | Admin Index |
| `POST` | `/api/admin/dashboard-atalhos` | Criar |
| `DELETE` | `/api/admin/dashboard-atalhos/{atalho}` | Remover |
| `PUT` | `/api/admin/dashboard-atalhos/{atalho}` | Atualizar |
| `PUT` | `/api/admin/painel-publico` | Atualizar |
| `GET` | `/api/audit-logs` | Listar |
| `GET` | `/api/audit-logs/users` | Users |
| `POST` | `/api/audit/page-view` | Criar |
| `GET` | `/api/banned-ips` | Listar |
| `POST` | `/api/banned-ips` | Criar |
| `GET` | `/api/banned-ips/settings` | Settings |
| `PUT` | `/api/banned-ips/settings` | Update Settings |
| `DELETE` | `/api/banned-ips/{bannedIp}` | Remover |
| `GET` | `/api/email/config` | Detalhar |
| `POST` | `/api/email/config` | Atualizar |
| `POST` | `/api/email/testar` | Test |
| `GET` | `/api/errorlogs` | Listar |
| `POST` | `/api/errorlogs` | Criar |
| `DELETE` | `/api/errorlogs/{errorlog}` | Remover |
| `GET` | `/api/errorlogs/{errorlog}` | Detalhar |
| `PATCH` | `/api/errorlogs/{errorlog}` | Atualizar |
| `PUT` | `/api/errorlogs/{errorlog}` | Atualizar |
| `GET` | `/api/mensagens-enviadas` | Listar |
| `GET` | `/api/qrcode-logs` | Listar |
| `GET` | `/api/system-notices` | Listar |
| `POST` | `/api/system-notices` | Criar |
| `GET` | `/api/system-notices/active` | Active |
| `DELETE` | `/api/system-notices/{id}` | Remover |
| `POST` | `/api/system-notices/{id}/views` | Record View |
| `GET` | `/api/whatsapp/config` | Detalhar |
| `POST` | `/api/whatsapp/config` | Atualizar |
| `POST` | `/api/whatsapp/desconectar` | Disconnect |
| `POST` | `/api/whatsapp/enviar-teste` | Send Test |
| `GET` | `/api/whatsapp/qrcode` | Qrcode |
| `GET` | `/api/whatsapp/status` | Status |
| `POST` | `/api/whatsapp/testar` | Test |

---

## `GET /api/admin/backup/download`

**Download** · controller `BackupController@download`

- **Acesso:** Token Sanctum; somente administrador

## `GET /api/admin/dashboard-atalhos`

**Admin Index** · controller `DashboardAtalhoController@adminIndex`

- **Acesso:** Token Sanctum; somente administrador

## `POST /api/admin/dashboard-atalhos`

**Criar** · controller `DashboardAtalhoController@store`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`DashboardAtalhoRequest`):

| Campo | Regras |
|---|---|
| `titulo` | `required \| string \| max:60` |
| `url` | `required \| string \| max:500 \| regex:#^(/(?!/) \| https?://)#i` |
| `icone` | `nullable \| string \| max:40` |
| `ordem` | `nullable \| integer \| min:0` |
| `ativo` | `nullable \| boolean` |


## `DELETE /api/admin/dashboard-atalhos/{atalho}`

**Remover** · controller `DashboardAtalhoController@destroy`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `atalho`

## `PUT /api/admin/dashboard-atalhos/{atalho}`

**Atualizar** · controller `DashboardAtalhoController@update`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `atalho`
- **Corpo/validação** (`DashboardAtalhoRequest`):

| Campo | Regras |
|---|---|
| `titulo` | `required \| string \| max:60` |
| `url` | `required \| string \| max:500 \| regex:#^(/(?!/) \| https?://)#i` |
| `icone` | `nullable \| string \| max:40` |
| `ordem` | `nullable \| integer \| min:0` |
| `ativo` | `nullable \| boolean` |


## `PUT /api/admin/painel-publico`

**Atualizar** · controller `PainelPublicoController@update`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`UpdatePainelPublicoRequest`):

| Campo | Regras |
|---|---|
| `pagina_url` | `required \| string \| max:500 \| regex:#^(/(?!/) \| https?://)#i` |
| `esquerda_url` | `required \| string \| max:500 \| regex:#^(/(?!/) \| https?://)#i` |
| `direita_url` | `required \| string \| max:500 \| regex:#^(/(?!/) \| https?://)#i` |
| `largura_esquerda` | `required \| integer \| min:20 \| max:80` |


## `GET /api/audit-logs`

**Listar** · controller `AuditLogController@index`

- **Acesso:** Token Sanctum; somente administrador
- **Outros parâmetros lidos pelo controller:** `user_id`, `user_name`, `action`, `model_type`, `date_from`, `date_to`

## `GET /api/audit-logs/users`

**Users** · controller `AuditLogController@users`

- **Acesso:** Token Sanctum; somente administrador
- **Outros parâmetros lidos pelo controller:** `date_from`, `date_to`, `action`, `model_type`

## `POST /api/audit/page-view`

**Criar** · controller `PageViewAuditController@store`

- **Acesso:** Token Sanctum
- **Corpo/validação** (`StorePageViewAuditRequest`):

| Campo | Regras |
|---|---|
| `path` | `required \| string \| max:255` |
| `label` | `nullable \| string \| max:100` |
| `filtros` | `nullable \| array` |


## `GET /api/banned-ips`

**Listar** · controller `BannedIpController@index`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`IndexBannedIpRequest`):

| Campo | Regras |
|---|---|
| `date_from` | `nullable \| date` |
| `date_to` | `nullable \| date \| after_or_equal:date_from` |

- **Outros parâmetros lidos pelo controller:** `per_page`

## `POST /api/banned-ips`

**Criar** · controller `BannedIpController@store`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`StoreBannedIpRequest`):

| Campo | Regras |
|---|---|
| `ip` | `required \| ip` |
| `reason` | `nullable \| string \| max:255` |


## `GET /api/banned-ips/settings`

**Settings** · controller `BannedIpController@settings`

- **Acesso:** Token Sanctum; somente administrador

## `PUT /api/banned-ips/settings`

**Update Settings** · controller `BannedIpController@updateSettings`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`UpdateBannedIpSettingsRequest`):

| Campo | Regras |
|---|---|
| `strike_limit` | `required \| integer \| min:1 \| max:1000` |


## `DELETE /api/banned-ips/{bannedIp}`

**Remover** · controller `BannedIpController@destroy`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `bannedIp`

## `GET /api/email/config`

**Detalhar** · controller `EmailConfigController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/configuracoes/email`

## `POST /api/email/config`

**Atualizar** · controller `EmailConfigController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/configuracoes/email`
- **Corpo/validação** (`UpdateEmailConfigRequest`):

| Campo | Regras |
|---|---|
| `smtp_host` | `nullable \| string \| max:255` |
| `smtp_port` | `nullable \| integer \| min:1 \| max:65535` |
| `smtp_username` | `nullable \| string \| max:255` |
| `smtp_password` | `nullable \| string` |
| `smtp_encryption` | `nullable \| string \| max:20` |
| `from_address` | `nullable \| email \| max:255` |
| `from_name` | `nullable \| string \| max:255` |
| `email_ativo` | `boolean` |


## `POST /api/email/testar`

**Test** · controller `EmailConfigController@test`

- **Acesso:** Token Sanctum; página em Perfis: `/configuracoes/email`
- **Corpo/validação** (`TestEmailConfigRequest`):

| Campo | Regras |
|---|---|
| `destinatario` | `required \| email` |


## `GET /api/errorlogs`

**Listar** · controller `ErrorLogController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/errorlogs`

## `POST /api/errorlogs`

**Criar** · controller `ErrorLogController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/errorlogs`

## `DELETE /api/errorlogs/{errorlog}`

**Remover** · controller `ErrorLogController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/errorlogs`
- **Parâmetros de rota:** `errorlog`

## `GET /api/errorlogs/{errorlog}`

**Detalhar** · controller `ErrorLogController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/errorlogs`
- **Parâmetros de rota:** `errorlog`

## `PATCH /api/errorlogs/{errorlog}`

**Atualizar** · controller `ErrorLogController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/errorlogs`
- **Parâmetros de rota:** `errorlog`

## `PUT /api/errorlogs/{errorlog}`

**Atualizar** · controller `ErrorLogController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/errorlogs`
- **Parâmetros de rota:** `errorlog`

## `GET /api/mensagens-enviadas`

**Listar** · controller `MessageLogController@index`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`ListMessageLogsRequest`):

| Campo | Regras |
|---|---|
| `canal` | `nullable \| in:whatsapp,email` |
| `busca` | `nullable \| string \| max:100` |
| `de` | `nullable \| date` |
| `ate` | `nullable \| date \| after_or_equal:de` |
| `per_page` | `nullable \| integer \| between:1,100` |


## `GET /api/qrcode-logs`

**Listar** · controller `QRCodeLogController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/qrcodelogs`

## `GET /api/system-notices`

**Listar** · controller `SystemNoticeController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/avisos`

## `POST /api/system-notices`

**Criar** · controller `SystemNoticeController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/avisos`
- **Corpo/validação** (`StoreSystemNoticeRequest`):

| Campo | Regras |
|---|---|
| `title` | `required \| string \| max:160` |
| `subtitle` | `nullable \| string \| max:180` |
| `body` | `required \| string` |
| `image_data` | `nullable \| string` |
| `times_per_day` | `required \| integer \| min:1 \| max:24` |
| `interval_minutes` | `required \| integer \| min:1 \| max:1440` |
| `target_user_id` | `nullable \| integer \| exists:users,id` |
| `valid_until` | `nullable \| date` |
| `is_active` | `nullable \| boolean` |


## `GET /api/system-notices/active`

**Active** · controller `SystemNoticeController@active`

- **Acesso:** Token Sanctum

## `DELETE /api/system-notices/{id}`

**Remover** · controller `SystemNoticeController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/avisos`
- **Parâmetros de rota:** `id`

## `POST /api/system-notices/{id}/views`

**Record View** · controller `SystemNoticeController@recordView`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `id`

## `GET /api/whatsapp/config`

**Detalhar** · controller `WhatsappConfigController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/configuracoes/whatsapp`

## `POST /api/whatsapp/config`

**Atualizar** · controller `WhatsappConfigController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/configuracoes/whatsapp`
- **Corpo/validação** (`UpdateWhatsappConfigRequest`):

| Campo | Regras |
|---|---|
| `whatsapp_base_url` | `nullable \| string \| max:255` |
| `whatsapp_api_key` | `nullable \| string` |
| `whatsapp_instance_name` | `nullable \| string \| max:120` |
| `whatsapp_instance_token` | `nullable \| string` |
| `whatsapp_ativo` | `boolean` |


## `POST /api/whatsapp/desconectar`

**Disconnect** · controller `WhatsappConfigController@disconnect`

- **Acesso:** Token Sanctum; página em Perfis: `/configuracoes/whatsapp`

## `POST /api/whatsapp/enviar-teste`

**Send Test** · controller `WhatsappConfigController@sendTest`

- **Acesso:** Token Sanctum; página em Perfis: `/configuracoes/whatsapp`
- **Corpo/validação** (`SendTestWhatsappConfigRequest`):

| Campo | Regras |
|---|---|
| `telefone` | `required \| string` |


## `GET /api/whatsapp/qrcode`

**Qrcode** · controller `WhatsappConfigController@qrcode`

- **Acesso:** Token Sanctum; página em Perfis: `/configuracoes/whatsapp`

## `GET /api/whatsapp/status`

**Status** · controller `WhatsappConfigController@status`

- **Acesso:** Token Sanctum; página em Perfis: `/configuracoes/whatsapp`

## `POST /api/whatsapp/testar`

**Test** · controller `WhatsappConfigController@test`

- **Acesso:** Token Sanctum; página em Perfis: `/configuracoes/whatsapp`

