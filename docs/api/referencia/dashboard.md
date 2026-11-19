# Dashboards

Indicadores gerenciais por módulo, atalhos do dashboard e painel executivo.

> Gerado a partir das rotas reais da aplicação (`php artisan route:list`). Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs.

| Método | URL | Ação |
|---|---|---|
| `GET` | `/api/dashboard/almoxarifado` | Almoxarifado |
| `GET` | `/api/dashboard/arquivo` | Arquivo |
| `GET` | `/api/dashboard/atalhos` | Listar |
| `GET` | `/api/dashboard/chat` | Dashboard |
| `GET` | `/api/dashboard/conformidades` | Conformidades |
| `GET` | `/api/dashboard/farmacia` | Farmacia |
| `GET` | `/api/dashboard/fila` | Fila |
| `GET` | `/api/dashboard/inicio` | Inicio |
| `GET` | `/api/dashboard/laboratorio` | Laboratorio |
| `GET` | `/api/dashboard/logs` | Logs |
| `GET` | `/api/dashboard/meus-atalhos` | Listar |
| `POST` | `/api/dashboard/meus-atalhos` | Criar |
| `PUT` | `/api/dashboard/meus-atalhos/ordem` | Reordenar |
| `DELETE` | `/api/dashboard/meus-atalhos/{id}` | Remover |
| `GET` | `/api/dashboard/tfd` | Tfd |
| `GET` | `/api/dashboard/vigilancia` | Vigilancia |

---

## `GET /api/dashboard/almoxarifado`

**Almoxarifado** · controller `DashboardController@almoxarifado`

- **Acesso:** Token Sanctum; gate: dashboard-almoxarifado
- **Limite de requisições:** 120 por minuto

## `GET /api/dashboard/arquivo`

**Arquivo** · controller `DashboardController@arquivo`

- **Acesso:** Token Sanctum; gate: dashboard-arquivo
- **Limite de requisições:** 120 por minuto

## `GET /api/dashboard/atalhos`

**Listar** · controller `DashboardAtalhoController@index`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 120 por minuto

## `GET /api/dashboard/chat`

**Dashboard** · controller `ChatController@dashboard`

- **Acesso:** Token Sanctum; gate: dashboard-chat; perfil com chat liberado
- **Limite de requisições:** 120 por minuto

## `GET /api/dashboard/conformidades`

**Conformidades** · controller `DashboardController@conformidades`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 120 por minuto

## `GET /api/dashboard/farmacia`

**Farmacia** · controller `DashboardController@farmacia`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 120 por minuto
- **Outros parâmetros lidos pelo controller:** `janela_dias`, `janela_meses`

## `GET /api/dashboard/fila`

**Fila** · controller `DashboardController@fila`

- **Acesso:** Token Sanctum; gate: dashboard-fila
- **Limite de requisições:** 120 por minuto

## `GET /api/dashboard/inicio`

**Inicio** · controller `DashboardController@inicio`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 120 por minuto

## `GET /api/dashboard/laboratorio`

**Laboratorio** · controller `DashboardController@laboratorio`

- **Acesso:** Token Sanctum; gate: dashboard-laboratorio
- **Limite de requisições:** 120 por minuto

## `GET /api/dashboard/logs`

**Logs** · controller `DashboardController@logs`

- **Acesso:** Token Sanctum; gate: dashboard-logs
- **Limite de requisições:** 120 por minuto

## `GET /api/dashboard/meus-atalhos`

**Listar** · controller `UserAtalhoController@index`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 120 por minuto

## `POST /api/dashboard/meus-atalhos`

**Criar** · controller `UserAtalhoController@store`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 120 por minuto
- **Corpo/validação** (`UserAtalhoRequest`):

| Campo | Regras |
|---|---|
| `titulo` | `required \| string \| max:80` |
| `url` | `required \| string \| max:500 \| regex:#^/(?!/)#` |
| `icone` | `nullable \| string \| max:40` |


## `PUT /api/dashboard/meus-atalhos/ordem`

**Reordenar** · controller `UserAtalhoController@reordenar`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 120 por minuto
- **Corpo/validação** (`ReordenarUserAtalhosRequest`):

| Campo | Regras |
|---|---|
| `ids` | `required \| array \| min:1 \| max:9` |
| `ids.*` | `integer \| distinct` |


## `DELETE /api/dashboard/meus-atalhos/{id}`

**Remover** · controller `UserAtalhoController@destroy`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 120 por minuto
- **Parâmetros de rota:** `id`

## `GET /api/dashboard/tfd`

**Tfd** · controller `DashboardController@tfd`

- **Acesso:** Token Sanctum; gate: dashboard-tfd
- **Limite de requisições:** 120 por minuto
- **Outros parâmetros lidos pelo controller:** `periodo`

## `GET /api/dashboard/vigilancia`

**Vigilancia** · controller `DashboardController@vigilancia`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 120 por minuto

