# Iluminação pública e ordens de serviço

Cadastro de postes com fotos e desfecho, e ordens de serviço com técnico, encaminhamento e PDF.

> Gerado a partir das rotas reais da aplicação (`php artisan route:list`). Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs.

| Método | URL | Ação |
|---|---|---|
| `GET` | `/api/iluminacao/postes` | Listar |
| `POST` | `/api/iluminacao/postes` | Criar |
| `DELETE` | `/api/iluminacao/postes/{poste}` | Remover |
| `GET` | `/api/iluminacao/postes/{poste}` | Detalhar |
| `PUT` | `/api/iluminacao/postes/{poste}` | Atualizar |
| `POST` | `/api/iluminacao/postes/{poste}/desfecho` | Criar |
| `POST` | `/api/iluminacao/postes/{poste}/fotos` | Criar |
| `DELETE` | `/api/iluminacao/postes/{poste}/fotos/{foto}` | Remover |
| `GET` | `/api/iluminacao/postes/{poste}/fotos/{foto}/download` | Download |
| `GET` | `/api/iluminacao/postes/{poste}/historico` | Historico |
| `POST` | `/api/iluminacao/postes/{poste}/reabrir` | Reabrir |
| `GET` | `/api/ordens-servico` | Listar |
| `POST` | `/api/ordens-servico` | Criar |
| `GET` | `/api/ordens-servico/secretarias` | Secretarias |
| `GET` | `/api/ordens-servico/tecnicos` | Tecnicos |
| `GET` | `/api/ordens-servico/{id}` | Detalhar |
| `POST` | `/api/ordens-servico/{id}/atribuir` | Atribuir |
| `POST` | `/api/ordens-servico/{id}/cancelar` | Cancelar |
| `POST` | `/api/ordens-servico/{id}/concluir` | Concluir |
| `POST` | `/api/ordens-servico/{id}/encaminhar` | Encaminhar |
| `GET` | `/api/ordens-servico/{id}/fotos/{fotoId}` | Baixar foto |
| `POST` | `/api/ordens-servico/{id}/iniciar` | Iniciar |
| `GET` | `/api/ordens-servico/{id}/pdf` | Baixar PDF |
| `GET` | `/api/os-configuracao` | Ler configuração |
| `PUT` | `/api/os-configuracao` | Salvar configuração |
| `GET` | `/api/os-tipos` | Listar |
| `POST` | `/api/os-tipos` | Criar |
| `PUT` | `/api/os-tipos/{id}` | Atualizar |

---

## `GET /api/iluminacao/postes`

**Listar** · controller `IluminacaoPosteController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/iluminacao/cadastro` ou `/iluminacao/servico`
- **Corpo/validação** (`ListIluminacaoPostesRequest`):

| Campo | Regras |
|---|---|
| `status` | `nullable \| in:pendentes,concluidos,todos` |
| `de` | `nullable \| date_format:Y-m-d` |
| `ate` | `nullable \| date_format:Y-m-d \| after_or_equal:de` |
| `busca` | `nullable \| string \| max:100` |
| `per_page` | `nullable \| integer \| min:1 \| max:100` |


## `POST /api/iluminacao/postes`

**Criar** · controller `IluminacaoPosteController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/iluminacao/cadastro` ou `/iluminacao/servico`; página em Perfis: `/iluminacao/cadastro`
- **Limite de requisições:** 60 por minuto
- **Corpo/validação** (`StoreIluminacaoPosteRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |
| `client_uuid` | `nullable \| uuid` |
| `ponto_referencia` | `required \| string \| min:3 \| max:160` |
| `logradouro` | `required \| string \| max:160` |
| `numero` | `nullable \| string \| max:20` |
| `bairro` | `required \| string \| max:100` |
| `latitude` | `required \| numeric \| between:-90,90` |
| `longitude` | `required \| numeric \| between:-180,180` |
| `precisao_m` | `nullable \| numeric \| min:0 \| max:100000` |
| `fotos` | `sometimes \| array \| max:4` |
| `fotos.*` | `file \| image \| mimes:jpg,jpeg,png,webp \| max:8192` |


## `DELETE /api/iluminacao/postes/{poste}`

**Remover** · controller `IluminacaoPosteController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/iluminacao/cadastro` ou `/iluminacao/servico`
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `poste`

## `GET /api/iluminacao/postes/{poste}`

**Detalhar** · controller `IluminacaoPosteController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/iluminacao/cadastro` ou `/iluminacao/servico`
- **Parâmetros de rota:** `poste`

## `PUT /api/iluminacao/postes/{poste}`

**Atualizar** · controller `IluminacaoPosteController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/iluminacao/cadastro` ou `/iluminacao/servico`; página em Perfis: `/iluminacao/cadastro`
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `poste`
- **Corpo/validação** (`UpdateIluminacaoPosteRequest`):

| Campo | Regras |
|---|---|
| `ponto_referencia` | `sometimes \| required \| string \| min:3 \| max:160` |
| `logradouro` | `sometimes \| required \| string \| max:160` |
| `numero` | `sometimes \| nullable \| string \| max:20` |
| `bairro` | `sometimes \| required \| string \| max:100` |
| `latitude` | `sometimes \| required \| numeric \| between:-90,90` |
| `longitude` | `sometimes \| required \| numeric \| between:-180,180` |
| `precisao_m` | `sometimes \| nullable \| numeric \| min:0 \| max:100000` |


## `POST /api/iluminacao/postes/{poste}/desfecho`

**Criar** · controller `IluminacaoDesfechoController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/iluminacao/cadastro` ou `/iluminacao/servico`; página em Perfis: `/iluminacao/servico`
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `poste`
- **Corpo/validação** (`StoreIluminacaoDesfechoRequest`):

| Campo | Regras |
|---|---|
| `resultado` | `required \| in:concluido,nao_concluido,aguardando` |
| `motivo` | `required_if:resultado,nao_concluido,aguardando \| nullable \| string \| min:3 \| max:1000` |
| `fotos` | `sometimes \| array \| max:4` |
| `fotos.*` | `file \| image \| mimes:jpg,jpeg,png,webp \| max:8192` |


## `POST /api/iluminacao/postes/{poste}/fotos`

**Criar** · controller `IluminacaoPosteFotoController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/iluminacao/cadastro` ou `/iluminacao/servico`; página em Perfis: `/iluminacao/cadastro`
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `poste`
- **Corpo/validação** (`StoreIluminacaoPosteFotoRequest`):

| Campo | Regras |
|---|---|
| `fotos` | `required \| array \| min:1 \| max:4` |
| `fotos.*` | `file \| image \| mimes:jpg,jpeg,png,webp \| max:8192` |


## `DELETE /api/iluminacao/postes/{poste}/fotos/{foto}`

**Remover** · controller `IluminacaoPosteFotoController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/iluminacao/cadastro` ou `/iluminacao/servico`; página em Perfis: `/iluminacao/cadastro`
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `poste`, `foto`

## `GET /api/iluminacao/postes/{poste}/fotos/{foto}/download`

**Download** · controller `IluminacaoPosteFotoController@download`

- **Acesso:** Token Sanctum; página em Perfis: `/iluminacao/cadastro` ou `/iluminacao/servico`
- **Parâmetros de rota:** `poste`, `foto`

## `GET /api/iluminacao/postes/{poste}/historico`

**Historico** · controller `IluminacaoPosteController@historico`

- **Acesso:** Token Sanctum; página em Perfis: `/iluminacao/cadastro` ou `/iluminacao/servico`
- **Parâmetros de rota:** `poste`

## `POST /api/iluminacao/postes/{poste}/reabrir`

**Reabrir** · controller `IluminacaoDesfechoController@reabrir`

- **Acesso:** Token Sanctum; página em Perfis: `/iluminacao/cadastro` ou `/iluminacao/servico`
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `poste`
- **Corpo/validação** (`ReabrirIluminacaoPosteRequest`):

| Campo | Regras |
|---|---|
| `motivo` | `required \| string \| min:3 \| max:1000` |


## `GET /api/ordens-servico`

**Listar** · controller `OrdemServicoController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico`
- **Outros parâmetros lidos pelo controller:** `status`, `q`

## `POST /api/ordens-servico`

**Criar** · controller `OrdemServicoController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico`
- **Limite de requisições:** 60 por minuto
- **Corpo/validação** (`StoreOrdemServicoRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `array_filter(['required', 'integer', Rule::exists('secretarias', 'id')->where('ativa', true)` |
| `tipo_servico_id` | `'required', 'integer', Rule::exists('os_tipos_servico', 'id')->where('secretaria_id', $secretaria)->where('ativo', true)` |
| `tecnico_id` | `'nullable', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('active', true)->where(fn ($p) => $p->where('profile', 'tecnico')->orWhereIn('id', \App\Models\User::idsComPerfilAdicional('tecnico'))))` |
| `local` | `required \| string \| max:160` |
| `endereco` | `nullable \| string \| max:255` |
| `descricao` | `required \| string \| max:5000` |
| `problema` | `nullable \| string \| max:5000` |
| `prioridade` | `'nullable', Rule::in(OrdemServico::PRIORIDADES)` |
| `prazo` | `nullable \| date \| after_or_equal:today` |
| `latitude` | `nullable \| numeric \| between:-90,90` |
| `longitude` | `nullable \| numeric \| between:-180,180` |
| `contato_nome` | `nullable \| string \| max:120` |
| `contato_telefone` | `nullable \| string \| max:30` |
| `fotos` | `nullable \| array \| max:6` |
| `fotos.*` | `file \| image \| mimes:jpg,jpeg,png,webp \| max:8192` |


## `GET /api/ordens-servico/secretarias`

**Secretarias** · controller `OrdemServicoController@secretarias`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico`

## `GET /api/ordens-servico/tecnicos`

**Tecnicos** · controller `OrdemServicoController@tecnicos`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico`
- **Outros parâmetros lidos pelo controller:** `secretaria_id`

## `GET /api/ordens-servico/{id}`

**Detalhar** · controller `OrdemServicoController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico`
- **Parâmetros de rota:** `id`

## `POST /api/ordens-servico/{id}/atribuir`

**Atribuir** · controller `OrdemServicoController@atribuir`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico`
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`AtribuirOrdemServicoRequest`):

| Campo | Regras |
|---|---|
| `tecnico_id` | `required \| integer \| <Exists>` |


## `POST /api/ordens-servico/{id}/cancelar`

**Cancelar** · controller `OrdemServicoController@cancelar`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico`
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`ObservacaoOrdemServicoRequest`):

| Campo | Regras |
|---|---|
| `observacao` | `nullable \| string \| max:1000` |


## `POST /api/ordens-servico/{id}/concluir`

**Concluir** · controller `OrdemServicoController@concluir`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico`
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`ConcluirOrdemServicoRequest`):

| Campo | Regras |
|---|---|
| `relato` | `required \| string \| max:5000` |
| `materiais` | `nullable \| string \| max:3000` |
| `tempo_gasto_min` | `nullable \| integer \| min:0 \| max:100000` |
| `fotos` | `nullable \| array \| max:6` |
| `fotos.*` | `file \| image \| mimes:jpg,jpeg,png,webp \| max:8192` |


## `POST /api/ordens-servico/{id}/encaminhar`

**Encaminhar** · controller `OrdemServicoController@encaminhar`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico`
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`EncaminharOrdemServicoRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `'required', 'integer', Rule::exists('secretarias', 'id')->where('ativa', true)` |
| `tipo_servico_id` | `'required', 'integer', Rule::exists('os_tipos_servico', 'id')->where('secretaria_id', $secretaria)->where('ativo', true)` |
| `tecnico_id` | `'nullable', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('active', true)->where(fn ($p) => $p->where('profile', 'tecnico')->orWhereIn('id', \App\Models\User::idsComPerfilAdicional('tecnico'))))` |
| `observacao` | `nullable \| string \| max:500` |


## `GET /api/ordens-servico/{id}/fotos/{fotoId}`

**Baixar foto** · controller `OrdemServicoController@foto`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico`
- **Parâmetros de rota:** `id`, `fotoId`

## `POST /api/ordens-servico/{id}/iniciar`

**Iniciar** · controller `OrdemServicoController@iniciar`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico`
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`ObservacaoOrdemServicoRequest`):

| Campo | Regras |
|---|---|
| `observacao` | `nullable \| string \| max:1000` |


## `GET /api/ordens-servico/{id}/pdf`

**Baixar PDF** · controller `OrdemServicoController@pdf`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico`
- **Limite de requisições:** 20 por minuto
- **Parâmetros de rota:** `id`

## `GET /api/os-configuracao`

**Ler configuração** · controller `OsTipoServicoController@configuracao`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico/configuracao`

## `PUT /api/os-configuracao`

**Salvar configuração** · controller `OsTipoServicoController@salvarConfiguracao`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico/configuracao`
- **Limite de requisições:** 60 por minuto
- **Corpo/validação** (`OsConfiguracaoRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable \| integer \| exists:secretarias,id` |
| `solicitante_aponta_tecnico` | `required \| boolean` |


## `GET /api/os-tipos`

**Listar** · controller `OsTipoServicoController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico` ou `/ordem-servico/configuracao`
- **Outros parâmetros lidos pelo controller:** `secretaria_id`, `todos`

## `POST /api/os-tipos`

**Criar** · controller `OsTipoServicoController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico/configuracao`
- **Limite de requisições:** 60 por minuto
- **Corpo/validação** (`OsTipoServicoRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `$criando ? 'required' : 'prohibited', 'integer', Rule::exists('secretarias', 'id')` |
| `nome` | `required \| string \| max:120` |
| `ativo` | `sometimes \| boolean` |


## `PUT /api/os-tipos/{id}`

**Atualizar** · controller `OsTipoServicoController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/ordem-servico/configuracao`
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`OsTipoServicoRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `$criando ? 'required' : 'prohibited', 'integer', Rule::exists('secretarias', 'id')` |
| `nome` | `required \| string \| max:120` |
| `ativo` | `sometimes \| boolean` |


