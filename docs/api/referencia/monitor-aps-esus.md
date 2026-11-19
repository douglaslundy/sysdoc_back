# Monitor APS e painel e-SUS

Indicadores da Atenção Primária e painéis lidos do banco e-SUS PEC (somente leitura).

> Gerado a partir das rotas reais da aplicação (`php artisan route:list`). Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs.

| Método | URL | Ação |
|---|---|---|
| `GET` | `/api/monitor-aps/cidadaos` | Listar |
| `GET` | `/api/monitor-aps/cidadaos/agentes` | Agentes |
| `GET` | `/api/monitor-aps/config/equipes` | Equipes |
| `GET` | `/api/monitor-aps/config/explorar` | Explorar |
| `GET` | `/api/monitor-aps/config/load` | Load |
| `POST` | `/api/monitor-aps/config/save` | Save |
| `GET` | `/api/monitor-aps/config/status` | Status |
| `POST` | `/api/monitor-aps/config/test` | Testar |
| `GET` | `/api/monitor-aps/indicadores/historico` | Historico |
| `GET` | `/api/monitor-aps/indicadores/qualidade` | Qualidade |
| `GET` | `/api/monitor-aps/indicadores/qualidade/{id}` | Qualidade Indicador |
| `GET` | `/api/monitor-aps/indicadores/repasse` | Repasse |
| `GET` | `/api/monitor-aps/indicadores/resumo` | Resumo |
| `GET` | `/api/monitor-aps/indicadores/vinculo` | Vinculo |
| `GET` | `/api/monitor-aps/minhas-equipes` | Minhas Equipes |
| `GET` | `/api/monitor-aps/visitas` | Listar |
| `GET` | `/api/monitor-aps/visitas/agentes` | Agentes |
| `GET` | `/api/monitor-aps/visitas/debug/{id}` | Show Debug |
| `GET` | `/api/monitor-aps/visitas/equipes` | Equipes |
| `GET` | `/api/monitor-aps/visitas/evolucao` | Evolucao |
| `GET` | `/api/monitor-aps/visitas/evolucao/anos` | Anos Disponiveis |
| `GET` | `/api/monitor-aps/visitas/lista` | Lista |
| `GET` | `/api/monitor-aps/visitas/mapa` | Mapa |
| `GET` | `/api/monitor-aps/visitas/responsabilidade` | Responsabilidade |
| `GET` | `/api/monitor-aps/visitas/resumo` | Resumo |
| `GET` | `/api/monitor-aps/visitas/{id}` | Detalhar |
| `GET` | `/api/painel-esus/default-cnes` | Default Cnes |
| `GET` | `/api/painel-esus/fila` | Fila |
| `GET` | `/api/painel-esus/filtros` | Filtros |
| `GET` | `/api/painel-esus/statuses` | Statuses |
| `GET` | `/api/painel-esus/unidades` | Unidades |

---

## `GET /api/monitor-aps/cidadaos`

**Listar** · controller `CidadaoAcsController@index`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto
- **Corpo/validação** (`IndexCidadaoAcsRequest`):

| Campo | Regras |
|---|---|
| `ine` | `nullable \| string` |
| `profissional_id` | `nullable \| integer` |
| `agente` | `nullable \| string \| max:255` |
| `agente_cns` | `nullable \| string \| max:255` |
| `condicao` | `nullable \| string \| in:gestante,has,dm,idoso,obito` |
| `busca` | `nullable \| string \| min:3 \| max:100` |
| `multi_domicilio` | `nullable \| boolean` |
| `idade_min` | `nullable \| integer \| min:0 \| max:130` |
| `idade_max` | `nullable \| integer \| min:0 \| max:130` |
| `sort` | `nullable \| string \| in:nome,idade` |
| `dir` | `nullable \| string \| in:asc,desc` |
| `page` | `nullable \| integer \| min:1` |
| `per_page` | `nullable \| integer \| min:10 \| max:200` |


## `GET /api/monitor-aps/cidadaos/agentes`

**Agentes** · controller `CidadaoAcsController@agentes`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto
- **Corpo/validação** (`AgentesCidadaoAcsRequest`):

| Campo | Regras |
|---|---|
| `ine` | `nullable \| string` |


## `GET /api/monitor-aps/config/equipes`

**Equipes** · controller `MonitorApsConfigController@equipes`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto

## `GET /api/monitor-aps/config/explorar`

**Explorar** · controller `MonitorApsConfigController@explorar`

- **Acesso:** Token Sanctum; somente administrador; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto

## `GET /api/monitor-aps/config/load`

**Load** · controller `MonitorApsConfigController@load`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto

## `POST /api/monitor-aps/config/save`

**Save** · controller `MonitorApsConfigController@save`

- **Acesso:** Token Sanctum; somente administrador; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto
- **Corpo/validação** (`SaveMonitorApsConfigRequest`):

| Campo | Regras |
|---|---|
| `host` | `required \| string` |
| `database` | `required \| string` |
| `user` | `required \| string` |
| `port` | `nullable \| integer` |
| `password` | `nullable \| string` |
| `municipio_ibge` | `nullable \| string` |
| `municipio_nome` | `nullable \| string` |
| `estrato_ied` | `nullable \| integer \| min:1 \| max:4` |


## `GET /api/monitor-aps/config/status`

**Status** · controller `MonitorApsConfigController@status`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto

## `POST /api/monitor-aps/config/test`

**Testar** · controller `MonitorApsConfigController@testar`

- **Acesso:** Token Sanctum; somente administrador; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto
- **Corpo/validação** (`TestMonitorApsConnectionRequest`):

| Campo | Regras |
|---|---|
| `host` | `required \| string` |
| `database` | `required \| string` |
| `user` | `required \| string` |
| `port` | `nullable \| integer` |
| `password` | `nullable \| string` |


## `GET /api/monitor-aps/indicadores/historico`

**Historico** · controller `MonitorApsController@historico`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto
- **Outros parâmetros lidos pelo controller:** `indicador_id`, `anos`

## `GET /api/monitor-aps/indicadores/qualidade`

**Qualidade** · controller `MonitorApsController@qualidade`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto

## `GET /api/monitor-aps/indicadores/qualidade/{id}`

**Qualidade Indicador** · controller `MonitorApsController@qualidadeIndicador`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto
- **Parâmetros de rota:** `id`

## `GET /api/monitor-aps/indicadores/repasse`

**Repasse** · controller `MonitorApsController@repasse`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto

## `GET /api/monitor-aps/indicadores/resumo`

**Resumo** · controller `MonitorApsController@resumo`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto

## `GET /api/monitor-aps/indicadores/vinculo`

**Vinculo** · controller `MonitorApsController@vinculo`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto

## `GET /api/monitor-aps/minhas-equipes`

**Minhas Equipes** · controller `MonitorApsController@minhasEquipes`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto

## `GET /api/monitor-aps/visitas`

**Listar** · controller `VisitaAcsController@index`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto
- **Corpo/validação** (`IndexVisitaAcsRequest`):

| Campo | Regras |
|---|---|
| `ano` | `required \| integer \| min:2020 \| max:2030` |
| `mes` | `required \| integer \| min:1 \| max:12` |
| `ine` | `nullable \| string` |
| `agente` | `nullable \| string` |
| `agente_cns` | `nullable \| string \| max:255` |
| `page` | `nullable \| integer \| min:1` |
| `per_page` | `nullable \| integer \| min:1 \| max:100` |


## `GET /api/monitor-aps/visitas/agentes`

**Agentes** · controller `VisitaAcsController@agentes`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto
- **Corpo/validação** (`AgentesVisitaAcsRequest`):

| Campo | Regras |
|---|---|
| `ano` | `required \| integer \| min:2020 \| max:2030` |
| `mes` | `required \| integer \| min:1 \| max:12` |
| `ine` | `nullable \| string` |
| `agente` | `nullable \| string` |
| `agente_cns` | `nullable \| string \| max:255` |
| `desfecho` | `nullable \| integer \| in:1,2,3` |
| `has_geo` | `nullable \| string \| in:sim,nao` |


## `GET /api/monitor-aps/visitas/debug/{id}`

**Show Debug** · controller `VisitaAcsController@showDebug`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto
- **Parâmetros de rota:** `id`

## `GET /api/monitor-aps/visitas/equipes`

**Equipes** · controller `VisitaAcsController@equipes`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto

## `GET /api/monitor-aps/visitas/evolucao`

**Evolucao** · controller `VisitaAcsController@evolucao`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto
- **Corpo/validação** (`EvolucaoVisitaAcsRequest`):

| Campo | Regras |
|---|---|
| `ine` | `nullable \| string` |
| `agente` | `nullable \| string` |
| `agente_cns` | `nullable \| string \| max:255` |
| `desfecho` | `nullable \| integer \| in:1,2,3` |
| `has_geo` | `nullable \| string \| in:sim,nao` |
| `ano` | `nullable \| integer \| min:2000 \| max:2099` |


## `GET /api/monitor-aps/visitas/evolucao/anos`

**Anos Disponiveis** · controller `VisitaAcsController@anosDisponiveis`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto

## `GET /api/monitor-aps/visitas/lista`

**Lista** · controller `VisitaAcsController@lista`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto
- **Corpo/validação** (`ListaVisitaAcsRequest`):

| Campo | Regras |
|---|---|
| `ano` | `required \| integer \| min:2020 \| max:2030` |
| `mes` | `required \| integer \| min:1 \| max:12` |
| `ine` | `nullable \| string` |
| `agente` | `nullable \| string` |
| `agente_cns` | `nullable \| string \| max:255` |
| `desfecho` | `nullable \| integer` |
| `has_geo` | `nullable \| in:sim,nao` |
| `page` | `nullable \| integer \| min:1` |
| `per_page` | `nullable \| integer \| min:1 \| max:100` |


## `GET /api/monitor-aps/visitas/mapa`

**Mapa** · controller `VisitaAcsController@mapa`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto
- **Corpo/validação** (`MapaVisitaAcsRequest`):

| Campo | Regras |
|---|---|
| `ano` | `required \| integer \| min:2020 \| max:2030` |
| `mes` | `required \| integer \| min:1 \| max:12` |
| `ine` | `nullable \| string` |
| `agente` | `nullable \| string` |
| `agente_cns` | `nullable \| string \| max:255` |
| `busca` | `nullable \| string \| max:200` |


## `GET /api/monitor-aps/visitas/responsabilidade`

**Responsabilidade** · controller `VisitaAcsController@responsabilidade`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto
- **Outros parâmetros lidos pelo controller:** `ine`

## `GET /api/monitor-aps/visitas/resumo`

**Resumo** · controller `VisitaAcsController@resumo`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto
- **Corpo/validação** (`ResumoVisitaAcsRequest`):

| Campo | Regras |
|---|---|
| `ano` | `required \| integer \| min:2020 \| max:2030` |
| `mes` | `required \| integer \| min:1 \| max:12` |
| `ine` | `nullable \| string` |
| `agente` | `nullable \| string` |
| `agente_cns` | `nullable \| string \| max:255` |
| `desfecho` | `nullable \| integer \| in:1,2,3` |
| `has_geo` | `nullable \| string \| in:sim,nao` |


## `GET /api/monitor-aps/visitas/{id}`

**Detalhar** · controller `VisitaAcsController@show`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Limite de requisições:** 180 por minuto
- **Parâmetros de rota:** `id`

## `GET /api/painel-esus/default-cnes`

**Default Cnes** · controller `PainelEsusController@defaultCnes`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS

## `GET /api/painel-esus/fila`

**Fila** · controller `PainelEsusController@fila`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Corpo/validação** (`FilaPainelEsusRequest`):

| Campo | Regras |
|---|---|
| `cnes` | `required \| string \| max:20` |
| `equipe` | `nullable \| integer` |
| `profissional` | `nullable \| integer` |
| `data` | `nullable \| date_format:Y-m-d` |
| `data_inicio` | `nullable \| date_format:Y-m-d` |
| `data_fim` | `nullable \| date_format:Y-m-d` |
| `situacao` | `nullable \| in:aguardando,atendidos,nao_aguardaram` |


## `GET /api/painel-esus/filtros`

**Filtros** · controller `PainelEsusController@filtros`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS
- **Corpo/validação** (`FiltrosPainelEsusRequest`):

| Campo | Regras |
|---|---|
| `cnes` | `required \| string \| max:20` |
| `data` | `nullable \| date_format:Y-m-d` |
| `data_inicio` | `nullable \| date_format:Y-m-d` |
| `data_fim` | `nullable \| date_format:Y-m-d` |
| `equipe` | `nullable \| integer` |


## `GET /api/painel-esus/statuses`

**Statuses** · controller `PainelEsusController@statuses`

- **Acesso:** Token Sanctum; somente secretaria saude

## `GET /api/painel-esus/unidades`

**Unidades** · controller `PainelEsusController@unidades`

- **Acesso:** Token Sanctum; somente secretaria saude; restrito por equipe APS

