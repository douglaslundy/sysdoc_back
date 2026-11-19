# Vigilância sanitária

Estabelecimentos, alvarás, fiscalizações com anexos e configurações da vigilância.

> Gerado a partir das rotas reais da aplicação (`php artisan route:list`). Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs.

| Método | URL | Ação |
|---|---|---|
| `GET` | `/api/alvaras` | Listar |
| `POST` | `/api/alvaras` | Criar |
| `DELETE` | `/api/alvaras/{alvara}` | Remover |
| `GET` | `/api/alvaras/{alvara}` | Detalhar |
| `PATCH` | `/api/alvaras/{alvara}` | Atualizar |
| `PUT` | `/api/alvaras/{alvara}` | Atualizar |
| `GET` | `/api/alvaras/{id}/pdf` | Download Pdf |
| `GET` | `/api/cnaes/select` | Cnaes Select |
| `GET` | `/api/estabelecimentos` | Listar |
| `POST` | `/api/estabelecimentos` | Criar |
| `GET` | `/api/estabelecimentos/select` | Select |
| `DELETE` | `/api/estabelecimentos/{estabelecimento}` | Remover |
| `GET` | `/api/estabelecimentos/{estabelecimento}` | Detalhar |
| `PATCH` | `/api/estabelecimentos/{estabelecimento}` | Atualizar |
| `PUT` | `/api/estabelecimentos/{estabelecimento}` | Atualizar |
| `GET` | `/api/fiscalizacoes` | Listar |
| `POST` | `/api/fiscalizacoes` | Criar |
| `DELETE` | `/api/fiscalizacoes/{fiscalizacao}` | Remover |
| `GET` | `/api/fiscalizacoes/{fiscalizacao}` | Detalhar |
| `PATCH` | `/api/fiscalizacoes/{fiscalizacao}` | Atualizar |
| `PUT` | `/api/fiscalizacoes/{fiscalizacao}` | Atualizar |
| `GET` | `/api/fiscalizacoes/{fiscalizacao}/attachments` | Listar |
| `POST` | `/api/fiscalizacoes/{fiscalizacao}/attachments` | Criar |
| `DELETE` | `/api/fiscalizacoes/{fiscalizacao}/attachments/{attachment}` | Remover |
| `GET` | `/api/fiscalizacoes/{fiscalizacao}/attachments/{attachment}/download` | Download |
| `GET` | `/api/fiscalizacoes/{fiscalizacao}/historico` | Listar |
| `GET` | `/api/peticao-motivos` | Listar |
| `POST` | `/api/peticao-motivos` | Criar |
| `DELETE` | `/api/peticao-motivos/{motivo}` | Remover |
| `PATCH` | `/api/peticao-motivos/{motivo}` | Atualizar |
| `PUT` | `/api/peticao-motivos/{motivo}` | Atualizar |
| `GET` | `/api/vigilancia/config` | Detalhar |
| `PUT` | `/api/vigilancia/config` | Atualizar |
| `GET` | `/api/vigilancia/contatos-whatsapp` | Listar |
| `POST` | `/api/vigilancia/contatos-whatsapp` | Criar |
| `DELETE` | `/api/vigilancia/contatos-whatsapp/{contato}` | Remover |
| `PATCH` | `/api/vigilancia/contatos-whatsapp/{contato}` | Atualizar |
| `PUT` | `/api/vigilancia/contatos-whatsapp/{contato}` | Atualizar |

---

## `GET /api/alvaras`

**Listar** · controller `AlvaraController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/alvaras` ou `/auditoria`
- **Outros parâmetros lidos pelo controller:** `busca`, `nivel_risco`, `status`, `estabelecimento_id`, `data_de`, `data_ate`, `vencimento_de`, `vencimento_ate`, `per_page`

## `POST /api/alvaras`

**Criar** · controller `AlvaraController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/alvaras`
- **Corpo/validação** (`StoreAlvaraRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()` |
| `estabelecimento_id` | `'required', 'integer', EscopoAlmoxarifado::existe('estabelecimentos', $sid)` |
| `nivel_risco` | `'required', Rule::in(['1', '2', '3', 'N/A'])` |
| `status` | `['sometimes', 'nullable', Rule::in([` |
| `data_alvara` | `required \| date` |
| `vencimento_alvara` | `nullable \| date \| after_or_equal:data_alvara` |
| `contato` | `nullable \| string \| max:1000` |

- **Outros parâmetros lidos pelo controller:** `secretaria_id`

## `DELETE /api/alvaras/{alvara}`

**Remover** · controller `AlvaraController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/alvaras`
- **Parâmetros de rota:** `alvara`

## `GET /api/alvaras/{alvara}`

**Detalhar** · controller `AlvaraController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/alvaras` ou `/auditoria`
- **Parâmetros de rota:** `alvara`

## `PATCH /api/alvaras/{alvara}`

**Atualizar** · controller `AlvaraController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/alvaras`
- **Parâmetros de rota:** `alvara`
- **Corpo/validação** (`UpdateAlvaraRequest`):

| Campo | Regras |
|---|---|
| `estabelecimento_id` | `sometimes \| required \| integer \| <Exists>` |
| `nivel_risco` | `sometimes \| required \| <In>` |
| `status` | `nullable \| <In>` |
| `data_alvara` | `sometimes \| required \| date` |
| `vencimento_alvara` | `nullable \| date \| after_or_equal:data_alvara` |
| `contato` | `nullable \| string \| max:1000` |


## `PUT /api/alvaras/{alvara}`

**Atualizar** · controller `AlvaraController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/alvaras`
- **Parâmetros de rota:** `alvara`
- **Corpo/validação** (`UpdateAlvaraRequest`):

| Campo | Regras |
|---|---|
| `estabelecimento_id` | `sometimes \| required \| integer \| <Exists>` |
| `nivel_risco` | `sometimes \| required \| <In>` |
| `status` | `nullable \| <In>` |
| `data_alvara` | `sometimes \| required \| date` |
| `vencimento_alvara` | `nullable \| date \| after_or_equal:data_alvara` |
| `contato` | `nullable \| string \| max:1000` |


## `GET /api/alvaras/{id}/pdf`

**Download Pdf** · controller `AlvaraController@downloadPdf`

- **Acesso:** Token Sanctum; página em Perfis: `/alvaras` ou `/auditoria`
- **Parâmetros de rota:** `id`

## `GET /api/cnaes/select`

**Cnaes Select** · controller `EstabelecimentoController@cnaesSelect`

- **Acesso:** Token Sanctum; página em Perfis: `/estabelecimentos` ou `/alvaras` ou `/fiscalizacoes`
- **Outros parâmetros lidos pelo controller:** `busca`

## `GET /api/estabelecimentos`

**Listar** · controller `EstabelecimentoController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/estabelecimentos` ou `/alvaras` ou `/fiscalizacoes` ou `/auditoria`
- **Outros parâmetros lidos pelo controller:** `busca`, `per_page`

## `POST /api/estabelecimentos`

**Criar** · controller `EstabelecimentoController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/estabelecimentos`
- **Corpo/validação** (`StoreEstabelecimentoRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |
| `nome_responsavel` | `required \| string \| max:255` |
| `nome_estabelecimento` | `required \| string \| max:255` |
| `razao_social` | `nullable \| string \| max:255` |
| `nome_fantasia` | `nullable \| string \| max:255` |
| `cnpj` | `nullable \| string \| max:18 \| regex:/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/` |
| `telefone` | `nullable \| string \| max:20` |
| `endereco` | `required \| string \| max:500` |
| `cnaes` | `required \| array \| min:1` |
| `cnaes.*` | `required \| string \| regex:/^\d{4}-\d\/\d{2}$/` |
| `obs` | `nullable \| string \| max:5000` |


## `GET /api/estabelecimentos/select`

**Select** · controller `EstabelecimentoController@select`

- **Acesso:** Token Sanctum; página em Perfis: `/estabelecimentos` ou `/alvaras` ou `/fiscalizacoes` ou `/auditoria`

## `DELETE /api/estabelecimentos/{estabelecimento}`

**Remover** · controller `EstabelecimentoController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/estabelecimentos`
- **Parâmetros de rota:** `estabelecimento`

## `GET /api/estabelecimentos/{estabelecimento}`

**Detalhar** · controller `EstabelecimentoController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/estabelecimentos` ou `/alvaras` ou `/fiscalizacoes` ou `/auditoria`
- **Parâmetros de rota:** `estabelecimento`

## `PATCH /api/estabelecimentos/{estabelecimento}`

**Atualizar** · controller `EstabelecimentoController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/estabelecimentos`
- **Parâmetros de rota:** `estabelecimento`
- **Corpo/validação** (`UpdateEstabelecimentoRequest`):

| Campo | Regras |
|---|---|
| `nome_responsavel` | `sometimes \| required \| string \| max:255` |
| `nome_estabelecimento` | `sometimes \| required \| string \| max:255` |
| `razao_social` | `nullable \| string \| max:255` |
| `nome_fantasia` | `nullable \| string \| max:255` |
| `cnpj` | `nullable \| string \| max:18 \| regex:/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/` |
| `telefone` | `nullable \| string \| max:20` |
| `endereco` | `sometimes \| required \| string \| max:500` |
| `cnaes` | `sometimes \| required \| array \| min:1` |
| `cnaes.*` | `required \| string \| regex:/^\d{4}-\d\/\d{2}$/` |
| `obs` | `nullable \| string \| max:5000` |


## `PUT /api/estabelecimentos/{estabelecimento}`

**Atualizar** · controller `EstabelecimentoController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/estabelecimentos`
- **Parâmetros de rota:** `estabelecimento`
- **Corpo/validação** (`UpdateEstabelecimentoRequest`):

| Campo | Regras |
|---|---|
| `nome_responsavel` | `sometimes \| required \| string \| max:255` |
| `nome_estabelecimento` | `sometimes \| required \| string \| max:255` |
| `razao_social` | `nullable \| string \| max:255` |
| `nome_fantasia` | `nullable \| string \| max:255` |
| `cnpj` | `nullable \| string \| max:18 \| regex:/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/` |
| `telefone` | `nullable \| string \| max:20` |
| `endereco` | `sometimes \| required \| string \| max:500` |
| `cnaes` | `sometimes \| required \| array \| min:1` |
| `cnaes.*` | `required \| string \| regex:/^\d{4}-\d\/\d{2}$/` |
| `obs` | `nullable \| string \| max:5000` |


## `GET /api/fiscalizacoes`

**Listar** · controller `FiscalizacaoController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/fiscalizacoes`
- **Outros parâmetros lidos pelo controller:** `estabelecimento_id`, `resultado`, `origem`, `busca`, `per_page`

## `POST /api/fiscalizacoes`

**Criar** · controller `FiscalizacaoController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/fiscalizacoes`
- **Corpo/validação** (`StoreFiscalizacaoRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()` |
| `estabelecimento_id` | `'required', 'integer', EscopoAlmoxarifado::existe('estabelecimentos', $sid)` |
| `data_visita` | `required \| date` |
| `resultado` | `'required', Rule::in(['Conforme', 'Não conforme', 'Notificação', 'Auto de infração', 'Pendente de apuração'])` |
| `observacoes` | `nullable \| string \| max:2000` |

- **Outros parâmetros lidos pelo controller:** `secretaria_id`

## `DELETE /api/fiscalizacoes/{fiscalizacao}`

**Remover** · controller `FiscalizacaoController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/fiscalizacoes`
- **Parâmetros de rota:** `fiscalizacao`

## `GET /api/fiscalizacoes/{fiscalizacao}`

**Detalhar** · controller `FiscalizacaoController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/fiscalizacoes`
- **Parâmetros de rota:** `fiscalizacao`

## `PATCH /api/fiscalizacoes/{fiscalizacao}`

**Atualizar** · controller `FiscalizacaoController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/fiscalizacoes`
- **Parâmetros de rota:** `fiscalizacao`
- **Corpo/validação** (`UpdateFiscalizacaoRequest`):

| Campo | Regras |
|---|---|
| `estabelecimento_id` | `sometimes \| required \| integer \| <Exists>` |
| `data_visita` | `sometimes \| required \| date` |
| `resultado` | `sometimes \| required \| <In>` |
| `observacoes` | `nullable \| string \| max:2000` |
| `visivel_ao_denunciante` | `sometimes \| boolean` |


## `PUT /api/fiscalizacoes/{fiscalizacao}`

**Atualizar** · controller `FiscalizacaoController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/fiscalizacoes`
- **Parâmetros de rota:** `fiscalizacao`
- **Corpo/validação** (`UpdateFiscalizacaoRequest`):

| Campo | Regras |
|---|---|
| `estabelecimento_id` | `sometimes \| required \| integer \| <Exists>` |
| `data_visita` | `sometimes \| required \| date` |
| `resultado` | `sometimes \| required \| <In>` |
| `observacoes` | `nullable \| string \| max:2000` |
| `visivel_ao_denunciante` | `sometimes \| boolean` |


## `GET /api/fiscalizacoes/{fiscalizacao}/attachments`

**Listar** · controller `FiscalizacaoAttachmentController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/fiscalizacoes`
- **Parâmetros de rota:** `fiscalizacao`

## `POST /api/fiscalizacoes/{fiscalizacao}/attachments`

**Criar** · controller `FiscalizacaoAttachmentController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/fiscalizacoes`
- **Parâmetros de rota:** `fiscalizacao`
- **Corpo/validação** (`StoreFiscalizacaoAttachmentRequest`):

| Campo | Regras |
|---|---|
| `file` | `nullable \| file \| mimes:pdf,jpg,jpeg,png \| max:30720 \| required_without:files` |
| `files` | `nullable \| array \| required_without:file` |
| `files.*` | `file \| mimes:pdf,jpg,jpeg,png \| max:30720` |


## `DELETE /api/fiscalizacoes/{fiscalizacao}/attachments/{attachment}`

**Remover** · controller `FiscalizacaoAttachmentController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/fiscalizacoes`
- **Parâmetros de rota:** `fiscalizacao`, `attachment`

## `GET /api/fiscalizacoes/{fiscalizacao}/attachments/{attachment}/download`

**Download** · controller `FiscalizacaoAttachmentController@download`

- **Acesso:** Token Sanctum; página em Perfis: `/fiscalizacoes`
- **Parâmetros de rota:** `fiscalizacao`, `attachment`

## `GET /api/fiscalizacoes/{fiscalizacao}/historico`

**Listar** · controller `FiscalizacaoHistoricoController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/fiscalizacoes`
- **Parâmetros de rota:** `fiscalizacao`

## `GET /api/peticao-motivos`

**Listar** · controller `PeticaoMotivoController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/peticao-motivos`

## `POST /api/peticao-motivos`

**Criar** · controller `PeticaoMotivoController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/peticao-motivos`
- **Corpo/validação** (`PeticaoMotivoRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `nome` | `'required', 'string', 'max:120', EscopoAlmoxarifado::unico('peticao_motivos', 'nome', $sid)->ignore($ignore)` |
| `descricao` | `nullable \| string \| max:255` |
| `unit_id` | `'nullable', 'integer', EscopoAlmoxarifado::existe('protocol_organizational_units', $sid)` |
| `ativo` | `sometimes \| boolean` |
| `ordem` | `sometimes \| integer \| min:0 \| max:65535` |

- **Outros parâmetros lidos pelo controller:** `secretaria_id`

## `DELETE /api/peticao-motivos/{motivo}`

**Remover** · controller `PeticaoMotivoController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/peticao-motivos`
- **Parâmetros de rota:** `motivo`

## `PATCH /api/peticao-motivos/{motivo}`

**Atualizar** · controller `PeticaoMotivoController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/peticao-motivos`
- **Parâmetros de rota:** `motivo`
- **Corpo/validação** (`PeticaoMotivoRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `nome` | `'required', 'string', 'max:120', EscopoAlmoxarifado::unico('peticao_motivos', 'nome', $sid)->ignore($ignore)` |
| `descricao` | `nullable \| string \| max:255` |
| `unit_id` | `'nullable', 'integer', EscopoAlmoxarifado::existe('protocol_organizational_units', $sid)` |
| `ativo` | `sometimes \| boolean` |
| `ordem` | `sometimes \| integer \| min:0 \| max:65535` |


## `PUT /api/peticao-motivos/{motivo}`

**Atualizar** · controller `PeticaoMotivoController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/peticao-motivos`
- **Parâmetros de rota:** `motivo`
- **Corpo/validação** (`PeticaoMotivoRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `nome` | `'required', 'string', 'max:120', EscopoAlmoxarifado::unico('peticao_motivos', 'nome', $sid)->ignore($ignore)` |
| `descricao` | `nullable \| string \| max:255` |
| `unit_id` | `'nullable', 'integer', EscopoAlmoxarifado::existe('protocol_organizational_units', $sid)` |
| `ativo` | `sometimes \| boolean` |
| `ordem` | `sometimes \| integer \| min:0 \| max:65535` |


## `GET /api/vigilancia/config`

**Detalhar** · controller `VigilanciaConfigController@show`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`ShowVigilanciaConfigRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |


## `PUT /api/vigilancia/config`

**Atualizar** · controller `VigilanciaConfigController@update`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`UpdateVigilanciaConfigRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |
| `estado` | `nullable \| string \| max:2` |
| `nome_municipio` | `nullable \| string \| max:255` |
| `nome_prefeitura` | `nullable \| string \| max:255` |
| `cnpj_prefeitura` | `nullable \| string \| max:14` |
| `nome_secretaria` | `nullable \| string \| max:255` |
| `cnpj_secretaria` | `nullable \| string \| max:14` |
| `divisao` | `nullable \| string \| max:255` |
| `endereco` | `nullable \| string \| max:255` |
| `cep` | `nullable \| string \| max:8` |
| `telefone` | `nullable \| string \| max:20` |
| `email` | `nullable \| email \| max:255` |
| `nome_responsavel` | `nullable \| string \| max:255` |
| `cargo_responsavel` | `nullable \| string \| max:255` |
| `grant_type` | `nullable \| string \| max:255` |
| `observacoes` | `nullable \| array` |
| `observacoes.*` | `string \| max:500` |


## `GET /api/vigilancia/contatos-whatsapp`

**Listar** · controller `VigilanciaContatoWhatsappController@index`

- **Acesso:** Token Sanctum; somente administrador

## `POST /api/vigilancia/contatos-whatsapp`

**Criar** · controller `VigilanciaContatoWhatsappController@store`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`VigilanciaContatoWhatsappRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `nome` | `required \| string \| max:150` |
| `telefone` | `required \| string \| regex:/^\d{10,13}$/` |
| `ativo` | `sometimes \| boolean` |

- **Outros parâmetros lidos pelo controller:** `secretaria_id`, `ativo`

## `DELETE /api/vigilancia/contatos-whatsapp/{contato}`

**Remover** · controller `VigilanciaContatoWhatsappController@destroy`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `contato`

## `PATCH /api/vigilancia/contatos-whatsapp/{contato}`

**Atualizar** · controller `VigilanciaContatoWhatsappController@update`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `contato`
- **Corpo/validação** (`VigilanciaContatoWhatsappRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `nome` | `required \| string \| max:150` |
| `telefone` | `required \| string \| regex:/^\d{10,13}$/` |
| `ativo` | `sometimes \| boolean` |


## `PUT /api/vigilancia/contatos-whatsapp/{contato}`

**Atualizar** · controller `VigilanciaContatoWhatsappController@update`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `contato`
- **Corpo/validação** (`VigilanciaContatoWhatsappRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `nome` | `required \| string \| max:150` |
| `telefone` | `required \| string \| regex:/^\d{10,13}$/` |
| `ativo` | `sometimes \| boolean` |


