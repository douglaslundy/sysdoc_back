# Cidadãos

Cadastro central do cidadão, histórico consolidado, relatório detalhado e conformidade cadastral.

> Gerado a partir das rotas reais da aplicação (`php artisan route:list`). Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs.

| Método | URL | Ação |
|---|---|---|
| `GET` | `/api/clients` | Listar |
| `POST` | `/api/clients` | Criar |
| `GET` | `/api/clients/buscar-cpf-cns` | Buscar Por Cpf Cns |
| `DELETE` | `/api/clients/duplicates` | Destroy Duplicate Candidates |
| `GET` | `/api/clients/duplicates` | Duplicate Candidates |
| `GET` | `/api/clients/select` | Select |
| `DELETE` | `/api/clients/{client}` | Remover |
| `GET` | `/api/clients/{client}` | Detalhar |
| `PATCH` | `/api/clients/{client}` | Atualizar |
| `PUT` | `/api/clients/{client}` | Atualizar |
| `GET` | `/api/clients/{client}/historico` | Listar |
| `DELETE` | `/api/clients/{client}/photo` | Destroy Client |
| `GET` | `/api/clients/{client}/photo` | Show Client |
| `POST` | `/api/clients/{client}/photo` | Store Client |
| `POST` | `/api/conformidade-cidadao/analisar` | Analisar |
| `POST` | `/api/conformidade-cidadao/aplicar/{job_id}` | Aplicar |
| `POST` | `/api/conformidade-cidadao/cancelar/{job_id}` | Cancelar |
| `GET` | `/api/conformidade-cidadao/erros/{job_id}` | Erros |
| `GET` | `/api/conformidade-cidadao/historico` | Historico |
| `GET` | `/api/conformidade-cidadao/itens/{job_id}` | Itens |
| `GET` | `/api/conformidade-cidadao/status/{job_id}` | Status |
| `GET` | `/api/detailed-client-report` | Detailed Client Report |
| `GET` | `/api/states` | Listar |

---

## `GET /api/clients`

**Listar** · controller `ClientController@index`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Corpo/validação** (`ListClientsRequest`):

| Campo | Regras |
|---|---|
| `search` | `nullable \| string \| max:120` |
| `somente_nome` | `nullable \| boolean` |
| `per_page` | `nullable \| integer \| min:1 \| max:100` |
| `limit` | `nullable \| integer \| min:1 \| max:50` |


## `POST /api/clients`

**Criar** · controller `ClientController@store`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Corpo/validação** (`ClientRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `name` | `string \| required \| min:5 \| max:100` |
| `mother` | `nullable \| string \| max:100` |
| `father` | `nullable \| string \| max:100` |
| `cns` | `nullable \| string \| max:15` |
| `cpf` | `'required', 'string', 'max:18', new ValidCpf()` |
| `email` | `nullable \| string \| email \| max:100` |
| `phone` | `new PhoneValidation` |
| `obs` | `nullable \| string \| max:500` |
| `born_date` | `required \| date` |
| `sexo` | `'nullable', Rule::in(['MASCULINE', 'FEMININE', 'INDETERMINATE'])` |
| `raca_cor` | `nullable \| string \| max:30` |
| `data_obito` | `nullable \| date` |
| `st_falecido` | `nullable \| boolean` |
| `active` | `nullable \| boolean` |
| `addresses.zip_code` | `required \| max:10` |
| `addresses.city` | `required \| max:30` |
| `addresses.street` | `required \| max:100` |
| `addresses.number` | `required \| max:6` |
| `addresses.district` | `required \| max:100` |
| `addresses.complement` | `nullable \| max:50` |


## `GET /api/clients/buscar-cpf-cns`

**Buscar Por Cpf Cns** · controller `ClientController@buscarPorCpfCns`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Outros parâmetros lidos pelo controller:** `q`

## `DELETE /api/clients/duplicates`

**Destroy Duplicate Candidates** · controller `ClientController@destroyDuplicateCandidates`

- **Acesso:** Token Sanctum; somente administrador
- **Limite de requisições:** 60 por minuto
- **Corpo/validação** (`DeleteDuplicateClientsRequest`):

| Campo | Regras |
|---|---|
| `ids` | `required \| array \| min:1` |
| `ids.*` | `integer \| distinct \| min:1` |


## `GET /api/clients/duplicates`

**Duplicate Candidates** · controller `ClientController@duplicateCandidates`

- **Acesso:** Token Sanctum; somente administrador
- **Limite de requisições:** 60 por minuto
- **Outros parâmetros lidos pelo controller:** `type`

## `GET /api/clients/select`

**Select** · controller `ClientController@select`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Corpo/validação** (`ListClientsRequest`):

| Campo | Regras |
|---|---|
| `search` | `nullable \| string \| max:120` |
| `somente_nome` | `nullable \| boolean` |
| `per_page` | `nullable \| integer \| min:1 \| max:100` |
| `limit` | `nullable \| integer \| min:1 \| max:50` |


## `DELETE /api/clients/{client}`

**Remover** · controller `ClientController@destroy`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `client`

## `GET /api/clients/{client}`

**Detalhar** · controller `ClientController@show`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `client`

## `PATCH /api/clients/{client}`

**Atualizar** · controller `ClientController@update`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `client`
- **Corpo/validação** (`ClientRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `name` | `string \| required \| min:5 \| max:100` |
| `mother` | `nullable \| string \| max:100` |
| `father` | `nullable \| string \| max:100` |
| `cns` | `nullable \| string \| max:15` |
| `cpf` | `'required', 'string', 'max:18', new ValidCpf()` |
| `email` | `nullable \| string \| email \| max:100` |
| `phone` | `new PhoneValidation` |
| `obs` | `nullable \| string \| max:500` |
| `born_date` | `required \| date` |
| `sexo` | `'nullable', Rule::in(['MASCULINE', 'FEMININE', 'INDETERMINATE'])` |
| `raca_cor` | `nullable \| string \| max:30` |
| `data_obito` | `nullable \| date` |
| `st_falecido` | `nullable \| boolean` |
| `active` | `nullable \| boolean` |
| `addresses.zip_code` | `required \| max:10` |
| `addresses.city` | `required \| max:30` |
| `addresses.street` | `required \| max:100` |
| `addresses.number` | `required \| max:6` |
| `addresses.district` | `required \| max:100` |
| `addresses.complement` | `nullable \| max:50` |


## `PUT /api/clients/{client}`

**Atualizar** · controller `ClientController@update`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `client`
- **Corpo/validação** (`ClientRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `name` | `string \| required \| min:5 \| max:100` |
| `mother` | `nullable \| string \| max:100` |
| `father` | `nullable \| string \| max:100` |
| `cns` | `nullable \| string \| max:15` |
| `cpf` | `'required', 'string', 'max:18', new ValidCpf()` |
| `email` | `nullable \| string \| email \| max:100` |
| `phone` | `new PhoneValidation` |
| `obs` | `nullable \| string \| max:500` |
| `born_date` | `required \| date` |
| `sexo` | `'nullable', Rule::in(['MASCULINE', 'FEMININE', 'INDETERMINATE'])` |
| `raca_cor` | `nullable \| string \| max:30` |
| `data_obito` | `nullable \| date` |
| `st_falecido` | `nullable \| boolean` |
| `active` | `nullable \| boolean` |
| `addresses.zip_code` | `required \| max:10` |
| `addresses.city` | `required \| max:30` |
| `addresses.street` | `required \| max:100` |
| `addresses.number` | `required \| max:6` |
| `addresses.district` | `required \| max:100` |
| `addresses.complement` | `nullable \| max:50` |


## `GET /api/clients/{client}/historico`

**Listar** · controller `ClientHistoryController@index`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `client`

## `DELETE /api/clients/{client}/photo`

**Destroy Client** · controller `PessoaFotoController@destroyClient`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `client`

## `GET /api/clients/{client}/photo`

**Show Client** · controller `PessoaFotoController@showClient`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `client`

## `POST /api/clients/{client}/photo`

**Store Client** · controller `PessoaFotoController@storeClient`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `client`
- **Corpo/validação** (`StorePessoaFotoRequest`):

| Campo | Regras |
|---|---|
| `photo` | `required \| file \| image \| mimes:jpg,jpeg,png,webp \| max:4096` |


## `POST /api/conformidade-cidadao/analisar`

**Analisar** · controller `ConformidadeCidadaoController@analisar`

- **Acesso:** Token Sanctum; página em Perfis: `/conformidade-cidadao`; somente secretaria saude

## `POST /api/conformidade-cidadao/aplicar/{job_id}`

**Aplicar** · controller `ConformidadeCidadaoController@aplicar`

- **Acesso:** Token Sanctum; página em Perfis: `/conformidade-cidadao`; somente secretaria saude
- **Parâmetros de rota:** `job_id`

## `POST /api/conformidade-cidadao/cancelar/{job_id}`

**Cancelar** · controller `ConformidadeCidadaoController@cancelar`

- **Acesso:** Token Sanctum; página em Perfis: `/conformidade-cidadao`; somente secretaria saude
- **Parâmetros de rota:** `job_id`

## `GET /api/conformidade-cidadao/erros/{job_id}`

**Erros** · controller `ConformidadeCidadaoController@erros`

- **Acesso:** Token Sanctum; página em Perfis: `/conformidade-cidadao`; somente secretaria saude
- **Parâmetros de rota:** `job_id`
- **Outros parâmetros lidos pelo controller:** `per_page`, `page`

## `GET /api/conformidade-cidadao/historico`

**Historico** · controller `ConformidadeCidadaoController@historico`

- **Acesso:** Token Sanctum; página em Perfis: `/conformidade-cidadao`; somente secretaria saude
- **Outros parâmetros lidos pelo controller:** `per_page`

## `GET /api/conformidade-cidadao/itens/{job_id}`

**Itens** · controller `ConformidadeCidadaoController@itens`

- **Acesso:** Token Sanctum; página em Perfis: `/conformidade-cidadao`; somente secretaria saude
- **Parâmetros de rota:** `job_id`
- **Outros parâmetros lidos pelo controller:** `per_page`, `page`

## `GET /api/conformidade-cidadao/status/{job_id}`

**Status** · controller `ConformidadeCidadaoController@status`

- **Acesso:** Token Sanctum; página em Perfis: `/conformidade-cidadao`; somente secretaria saude
- **Parâmetros de rota:** `job_id`
- **Outros parâmetros lidos pelo controller:** `per_page`, `page`

## `GET /api/detailed-client-report`

**Detailed Client Report** · controller `ClientController@detailedClientReport`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Outros parâmetros lidos pelo controller:** `value`

## `GET /api/states`

**Listar** · controller `StateController@index`

- **Acesso:** Token Sanctum

