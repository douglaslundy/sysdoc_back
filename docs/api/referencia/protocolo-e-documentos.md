# Protocolo, documentos e kanban

Protocolo com tramitação, documentos com versões e aprovação, ofícios e portarias (com IA) e quadro kanban.

> Gerado a partir das rotas reais da aplicação (`php artisan route:list`). Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs.

| Método | URL | Ação |
|---|---|---|
| `GET` | `/api/documentos` | Listar |
| `POST` | `/api/documentos` | Criar |
| `GET` | `/api/documentos/aprovacoes` | Listar |
| `POST` | `/api/documentos/aprovacoes/{id}/aprovar` | Approve |
| `POST` | `/api/documentos/aprovacoes/{id}/rejeitar` | Reject |
| `GET` | `/api/documentos/configuracoes` | Detalhar |
| `PUT` | `/api/documentos/configuracoes` | Atualizar |
| `GET` | `/api/documentos/tipos` | Listar |
| `POST` | `/api/documentos/tipos` | Criar |
| `DELETE` | `/api/documentos/tipos/{id}` | Remover |
| `PUT` | `/api/documentos/tipos/{id}` | Atualizar |
| `DELETE` | `/api/documentos/{documentId}/versoes/{versionId}` | Destroy Version |
| `GET` | `/api/documentos/{documentId}/versoes/{versionId}/download` | Download Version |
| `DELETE` | `/api/documentos/{id}` | Remover |
| `GET` | `/api/documentos/{id}` | Detalhar |
| `PUT` | `/api/documentos/{id}` | Atualizar |
| `GET` | `/api/documentos/{id}/historico` | History |
| `GET` | `/api/documentos/{id}/versoes` | Versions |
| `POST` | `/api/documentos/{id}/versoes` | Upload Version |
| `GET` | `/api/kanban` | Listar |
| `POST` | `/api/kanban` | Criar |
| `DELETE` | `/api/kanban/{id}` | Remover |
| `PUT` | `/api/kanban/{id}` | Atualizar |
| `GET` | `/api/letters` | Listar |
| `POST` | `/api/letters` | Criar |
| `POST` | `/api/letters/newLetter` | Create Letter Ai |
| `DELETE` | `/api/letters/{letter}` | Remover |
| `GET` | `/api/letters/{letter}` | Detalhar |
| `PATCH` | `/api/letters/{letter}` | Atualizar |
| `PUT` | `/api/letters/{letter}` | Atualizar |
| `GET` | `/api/letters/{letter}/attachments` | Listar |
| `POST` | `/api/letters/{letter}/attachments` | Criar |
| `DELETE` | `/api/letters/{letter}/attachments/{attachment}` | Remover |
| `GET` | `/api/letters/{letter}/attachments/{attachment}/download` | Download |
| `POST` | `/api/letters/{letter}/protocol` | Create Protocol V2 |
| `GET` | `/api/models` | Listar |
| `POST` | `/api/models` | Criar |
| `DELETE` | `/api/models/{model}` | Remover |
| `GET` | `/api/models/{model}` | Detalhar |
| `PATCH` | `/api/models/{model}` | Atualizar |
| `PUT` | `/api/models/{model}` | Atualizar |
| `GET` | `/api/ordinances` | Listar |
| `POST` | `/api/ordinances` | Criar |
| `POST` | `/api/ordinances/newOrdinance` | Create Ordinance Ai |
| `DELETE` | `/api/ordinances/{ordinance}` | Remover |
| `GET` | `/api/ordinances/{ordinance}` | Detalhar |
| `PATCH` | `/api/ordinances/{ordinance}` | Atualizar |
| `PUT` | `/api/ordinances/{ordinance}` | Atualizar |
| `GET` | `/api/ordinances/{ordinance}/attachments` | Listar |
| `POST` | `/api/ordinances/{ordinance}/attachments` | Criar |
| `DELETE` | `/api/ordinances/{ordinance}/attachments/{attachment}` | Remover |
| `GET` | `/api/ordinances/{ordinance}/attachments/{attachment}/download` | Download |
| `GET` | `/api/protocolos` | Listar |
| `POST` | `/api/protocolos` | Criar |
| `GET` | `/api/protocolos/alertas` | Listar |
| `POST` | `/api/protocolos/alertas` | Criar |
| `DELETE` | `/api/protocolos/alertas/{id}` | Remover |
| `PUT` | `/api/protocolos/alertas/{id}` | Atualizar |
| `GET` | `/api/protocolos/anexos/{attachment}/download` | Download Attachment |
| `GET` | `/api/protocolos/caixa-entrada` | Inbox |
| `GET` | `/api/protocolos/configuracoes` | Detalhar |
| `PUT` | `/api/protocolos/configuracoes` | Atualizar |
| `GET` | `/api/protocolos/contadores` | Counts |
| `GET` | `/api/protocolos/contexto-novo` | Creation Context |
| `GET` | `/api/protocolos/tipos` | Listar |
| `POST` | `/api/protocolos/tipos` | Criar |
| `DELETE` | `/api/protocolos/tipos/{id}` | Remover |
| `PUT` | `/api/protocolos/tipos/{id}` | Atualizar |
| `GET` | `/api/protocolos/unidades-organizacionais` | Listar |
| `POST` | `/api/protocolos/unidades-organizacionais` | Criar |
| `DELETE` | `/api/protocolos/unidades-organizacionais/{id}` | Remover |
| `PUT` | `/api/protocolos/unidades-organizacionais/{id}` | Atualizar |
| `GET` | `/api/protocolos/usuarios-elegiveis` | Eligible Destination Users |
| `GET` | `/api/protocolos/{id}` | Detalhar |
| `PUT` | `/api/protocolos/{id}` | Atualizar |
| `POST` | `/api/protocolos/{id}/anexos` | Attach |
| `POST` | `/api/protocolos/{id}/comentarios` | Comment |
| `POST` | `/api/protocolos/{id}/devolver` | Return To Sender |
| `POST` | `/api/protocolos/{id}/encaminhar` | Forward |
| `POST` | `/api/protocolos/{id}/encerrar` | Close |
| `GET` | `/api/protocolos/{id}/historico` | Historico |
| `POST` | `/api/protocolos/{id}/kanban-status` | Move From Kanban |
| `POST` | `/api/protocolos/{id}/reabrir` | Reopen |
| `POST` | `/api/protocolos/{id}/receber` | Receive |
| `GET` | `/api/protocolos/{id}/visualizacoes` | Visualizations |

---

## `GET /api/documentos`

**Listar** · controller `DocumentController@index`

- **Acesso:** Token Sanctum
- **Outros parâmetros lidos pelo controller:** `search`, `per_page`

## `POST /api/documentos`

**Criar** · controller `DocumentController@store`

- **Acesso:** Token Sanctum
- **Corpo/validação** (`StoreDocumentRequest`):

| Campo | Regras |
|---|---|
| `document_type_id` | `nullable \| integer \| exists:document_types,id` |
| `titulo` | `required \| string \| max:200` |
| `resumo` | `nullable \| string` |
| `sigilo` | `required \| string \| <In>` |
| `status` | `nullable \| string \| max:30` |
| `arquivo` | `required \| file \| max:51200` |
| `secretaria_id` | `nullable \| integer \| <Exists>` |


## `GET /api/documentos/aprovacoes`

**Listar** · controller `DocumentApprovalController@index`

- **Acesso:** Token Sanctum
- **Outros parâmetros lidos pelo controller:** `document_id`, `per_page`

## `POST /api/documentos/aprovacoes/{id}/aprovar`

**Approve** · controller `DocumentApprovalController@approve`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `id`

## `POST /api/documentos/aprovacoes/{id}/rejeitar`

**Reject** · controller `DocumentApprovalController@reject`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `id`

## `GET /api/documentos/configuracoes`

**Detalhar** · controller `DocumentConfigController@show`

- **Acesso:** Token Sanctum

## `PUT /api/documentos/configuracoes`

**Atualizar** · controller `DocumentConfigController@update`

- **Acesso:** Token Sanctum
- **Corpo/validação** (`UpdateDocumentConfigRequest`):

| Campo | Regras |
|---|---|
| `triple_signature_enabled` | `nullable \| boolean` |
| `triple_signature_sigilos` | `nullable \| array` |
| `triple_signature_sigilos.*` | `string \| distinct \| in:publico,interno,restrito` |
| `signer_user_1_id` | `nullable \| integer \| distinct \| exists:users,id` |
| `signer_user_2_id` | `nullable \| integer \| distinct \| exists:users,id` |
| `signer_user_3_id` | `nullable \| integer \| distinct \| exists:users,id` |


## `GET /api/documentos/tipos`

**Listar** · controller `DocumentTypeController@index`

- **Acesso:** Token Sanctum

## `POST /api/documentos/tipos`

**Criar** · controller `DocumentTypeController@store`

- **Acesso:** Token Sanctum
- **Corpo/validação** (`DocumentTypeRequest`):

| Campo | Regras |
|---|---|
| `codigo` | `required \| string \| max:60 \| unique:document_types,codigo` |
| `nome` | `required \| string \| max:150` |
| `descricao` | `nullable \| string` |
| `ordem` | `nullable \| integer \| min:0 \| max:9999` |
| `ativo` | `nullable \| boolean` |


## `DELETE /api/documentos/tipos/{id}`

**Remover** · controller `DocumentTypeController@destroy`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `id`

## `PUT /api/documentos/tipos/{id}`

**Atualizar** · controller `DocumentTypeController@update`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`DocumentTypeRequest`):

| Campo | Regras |
|---|---|
| `codigo` | `required \| string \| max:60 \| unique:document_types,codigo` |
| `nome` | `required \| string \| max:150` |
| `descricao` | `nullable \| string` |
| `ordem` | `nullable \| integer \| min:0 \| max:9999` |
| `ativo` | `nullable \| boolean` |


## `DELETE /api/documentos/{documentId}/versoes/{versionId}`

**Destroy Version** · controller `DocumentController@destroyVersion`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `documentId`, `versionId`

## `GET /api/documentos/{documentId}/versoes/{versionId}/download`

**Download Version** · controller `DocumentController@downloadVersion`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `documentId`, `versionId`

## `DELETE /api/documentos/{id}`

**Remover** · controller `DocumentController@destroy`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `id`

## `GET /api/documentos/{id}`

**Detalhar** · controller `DocumentController@show`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `id`

## `PUT /api/documentos/{id}`

**Atualizar** · controller `DocumentController@update`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`UpdateDocumentRequest`):

| Campo | Regras |
|---|---|
| `document_type_id` | `nullable \| integer \| exists:document_types,id` |
| `titulo` | `sometimes \| required \| string \| max:200` |
| `resumo` | `nullable \| string` |
| `sigilo` | `sometimes \| required \| string \| <In>` |
| `status` | `nullable \| string \| max:30` |
| `arquivo` | `nullable \| file \| max:51200` |


## `GET /api/documentos/{id}/historico`

**History** · controller `DocumentController@history`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `id`

## `GET /api/documentos/{id}/versoes`

**Versions** · controller `DocumentController@versions`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `id`

## `POST /api/documentos/{id}/versoes`

**Upload Version** · controller `DocumentController@uploadVersion`

- **Acesso:** Token Sanctum
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`UploadDocumentVersionRequest`):

| Campo | Regras |
|---|---|
| `arquivo` | `required \| file \| max:51200` |


## `GET /api/kanban`

**Listar** · controller `KanbanController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/kanban`
- **Outros parâmetros lidos pelo controller:** `pending_only`, `search`

## `POST /api/kanban`

**Criar** · controller `KanbanController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/kanban`
- **Corpo/validação** (`StoreKanbanRequest`):

| Campo | Regras |
|---|---|
| `titulo` | `required \| string \| max:200` |
| `descricao` | `nullable \| string` |
| `status` | `nullable \| string \| max:40` |
| `prioridade` | `nullable \| string \| max:20` |
| `vencimento` | `nullable \| date` |
| `secretaria_id` | `nullable \| integer \| <Exists>` |
| `responsavel_id` | `nullable \| integer \| exists:users,id` |
| `visibility` | `nullable \| string \| in:public,private` |
| `ordem` | `nullable \| integer \| min:0` |


## `DELETE /api/kanban/{id}`

**Remover** · controller `KanbanController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/kanban`
- **Parâmetros de rota:** `id`

## `PUT /api/kanban/{id}`

**Atualizar** · controller `KanbanController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/kanban`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`UpdateKanbanTaskRequest`):

| Campo | Regras |
|---|---|
| `titulo` | `sometimes \| required \| string \| max:200` |
| `descricao` | `nullable \| string` |
| `status` | `sometimes \| required \| string \| max:40` |
| `prioridade` | `sometimes \| required \| string \| max:20` |
| `vencimento` | `nullable \| date` |
| `responsavel_id` | `nullable \| integer \| exists:users,id` |
| `visibility` | `nullable \| string \| in:public,private` |
| `ordem` | `nullable \| integer \| min:0` |


## `GET /api/letters`

**Listar** · controller `LetterController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/letters`

## `POST /api/letters`

**Criar** · controller `LetterController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/letters`
- **Outros parâmetros lidos pelo controller:** `secretaria_id`, `id_user`, `subject_matter`, `sender`, `recipient`, `obs`, `summary`, `fileurl`

## `POST /api/letters/newLetter`

**Create Letter Ai** · controller `LetterController@createLetterAi`

- **Acesso:** Token Sanctum; página em Perfis: `/letters`
- **Corpo/validação** (`CreateLetterAiRequest`):

| Campo | Regras |
|---|---|
| `id_user` | `required` |
| `sender` | `required \| string` |
| `recipient` | `required \| string` |
| `subject_matter` | `required \| string` |
| `summary` | `nullable \| string` |
| `wishes` | `nullable \| string` |
| `treatment_pronoun` | `nullable \| string` |


## `DELETE /api/letters/{letter}`

**Remover** · controller `LetterController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/letters`
- **Parâmetros de rota:** `letter`

## `GET /api/letters/{letter}`

**Detalhar** · controller `LetterController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/letters`
- **Parâmetros de rota:** `letter`

## `PATCH /api/letters/{letter}`

**Atualizar** · controller `LetterController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/letters`
- **Parâmetros de rota:** `letter`
- **Outros parâmetros lidos pelo controller:** `id`, `subject_matter`, `sender`, `recipient`, `obs`, `summary`, `fileurl`

## `PUT /api/letters/{letter}`

**Atualizar** · controller `LetterController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/letters`
- **Parâmetros de rota:** `letter`
- **Outros parâmetros lidos pelo controller:** `id`, `subject_matter`, `sender`, `recipient`, `obs`, `summary`, `fileurl`

## `GET /api/letters/{letter}/attachments`

**Listar** · controller `LetterAttachmentController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/letters`
- **Parâmetros de rota:** `letter`

## `POST /api/letters/{letter}/attachments`

**Criar** · controller `LetterAttachmentController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/letters`
- **Parâmetros de rota:** `letter`
- **Corpo/validação** (`StoreLetterAttachmentRequest`):

| Campo | Regras |
|---|---|
| `file` | `nullable \| file \| mimes:pdf,jpg,jpeg,png \| max:30720 \| required_without:files` |
| `files` | `nullable \| array \| required_without:file` |
| `files.*` | `file \| mimes:pdf,jpg,jpeg,png \| max:30720` |


## `DELETE /api/letters/{letter}/attachments/{attachment}`

**Remover** · controller `LetterAttachmentController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/letters`
- **Parâmetros de rota:** `letter`, `attachment`

## `GET /api/letters/{letter}/attachments/{attachment}/download`

**Download** · controller `LetterAttachmentController@download`

- **Acesso:** Token Sanctum; página em Perfis: `/letters`
- **Parâmetros de rota:** `letter`, `attachment`

## `POST /api/letters/{letter}/protocol`

**Create Protocol V2** · controller `LetterController@createProtocolV2`

- **Acesso:** Token Sanctum; página em Perfis: `/letters`
- **Parâmetros de rota:** `letter`
- **Corpo/validação** (`CreateProtocolV2LetterRequest`):

| Campo | Regras |
|---|---|
| `destino_unit_id` | `required \| integer \| exists:protocol_organizational_units,id` |
| `destino_user_id` | `nullable \| integer \| exists:users,id` |


## `GET /api/models`

**Listar** · controller `ModelController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/models`

## `POST /api/models`

**Criar** · controller `ModelController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/models`

## `DELETE /api/models/{model}`

**Remover** · controller `ModelController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/models`
- **Parâmetros de rota:** `model`

## `GET /api/models/{model}`

**Detalhar** · controller `ModelController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/models`
- **Parâmetros de rota:** `model`

## `PATCH /api/models/{model}`

**Atualizar** · controller `ModelController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/models`
- **Parâmetros de rota:** `model`

## `PUT /api/models/{model}`

**Atualizar** · controller `ModelController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/models`
- **Parâmetros de rota:** `model`

## `GET /api/ordinances`

**Listar** · controller `OrdinanceController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/ordinance`

## `POST /api/ordinances`

**Criar** · controller `OrdinanceController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/ordinance`
- **Corpo/validação** (`StoreOrdinanceRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable \| integer \| <Exists>` |
| `user_id` | `required \| integer \| exists:users,id` |
| `type` | `required \| <In>` |
| `title` | `required \| string \| max:255` |
| `subject` | `required \| string \| max:255` |
| `summary` | `nullable \| string` |
| `content` | `nullable \| string` |
| `legal_basis` | `nullable \| string` |
| `signatory_name` | `required \| string \| max:150` |
| `signatory_role` | `nullable \| string \| max:150` |
| `file` | `nullable \| file \| mimes:pdf,doc,docx \| max:30720` |
| `notes` | `nullable \| string` |


## `POST /api/ordinances/newOrdinance`

**Create Ordinance Ai** · controller `OrdinanceController@createOrdinanceAi`

- **Acesso:** Token Sanctum; página em Perfis: `/ordinance`
- **Corpo/validação** (`CreateOrdinanceAiOrdinanceRequest`):

| Campo | Regras |
|---|---|
| `user_id` | `required \| integer \| exists:users,id` |
| `type` | `required \| in:normativa,ordinatoria` |
| `title` | `required \| string \| max:255` |
| `subject` | `required \| string \| max:255` |
| `summary` | `required \| string` |
| `legal_basis` | `nullable \| string` |
| `signatory_name` | `required \| string \| max:150` |
| `signatory_role` | `nullable \| string \| max:150` |
| `additional_instructions` | `nullable \| string` |


## `DELETE /api/ordinances/{ordinance}`

**Remover** · controller `OrdinanceController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/ordinance`
- **Parâmetros de rota:** `ordinance`

## `GET /api/ordinances/{ordinance}`

**Detalhar** · controller `OrdinanceController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/ordinance`
- **Parâmetros de rota:** `ordinance`

## `PATCH /api/ordinances/{ordinance}`

**Atualizar** · controller `OrdinanceController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/ordinance`
- **Parâmetros de rota:** `ordinance`
- **Corpo/validação** (`UpdateOrdinanceRequest`):

| Campo | Regras |
|---|---|
| `type` | `required \| <In>` |
| `title` | `required \| string \| max:255` |
| `subject` | `required \| string \| max:255` |
| `summary` | `nullable \| string` |
| `content` | `nullable \| string` |
| `legal_basis` | `nullable \| string` |
| `signatory_name` | `required \| string \| max:150` |
| `signatory_role` | `nullable \| string \| max:150` |
| `file` | `nullable \| file \| mimes:pdf,doc,docx \| max:30720` |
| `notes` | `nullable \| string` |


## `PUT /api/ordinances/{ordinance}`

**Atualizar** · controller `OrdinanceController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/ordinance`
- **Parâmetros de rota:** `ordinance`
- **Corpo/validação** (`UpdateOrdinanceRequest`):

| Campo | Regras |
|---|---|
| `type` | `required \| <In>` |
| `title` | `required \| string \| max:255` |
| `subject` | `required \| string \| max:255` |
| `summary` | `nullable \| string` |
| `content` | `nullable \| string` |
| `legal_basis` | `nullable \| string` |
| `signatory_name` | `required \| string \| max:150` |
| `signatory_role` | `nullable \| string \| max:150` |
| `file` | `nullable \| file \| mimes:pdf,doc,docx \| max:30720` |
| `notes` | `nullable \| string` |


## `GET /api/ordinances/{ordinance}/attachments`

**Listar** · controller `OrdinanceAttachmentController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/ordinance`
- **Parâmetros de rota:** `ordinance`

## `POST /api/ordinances/{ordinance}/attachments`

**Criar** · controller `OrdinanceAttachmentController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/ordinance`
- **Parâmetros de rota:** `ordinance`
- **Corpo/validação** (`StoreOrdinanceAttachmentRequest`):

| Campo | Regras |
|---|---|
| `file` | `nullable \| file \| mimes:pdf,jpg,jpeg,png \| max:30720 \| required_without:files` |
| `files` | `nullable \| array \| required_without:file` |
| `files.*` | `file \| mimes:pdf,jpg,jpeg,png \| max:30720` |


## `DELETE /api/ordinances/{ordinance}/attachments/{attachment}`

**Remover** · controller `OrdinanceAttachmentController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/ordinance`
- **Parâmetros de rota:** `ordinance`, `attachment`

## `GET /api/ordinances/{ordinance}/attachments/{attachment}/download`

**Download** · controller `OrdinanceAttachmentController@download`

- **Acesso:** Token Sanctum; página em Perfis: `/ordinance`
- **Parâmetros de rota:** `ordinance`, `attachment`

## `GET /api/protocolos`

**Listar** · controller `ProtocolController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`
- **Outros parâmetros lidos pelo controller:** `search`, `responsavel_atual_id`, `origem_unit_id`, `destino_unit_id`, `vencimento`, `per_page`

## `POST /api/protocolos`

**Criar** · controller `ProtocolController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`
- **Corpo/validação** (`StoreProtocolRequest`):

| Campo | Regras |
|---|---|
| `status` | `nullable \| string \| <In>` |
| `visibility` | `nullable \| string \| in:public,private` |
| `assunto` | `required \| string \| max:200` |
| `descricao` | `nullable \| string` |
| `secretaria_id` | `nullable \| integer \| <Exists>` |
| `tipo` | `required \| string \| max:40 \| exists:protocol_types,codigo \| not_in:solicitacao_cadastro` |
| `prioridade` | `nullable \| string \| max:20` |
| `origem_unit_id` | `nullable \| integer \| exists:protocol_organizational_units,id` |
| `destino_unit_id` | `nullable \| integer \| exists:protocol_organizational_units,id` |
| `destino_user_id` | `nullable \| integer \| exists:users,id` |
| `prazo_dias` | `nullable \| integer \| min:1 \| max:365` |
| `kanban` | `nullable \| array` |
| `kanban.ativar` | `nullable \| boolean` |
| `kanban.id` | `nullable \| integer \| exists:kanban_tasks,id` |
| `kanban.titulo` | `nullable \| string \| max:200` |
| `kanban.descricao` | `nullable \| string` |
| `kanban.status` | `nullable \| string \| max:40` |
| `kanban.prioridade` | `nullable \| string \| max:20` |
| `kanban.vencimento` | `nullable \| date` |
| `kanban.responsavel_id` | `nullable \| integer \| exists:users,id` |
| `kanban.ordem` | `nullable \| integer \| min:0` |


## `GET /api/protocolos/alertas`

**Listar** · controller `ProtocolAlertController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`; página em Perfis: `/protocolo/alertas,/sistema/alertas`

## `POST /api/protocolos/alertas`

**Criar** · controller `ProtocolAlertController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`; página em Perfis: `/protocolo/alertas,/sistema/alertas`
- **Corpo/validação** (`StoreProtocolAlertRequest`):

| Campo | Regras |
|---|---|
| `nome` | `required \| string \| max:150` |
| `descricao` | `nullable \| string` |
| `modulo` | `required \| string \| max:80` |
| `gatilho` | `required \| string \| max:80` |
| `condicoes` | `nullable \| array` |
| `condicoes.*` | `string \| <In>` |
| `canais` | `required \| array \| min:1` |
| `canais.*` | `string \| <In>` |
| `destinatarios` | `nullable \| array` |
| `destinatarios.*` | `string \| <In>` |
| `template` | `nullable \| string` |
| `ativo` | `nullable \| boolean` |
| `frequencia` | `nullable \| string \| max:60` |
| `prevenir_duplicidade` | `nullable \| boolean` |


## `DELETE /api/protocolos/alertas/{id}`

**Remover** · controller `ProtocolAlertController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`; página em Perfis: `/protocolo/alertas,/sistema/alertas`
- **Parâmetros de rota:** `id`

## `PUT /api/protocolos/alertas/{id}`

**Atualizar** · controller `ProtocolAlertController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`; página em Perfis: `/protocolo/alertas,/sistema/alertas`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`UpdateProtocolAlertRequest`):

| Campo | Regras |
|---|---|
| `nome` | `sometimes \| required \| string \| max:150` |
| `descricao` | `nullable \| string` |
| `modulo` | `sometimes \| required \| string \| max:80` |
| `gatilho` | `sometimes \| required \| string \| max:80` |
| `condicoes` | `nullable \| array` |
| `condicoes.*` | `string \| <In>` |
| `canais` | `required \| array \| min:1` |
| `canais.*` | `string \| <In>` |
| `destinatarios` | `nullable \| array` |
| `destinatarios.*` | `string \| <In>` |
| `template` | `nullable \| string` |
| `ativo` | `nullable \| boolean` |
| `frequencia` | `nullable \| string \| max:60` |
| `prevenir_duplicidade` | `nullable \| boolean` |


## `GET /api/protocolos/anexos/{attachment}/download`

**Download Attachment** · controller `ProtocolController@downloadAttachment`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`
- **Parâmetros de rota:** `attachment`

## `GET /api/protocolos/caixa-entrada`

**Inbox** · controller `ProtocolController@inbox`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`
- **Outros parâmetros lidos pelo controller:** `status`, `situacao`, `prioridade`, `search`, `per_page`

## `GET /api/protocolos/configuracoes`

**Detalhar** · controller `ProtocolConfigController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`; página em Perfis: `/protocolo/configuracoes`

## `PUT /api/protocolos/configuracoes`

**Atualizar** · controller `ProtocolConfigController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`; página em Perfis: `/protocolo/configuracoes`
- **Corpo/validação** (`UpdateProtocolConfigRequest`):

| Campo | Regras |
|---|---|
| `allow_external_protocols` | `boolean` |
| `allow_reopen` | `boolean` |
| `notify_whatsapp` | `boolean` |
| `default_priority` | `nullable \| string \| max:20` |
| `default_due_days` | `nullable \| integer \| min:1 \| max:365` |
| `observacoes` | `nullable \| string` |


## `GET /api/protocolos/contadores`

**Counts** · controller `ProtocolController@counts`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`

## `GET /api/protocolos/contexto-novo`

**Creation Context** · controller `ProtocolController@creationContext`

- **Acesso:** Token Sanctum

## `GET /api/protocolos/tipos`

**Listar** · controller `ProtocolTypeController@index`

- **Acesso:** Token Sanctum

## `POST /api/protocolos/tipos`

**Criar** · controller `ProtocolTypeController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo/tipos`
- **Corpo/validação** (`StoreProtocolTypeRequest`):

| Campo | Regras |
|---|---|
| `codigo` | `required \| string \| max:40 \| unique:protocol_types,codigo` |
| `nome` | `required \| string \| max:120` |
| `descricao` | `nullable \| string` |
| `ordem` | `nullable \| integer \| min:0 \| max:65535` |
| `ativo` | `nullable \| boolean` |


## `DELETE /api/protocolos/tipos/{id}`

**Remover** · controller `ProtocolTypeController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo/tipos`
- **Parâmetros de rota:** `id`

## `PUT /api/protocolos/tipos/{id}`

**Atualizar** · controller `ProtocolTypeController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo/tipos`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`UpdateProtocolTypeRequest`):

| Campo | Regras |
|---|---|
| `codigo` | `sometimes \| required \| string \| max:40 \| <Unique>` |
| `nome` | `sometimes \| required \| string \| max:120` |
| `descricao` | `nullable \| string` |
| `ordem` | `nullable \| integer \| min:0 \| max:65535` |
| `ativo` | `nullable \| boolean` |


## `GET /api/protocolos/unidades-organizacionais`

**Listar** · controller `ProtocolOrganizationalUnitController@index`

- **Acesso:** Token Sanctum
- **Outros parâmetros lidos pelo controller:** `todas`

## `POST /api/protocolos/unidades-organizacionais`

**Criar** · controller `ProtocolOrganizationalUnitController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo/estrutura`
- **Corpo/validação** (`StoreProtocolOrganizationalUnitRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable \| integer \| <Exists>` |
| `parent_id` | `nullable \| integer \| exists:protocol_organizational_units,id` |
| `tipo` | `required \| string \| max:40` |
| `codigo` | `nullable \| string \| max:60` |
| `nome` | `required \| string \| max:150` |
| `descricao` | `nullable \| string` |
| `ativo` | `nullable \| boolean` |


## `DELETE /api/protocolos/unidades-organizacionais/{id}`

**Remover** · controller `ProtocolOrganizationalUnitController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo/estrutura`
- **Parâmetros de rota:** `id`

## `PUT /api/protocolos/unidades-organizacionais/{id}`

**Atualizar** · controller `ProtocolOrganizationalUnitController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo/estrutura`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`UpdateProtocolOrganizationalUnitRequest`):

| Campo | Regras |
|---|---|
| `parent_id` | `nullable \| integer \| exists:protocol_organizational_units,id` |
| `tipo` | `sometimes \| string \| max:40` |
| `codigo` | `nullable \| string \| max:60` |
| `nome` | `sometimes \| required \| string \| max:150` |
| `descricao` | `nullable \| string` |
| `ativo` | `nullable \| boolean` |


## `GET /api/protocolos/usuarios-elegiveis`

**Eligible Destination Users** · controller `ProtocolController@eligibleDestinationUsers`

- **Acesso:** Token Sanctum
- **Corpo/validação** (`EligibleProtocolUsersRequest`):

| Campo | Regras |
|---|---|
| `unit_id` | `nullable \| integer \| exists:protocol_organizational_units,id` |


## `GET /api/protocolos/{id}`

**Detalhar** · controller `ProtocolController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`
- **Parâmetros de rota:** `id`
- **Outros parâmetros lidos pelo controller:** `view_session`

## `PUT /api/protocolos/{id}`

**Atualizar** · controller `ProtocolController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`UpdateProtocolRequest`):

| Campo | Regras |
|---|---|
| `assunto` | `sometimes \| required \| string \| max:200` |
| `descricao` | `nullable \| string` |
| `tipo` | `sometimes \| required \| string \| max:40 \| exists:protocol_types,codigo \| not_in:solicitacao_cadastro` |
| `prioridade` | `nullable \| string \| max:20` |
| `visibility` | `sometimes \| string \| in:public,private` |
| `status` | `sometimes \| string \| in:novo,recebido,encaminhado,devolvido,reaberto,encerrado,concluido,cancelado` |
| `origem_unit_id` | `nullable \| integer \| exists:protocol_organizational_units,id` |
| `destino_unit_id` | `nullable \| integer \| exists:protocol_organizational_units,id` |
| `destino_user_id` | `nullable \| integer \| exists:users,id` |
| `prazo_dias` | `nullable \| integer \| min:1 \| max:365` |
| `kanban` | `nullable \| array` |
| `kanban.ativar` | `nullable \| boolean` |
| `kanban.id` | `nullable \| integer \| exists:kanban_tasks,id` |
| `kanban.titulo` | `nullable \| string \| max:200` |
| `kanban.descricao` | `nullable \| string` |
| `kanban.status` | `nullable \| string \| max:40` |
| `kanban.prioridade` | `nullable \| string \| max:20` |
| `kanban.vencimento` | `nullable \| date` |
| `kanban.responsavel_id` | `nullable \| integer \| exists:users,id` |
| `kanban.ordem` | `nullable \| integer \| min:0` |


## `POST /api/protocolos/{id}/anexos`

**Attach** · controller `ProtocolController@attach`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`AttachProtocolRequest`):

| Campo | Regras |
|---|---|
| `arquivo` | `required \| file \| max:30720 \| mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx` |
| `descricao` | `nullable \| string \| max:255` |


## `POST /api/protocolos/{id}/comentarios`

**Comment** · controller `ProtocolController@comment`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`CommentProtocolRequest`):

| Campo | Regras |
|---|---|
| `conteudo` | `required \| string` |
| `privado` | `nullable \| boolean` |
| `tipo` | `nullable \| string \| max:30` |


## `POST /api/protocolos/{id}/devolver`

**Return To Sender** · controller `ProtocolController@returnToSender`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`ReturnProtocolRequest`):

| Campo | Regras |
|---|---|
| `motivo` | `required \| string \| min:5` |


## `POST /api/protocolos/{id}/encaminhar`

**Forward** · controller `ProtocolController@forward`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`ForwardProtocolRequest`):

| Campo | Regras |
|---|---|
| `destino_unit_id` | `nullable \| integer \| exists:protocol_organizational_units,id` |
| `destino_user_id` | `nullable \| integer \| exists:users,id` |
| `observacao` | `nullable \| string` |


## `POST /api/protocolos/{id}/encerrar`

**Close** · controller `ProtocolController@close`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`CloseProtocolRequest`):

| Campo | Regras |
|---|---|
| `justificativa_encerramento` | `required \| string` |


## `GET /api/protocolos/{id}/historico`

**Historico** · controller `ProtocolController@historico`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`
- **Parâmetros de rota:** `id`

## `POST /api/protocolos/{id}/kanban-status`

**Move From Kanban** · controller `ProtocolController@moveFromKanban`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`MoveProtocolKanbanRequest`):

| Campo | Regras |
|---|---|
| `kanban_status` | `required \| in:novo,em_andamento,aguardando_resposta,bloqueado,concluido` |
| `observacao` | `nullable \| string \| max:1000` |


## `POST /api/protocolos/{id}/reabrir`

**Reopen** · controller `ProtocolController@reopen`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`
- **Parâmetros de rota:** `id`

## `POST /api/protocolos/{id}/receber`

**Receive** · controller `ProtocolController@receive`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`
- **Parâmetros de rota:** `id`

## `GET /api/protocolos/{id}/visualizacoes`

**Visualizations** · controller `ProtocolController@visualizations`

- **Acesso:** Token Sanctum; página em Perfis: `/protocolo*` ou `/kanban`
- **Parâmetros de rota:** `id`

