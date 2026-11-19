# Usuários, perfis e secretarias

Cadastro de usuários, perfis de acesso (páginas e permissões), secretarias, setores, especialidades e a aprovação de pedidos de cadastro.

> Gerado a partir das rotas reais da aplicação (`php artisan route:list`). Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs.

| Método | URL | Ação |
|---|---|---|
| `GET` | `/api/access-profiles` | Listar |
| `POST` | `/api/access-profiles` | Criar |
| `GET` | `/api/access-profiles/{accessProfile}/speciality-permissions` | Detalhar |
| `PUT` | `/api/access-profiles/{accessProfile}/speciality-permissions` | Atualizar |
| `DELETE` | `/api/access-profiles/{access_profile}` | Remover |
| `GET` | `/api/access-profiles/{access_profile}` | Detalhar |
| `PATCH` | `/api/access-profiles/{access_profile}` | Atualizar |
| `PUT` | `/api/access-profiles/{access_profile}` | Atualizar |
| `GET` | `/api/cadastro-solicitacoes/{id}` | Detalhar |
| `POST` | `/api/cadastro-solicitacoes/{id}/autorizar` | Autorizar |
| `POST` | `/api/cadastro-solicitacoes/{id}/recusar` | Recusar |
| `GET` | `/api/page-categories` | Listar |
| `POST` | `/api/page-categories` | Criar |
| `DELETE` | `/api/page-categories/{id}` | Remover |
| `PUT` | `/api/page-categories/{id}` | Atualizar |
| `GET` | `/api/secretarias-admin` | Listar |
| `POST` | `/api/secretarias-admin` | Criar |
| `DELETE` | `/api/secretarias-admin/{id}` | Remover |
| `PUT` | `/api/secretarias-admin/{id}` | Atualizar |
| `POST` | `/api/sector` | Insert |
| `PUT` | `/api/sector` | Atualizar |
| `DELETE` | `/api/sector/{id}` | Delete |
| `GET` | `/api/sector/{id}` | Edit |
| `GET` | `/api/sectors` | Get All |
| `GET` | `/api/specialities` | Listar |
| `POST` | `/api/specialities` | Criar |
| `DELETE` | `/api/specialities/{speciality}` | Remover |
| `GET` | `/api/specialities/{speciality}` | Detalhar |
| `PATCH` | `/api/specialities/{speciality}` | Atualizar |
| `PUT` | `/api/specialities/{speciality}` | Atualizar |
| `GET` | `/api/system-pages` | Listar |
| `POST` | `/api/system-pages` | Criar |
| `DELETE` | `/api/system-pages/{id}` | Remover |
| `PUT` | `/api/system-pages/{id}` | Atualizar |
| `GET` | `/api/users` | Listar |
| `POST` | `/api/users` | Criar |
| `POST` | `/api/users/presence/ping` | Presence |
| `DELETE` | `/api/users/{user}` | Remover |
| `GET` | `/api/users/{user}` | Detalhar |
| `PATCH` | `/api/users/{user}` | Atualizar |
| `PUT` | `/api/users/{user}` | Atualizar |
| `GET` | `/api/users/{user}/equipe-aps` | Detalhar |
| `PUT` | `/api/users/{user}/equipe-aps` | Atualizar |
| `DELETE` | `/api/users/{user}/photo` | Destroy User |
| `GET` | `/api/users/{user}/photo` | Show User |
| `POST` | `/api/users/{user}/photo` | Store User |

---

## `GET /api/access-profiles`

**Listar** · controller `AccessProfileController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/users`

## `POST /api/access-profiles`

**Criar** · controller `AccessProfileController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/users`
- **Corpo/validação** (`StoreAccessProfileRequest`):

| Campo | Regras |
|---|---|
| `nome` | `required \| string \| max:60 \| unique:access_profiles,nome` |
| `slug` | `required \| string \| max:60 \| unique:access_profiles,slug \| alpha_dash` |
| `descricao` | `nullable \| string \| max:200` |
| `chat_enabled` | `nullable \| boolean` |
| `almoxarifado_create_enabled` | `nullable \| boolean` |
| `almoxarifado_approve_enabled` | `nullable \| boolean` |
| `almoxarifado_deliver_enabled` | `nullable \| boolean` |
| `client_trips_view_enabled` | `nullable \| boolean` |
| `client_report_view_enabled` | `nullable \| boolean` |
| `client_history_view_enabled` | `nullable \| boolean` |
| `os_encaminhar_enabled` | `nullable \| boolean` |
| `protocol_allowed_types` | `nullable \| array` |
| `protocol_allowed_types.*` | `string \| exists:protocol_types,codigo` |
| `protocol_allowed_statuses` | `nullable \| array` |
| `protocol_allowed_statuses.*` | `string \| <In>` |
| `protocol_allowed_visibilities` | `nullable \| array` |
| `protocol_allowed_visibilities.*` | `string \| in:public,private` |
| `protocol_can_change_visibility` | `nullable \| boolean` |
| `page_ids` | `nullable \| array` |
| `page_ids.*` | `integer \| exists:system_pages,id` |


## `GET /api/access-profiles/{accessProfile}/speciality-permissions`

**Detalhar** · controller `AccessProfileSpecialityPermissionController@show`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `accessProfile`

## `PUT /api/access-profiles/{accessProfile}/speciality-permissions`

**Atualizar** · controller `AccessProfileSpecialityPermissionController@update`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `accessProfile`
- **Corpo/validação** (`UpdateProfileSpecialityPermissionRequest`):

| Campo | Regras |
|---|---|
| `permissions` | `present \| array` |
| `permissions.*.speciality_id` | `required \| integer \| distinct \| <Exists>` |
| `permissions.*.can_view` | `boolean` |
| `permissions.*.can_edit` | `boolean` |
| `permissions.*.can_insert` | `boolean` |


## `DELETE /api/access-profiles/{access_profile}`

**Remover** · controller `AccessProfileController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/users`
- **Parâmetros de rota:** `access_profile`

## `GET /api/access-profiles/{access_profile}`

**Detalhar** · controller `AccessProfileController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/users`
- **Parâmetros de rota:** `access_profile`

## `PATCH /api/access-profiles/{access_profile}`

**Atualizar** · controller `AccessProfileController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/users`
- **Parâmetros de rota:** `access_profile`
- **Corpo/validação** (`UpdateAccessProfileRequest`):

| Campo | Regras |
|---|---|
| `nome` | `sometimes \| string \| max:60 \| unique:access_profiles,nome,` |
| `slug` | `sometimes \| string \| max:60 \| unique:access_profiles,slug, \| alpha_dash` |
| `descricao` | `nullable \| string \| max:200` |
| `ativo` | `sometimes \| boolean` |
| `chat_enabled` | `sometimes \| boolean` |
| `almoxarifado_create_enabled` | `sometimes \| boolean` |
| `almoxarifado_approve_enabled` | `sometimes \| boolean` |
| `almoxarifado_deliver_enabled` | `sometimes \| boolean` |
| `client_trips_view_enabled` | `sometimes \| boolean` |
| `client_report_view_enabled` | `sometimes \| boolean` |
| `client_history_view_enabled` | `sometimes \| boolean` |
| `os_encaminhar_enabled` | `sometimes \| boolean` |
| `protocol_allowed_types` | `nullable \| array` |
| `protocol_allowed_types.*` | `string \| exists:protocol_types,codigo` |
| `protocol_allowed_statuses` | `nullable \| array` |
| `protocol_allowed_statuses.*` | `string \| <In>` |
| `protocol_allowed_visibilities` | `nullable \| array` |
| `protocol_allowed_visibilities.*` | `string \| in:public,private` |
| `protocol_can_change_visibility` | `sometimes \| boolean` |
| `page_ids` | `nullable \| array` |
| `page_ids.*` | `integer \| exists:system_pages,id` |


## `PUT /api/access-profiles/{access_profile}`

**Atualizar** · controller `AccessProfileController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/users`
- **Parâmetros de rota:** `access_profile`
- **Corpo/validação** (`UpdateAccessProfileRequest`):

| Campo | Regras |
|---|---|
| `nome` | `sometimes \| string \| max:60 \| unique:access_profiles,nome,` |
| `slug` | `sometimes \| string \| max:60 \| unique:access_profiles,slug, \| alpha_dash` |
| `descricao` | `nullable \| string \| max:200` |
| `ativo` | `sometimes \| boolean` |
| `chat_enabled` | `sometimes \| boolean` |
| `almoxarifado_create_enabled` | `sometimes \| boolean` |
| `almoxarifado_approve_enabled` | `sometimes \| boolean` |
| `almoxarifado_deliver_enabled` | `sometimes \| boolean` |
| `client_trips_view_enabled` | `sometimes \| boolean` |
| `client_report_view_enabled` | `sometimes \| boolean` |
| `client_history_view_enabled` | `sometimes \| boolean` |
| `os_encaminhar_enabled` | `sometimes \| boolean` |
| `protocol_allowed_types` | `nullable \| array` |
| `protocol_allowed_types.*` | `string \| exists:protocol_types,codigo` |
| `protocol_allowed_statuses` | `nullable \| array` |
| `protocol_allowed_statuses.*` | `string \| <In>` |
| `protocol_allowed_visibilities` | `nullable \| array` |
| `protocol_allowed_visibilities.*` | `string \| in:public,private` |
| `protocol_can_change_visibility` | `sometimes \| boolean` |
| `page_ids` | `nullable \| array` |
| `page_ids.*` | `integer \| exists:system_pages,id` |


## `GET /api/cadastro-solicitacoes/{id}`

**Detalhar** · controller `CadastroSolicitacaoController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/users` ou `/protocolo*` ou `/kanban`
- **Parâmetros de rota:** `id`

## `POST /api/cadastro-solicitacoes/{id}/autorizar`

**Autorizar** · controller `CadastroSolicitacaoController@autorizar`

- **Acesso:** Token Sanctum; página em Perfis: `/users` ou `/protocolo*` ou `/kanban`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`AutorizarCadastroRequest`):

| Campo | Regras |
|---|---|
| `perfil_slug` | `required \| string \| max:50` |
| `secretaria_ids` | `required \| array \| min:1` |
| `secretaria_ids.*` | `integer \| distinct` |


## `POST /api/cadastro-solicitacoes/{id}/recusar`

**Recusar** · controller `CadastroSolicitacaoController@recusar`

- **Acesso:** Token Sanctum; página em Perfis: `/users` ou `/protocolo*` ou `/kanban`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`RecusarCadastroRequest`):

| Campo | Regras |
|---|---|
| `motivo` | `required \| string \| min:5 \| max:500` |


## `GET /api/page-categories`

**Listar** · controller `PageCategoryController@index`

- **Acesso:** Token Sanctum; somente administrador

## `POST /api/page-categories`

**Criar** · controller `PageCategoryController@store`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`StorePageCategoryRequest`):

| Campo | Regras |
|---|---|
| `nome` | `required \| string \| max:60 \| unique:page_categories,nome` |
| `icone` | `nullable \| string \| max:40` |
| `ordem` | `nullable \| integer \| min:0` |
| `ativo` | `nullable \| boolean` |


## `DELETE /api/page-categories/{id}`

**Remover** · controller `PageCategoryController@destroy`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `id`

## `PUT /api/page-categories/{id}`

**Atualizar** · controller `PageCategoryController@update`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`UpdatePageCategoryRequest`):

| Campo | Regras |
|---|---|
| `nome` | `sometimes \| string \| max:60 \| unique:page_categories,nome,` |
| `icone` | `nullable \| string \| max:40` |
| `ordem` | `nullable \| integer \| min:0` |
| `ativo` | `nullable \| boolean` |


## `GET /api/secretarias-admin`

**Listar** · controller `SecretariaAdminController@index`

- **Acesso:** Token Sanctum; somente administrador

## `POST /api/secretarias-admin`

**Criar** · controller `SecretariaAdminController@store`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`SecretariaRequest`):

| Campo | Regras |
|---|---|
| `nome` | `required \| string \| max:80 \| <Unique>` |
| `ativa` | `sometimes \| boolean` |
| `page_ids` | `sometimes \| array` |
| `page_ids.*` | `integer \| exists:system_pages,id` |


## `DELETE /api/secretarias-admin/{id}`

**Remover** · controller `SecretariaAdminController@destroy`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `id`

## `PUT /api/secretarias-admin/{id}`

**Atualizar** · controller `SecretariaAdminController@update`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`SecretariaRequest`):

| Campo | Regras |
|---|---|
| `nome` | `required \| string \| max:80 \| <Unique>` |
| `ativa` | `sometimes \| boolean` |
| `page_ids` | `sometimes \| array` |
| `page_ids.*` | `integer \| exists:system_pages,id` |


## `POST /api/sector`

**Insert** · controller `SectorController@insert`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`InsertSectorRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |

- **Outros parâmetros lidos pelo controller:** `name`

## `PUT /api/sector`

**Atualizar** · controller `SectorController@update`

- **Acesso:** Token Sanctum; somente administrador
- **Outros parâmetros lidos pelo controller:** `name`

## `DELETE /api/sector/{id}`

**Delete** · controller `SectorController@delete`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `id`

## `GET /api/sector/{id}`

**Edit** · controller `SectorController@edit`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `id`

## `GET /api/sectors`

**Get All** · controller `SectorController@getAll`

- **Acesso:** Token Sanctum; somente administrador

## `GET /api/specialities`

**Listar** · controller `SpecialityController@index`

- **Acesso:** Token Sanctum

## `POST /api/specialities`

**Criar** · controller `SpecialityController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/specialities`
- **Corpo/validação** (`StoreSpecialityRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |
| `id_user` | `required \| exists:users,id` |
| `name` | `required \| string \| max:50` |
| `allows_session_scheduling` | `sometimes \| boolean` |


## `DELETE /api/specialities/{speciality}`

**Remover** · controller `SpecialityController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/specialities`
- **Parâmetros de rota:** `speciality`

## `GET /api/specialities/{speciality}`

**Detalhar** · controller `SpecialityController@show`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `speciality`

## `PATCH /api/specialities/{speciality}`

**Atualizar** · controller `SpecialityController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/specialities`
- **Parâmetros de rota:** `speciality`
- **Corpo/validação** (`UpdateSpecialityRequest`):

| Campo | Regras |
|---|---|
| `id_user` | `required \| exists:users,id` |
| `name` | `required \| string \| max:50` |
| `allows_session_scheduling` | `sometimes \| boolean` |


## `PUT /api/specialities/{speciality}`

**Atualizar** · controller `SpecialityController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/specialities`
- **Parâmetros de rota:** `speciality`
- **Corpo/validação** (`UpdateSpecialityRequest`):

| Campo | Regras |
|---|---|
| `id_user` | `required \| exists:users,id` |
| `name` | `required \| string \| max:50` |
| `allows_session_scheduling` | `sometimes \| boolean` |


## `GET /api/system-pages`

**Listar** · controller `SystemPageController@index`

- **Acesso:** Token Sanctum; somente administrador

## `POST /api/system-pages`

**Criar** · controller `SystemPageController@store`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`StoreSystemPageRequest`):

| Campo | Regras |
|---|---|
| `titulo` | `required \| string \| max:80` |
| `path` | `required \| string \| max:120 \| unique:system_pages,path` |
| `icone` | `nullable \| string \| max:40` |
| `categoria` | `nullable \| string \| max:60` |
| `category_id` | `nullable \| integer \| exists:page_categories,id` |
| `ordem` | `nullable \| integer \| min:1` |


## `DELETE /api/system-pages/{id}`

**Remover** · controller `SystemPageController@destroy`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `id`

## `PUT /api/system-pages/{id}`

**Atualizar** · controller `SystemPageController@update`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`UpdateSystemPageRequest`):

| Campo | Regras |
|---|---|
| `titulo` | `sometimes \| string \| max:80` |
| `path` | `sometimes \| string \| max:120 \| unique:system_pages,path,` |
| `icone` | `nullable \| string \| max:40` |
| `categoria` | `nullable \| string \| max:60` |
| `category_id` | `nullable \| integer \| exists:page_categories,id` |
| `ordem` | `nullable \| integer \| min:1` |
| `ativo` | `sometimes \| boolean` |


## `GET /api/users`

**Listar** · controller `UserController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/users`

## `POST /api/users`

**Criar** · controller `UserController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/users`
- **Corpo/validação** (`UserRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `profile` | `$profileRules` |
| `name` | `required \| string \| max:50` |
| `preferred_name` | `nullable \| string \| max:50` |
| `phone` | `nullable', new PhoneValidation` |
| `email` | `'string', 'required', 'max:100', Rule::unique('users', 'email')->ignore($userId)` |
| `cpf` | `'string', 'required', 'max:18', Rule::unique('users', 'cpf')->ignore($userId), new ValidCpf()` |
| `email_verified_at` | `nullable \| date` |
| `active` | `Boolean` |
| `inactive_date` | `nullable \| date` |
| `is_rt_psf` | `nullable \| boolean` |
| `rt_all_teams` | `nullable \| boolean` |
| `chat_access_override` | `nullable \| boolean` |
| `secretaria_ids` | `$this->route('user') === null ? 'required' : 'sometimes', 'array', 'min:1'` |
| `secretaria_ids.*` | `array_values(array_filter([` |
| `extra_profiles` | `nullable \| array` |
| `extra_profiles.*` | `'string', 'distinct', Rule::in(array_values(array_diff($validSlugs, ['admin'])))` |
| `protocol_unit_ids` | `nullable \| array` |
| `protocol_unit_ids.*` | `integer \| distinct \| exists:protocol_organizational_units,id` |

- **Outros parâmetros lidos pelo controller:** `protocol_unit_ids`, `extra_profiles`, `secretaria_ids`

## `POST /api/users/presence/ping`

**Presence** · controller `UserController@presence`

- **Acesso:** Token Sanctum
- **Outros parâmetros lidos pelo controller:** `path`

## `DELETE /api/users/{user}`

**Remover** · controller `UserController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/users`
- **Parâmetros de rota:** `user`

## `GET /api/users/{user}`

**Detalhar** · controller `UserController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/users`
- **Parâmetros de rota:** `user`

## `PATCH /api/users/{user}`

**Atualizar** · controller `UserController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/users`
- **Parâmetros de rota:** `user`
- **Corpo/validação** (`UserRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `profile` | `$profileRules` |
| `name` | `required \| string \| max:50` |
| `preferred_name` | `nullable \| string \| max:50` |
| `phone` | `nullable', new PhoneValidation` |
| `email` | `'string', 'required', 'max:100', Rule::unique('users', 'email')->ignore($userId)` |
| `cpf` | `'string', 'required', 'max:18', Rule::unique('users', 'cpf')->ignore($userId), new ValidCpf()` |
| `email_verified_at` | `nullable \| date` |
| `active` | `Boolean` |
| `inactive_date` | `nullable \| date` |
| `is_rt_psf` | `nullable \| boolean` |
| `rt_all_teams` | `nullable \| boolean` |
| `chat_access_override` | `nullable \| boolean` |
| `secretaria_ids` | `$this->route('user') === null ? 'required' : 'sometimes', 'array', 'min:1'` |
| `secretaria_ids.*` | `array_values(array_filter([` |
| `extra_profiles` | `nullable \| array` |
| `extra_profiles.*` | `'string', 'distinct', Rule::in(array_values(array_diff($validSlugs, ['admin'])))` |
| `protocol_unit_ids` | `nullable \| array` |
| `protocol_unit_ids.*` | `integer \| distinct \| exists:protocol_organizational_units,id` |

- **Outros parâmetros lidos pelo controller:** `password`, `protocol_unit_ids`, `extra_profiles`, `secretaria_ids`

## `PUT /api/users/{user}`

**Atualizar** · controller `UserController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/users`
- **Parâmetros de rota:** `user`
- **Corpo/validação** (`UserRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `profile` | `$profileRules` |
| `name` | `required \| string \| max:50` |
| `preferred_name` | `nullable \| string \| max:50` |
| `phone` | `nullable', new PhoneValidation` |
| `email` | `'string', 'required', 'max:100', Rule::unique('users', 'email')->ignore($userId)` |
| `cpf` | `'string', 'required', 'max:18', Rule::unique('users', 'cpf')->ignore($userId), new ValidCpf()` |
| `email_verified_at` | `nullable \| date` |
| `active` | `Boolean` |
| `inactive_date` | `nullable \| date` |
| `is_rt_psf` | `nullable \| boolean` |
| `rt_all_teams` | `nullable \| boolean` |
| `chat_access_override` | `nullable \| boolean` |
| `secretaria_ids` | `$this->route('user') === null ? 'required' : 'sometimes', 'array', 'min:1'` |
| `secretaria_ids.*` | `array_values(array_filter([` |
| `extra_profiles` | `nullable \| array` |
| `extra_profiles.*` | `'string', 'distinct', Rule::in(array_values(array_diff($validSlugs, ['admin'])))` |
| `protocol_unit_ids` | `nullable \| array` |
| `protocol_unit_ids.*` | `integer \| distinct \| exists:protocol_organizational_units,id` |

- **Outros parâmetros lidos pelo controller:** `password`, `protocol_unit_ids`, `extra_profiles`, `secretaria_ids`

## `GET /api/users/{user}/equipe-aps`

**Detalhar** · controller `UserEquipeApsController@show`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `user`

## `PUT /api/users/{user}/equipe-aps`

**Atualizar** · controller `UserEquipeApsController@update`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `user`
- **Corpo/validação** (`UpdateUserEquipeApsRequest`):

| Campo | Regras |
|---|---|
| `is_rt_psf` | `required \| boolean` |
| `rt_all_teams` | `required \| boolean` |
| `equipes` | `nullable \| array` |
| `equipes.*.nu_ine` | `required_with:equipes \| string \| max:10` |
| `equipes.*.no_equipe` | `required_with:equipes \| string \| max:100` |


## `DELETE /api/users/{user}/photo`

**Destroy User** · controller `PessoaFotoController@destroyUser`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `user`

## `GET /api/users/{user}/photo`

**Show User** · controller `PessoaFotoController@showUser`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `user`

## `POST /api/users/{user}/photo`

**Store User** · controller `PessoaFotoController@storeUser`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `user`
- **Corpo/validação** (`StorePessoaFotoRequest`):

| Campo | Regras |
|---|---|
| `photo` | `required \| file \| image \| mimes:jpg,jpeg,png,webp \| max:4096` |


