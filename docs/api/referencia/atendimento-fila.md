# Atendimento e fila

Senhas, salas, chamadas, painel de atendimento, fila por especialidade e agendamento por sessões.

> Gerado a partir das rotas reais da aplicação (`php artisan route:list`). Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs.

| Método | URL | Ação |
|---|---|---|
| `GET` | `/api/attendance/attendants` | Attendants |
| `GET` | `/api/attendance/panel/state` | Panel State |
| `GET` | `/api/attendance/queue` | Queue |
| `POST` | `/api/attendance/queue/call-next` | Call Next |
| `POST` | `/api/attendance/queue/{ticketId}/call` | Call Specific |
| `GET` | `/api/attendance/rooms` | Rooms |
| `GET` | `/api/attendance/rooms-admin` | Rooms Index |
| `POST` | `/api/attendance/rooms-admin` | Rooms Store |
| `DELETE` | `/api/attendance/rooms-admin/{id}` | Rooms Destroy |
| `GET` | `/api/attendance/rooms-admin/{id}` | Rooms Show |
| `PUT` | `/api/attendance/rooms-admin/{id}` | Rooms Update |
| `PATCH` | `/api/attendance/rooms-admin/{id}/inactivate` | Rooms Inactivate |
| `GET` | `/api/attendance/service/{ticketId}` | Service Data |
| `POST` | `/api/attendance/service/{ticketId}/finish` | Service Finish |
| `PATCH` | `/api/attendance/service/{ticketId}/notes` | Service Notes |
| `POST` | `/api/attendance/service/{ticketId}/start` | Service Start |
| `GET` | `/api/attendance/tickets` | List Tickets |
| `POST` | `/api/attendance/tickets` | Create Ticket |
| `GET` | `/api/attendance/tickets/{id}` | Show Ticket |
| `PATCH` | `/api/attendance/tickets/{id}/cancel` | Cancel Ticket |
| `PATCH` | `/api/attendance/tickets/{id}/no-show` | No Show Ticket |
| `GET` | `/api/queue-treatment-plans` | Listar |
| `POST` | `/api/queue-treatment-plans` | Criar |
| `POST` | `/api/queue-treatment-plans/preview` | Preview |
| `GET` | `/api/queue-treatment-plans/{plan}` | Detalhar |
| `PUT` | `/api/queue-treatment-plans/{plan}/cancel` | Cancel |
| `PUT` | `/api/queue-treatment-sessions/{session}/complete` | Complete |
| `PUT` | `/api/queue-treatment-sessions/{session}/reschedule` | Reschedule |
| `GET` | `/api/queues` | Listar |
| `POST` | `/api/queues` | Criar |
| `POST` | `/api/queues/log-location` | Store Location Log |
| `GET` | `/api/queues/specialities-options` | Speciality Options |
| `GET` | `/api/queues/{queueId}/treatment-plan` | For Queue |
| `DELETE` | `/api/queues/{queue}` | Remover |
| `GET` | `/api/queues/{queue}` | Detalhar |
| `PATCH` | `/api/queues/{queue}` | Atualizar |
| `PUT` | `/api/queues/{queue}` | Atualizar |
| `GET` | `/api/queues/{queue}/attachments` | Listar |
| `POST` | `/api/queues/{queue}/attachments` | Criar |
| `DELETE` | `/api/queues/{queue}/attachments/{attachment}` | Remover |
| `GET` | `/api/queues/{queue}/attachments/{attachment}/download` | Download |

---

## `GET /api/attendance/attendants`

**Attendants** · controller `AttendanceController@attendants`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`

## `GET /api/attendance/panel/state`

**Panel State** · controller `AttendanceController@panelState`

- **Acesso:** Pública (sem login)

## `GET /api/attendance/queue`

**Queue** · controller `AttendanceController@queue`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Corpo/validação** (`QueueAttendanceRequest`):

| Campo | Regras |
|---|---|
| `roomId` | `nullable \| integer \| <Exists>` |


## `POST /api/attendance/queue/call-next`

**Call Next** · controller `AttendanceController@callNext`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Corpo/validação** (`CallNextAttendanceRequest`):

| Campo | Regras |
|---|---|
| `roomId` | `required \| integer \| <Exists>` |


## `POST /api/attendance/queue/{ticketId}/call`

**Call Specific** · controller `AttendanceController@callSpecific`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Parâmetros de rota:** `ticketId`
- **Corpo/validação** (`CallSpecificAttendanceRequest`):

| Campo | Regras |
|---|---|
| `roomId` | `required \| integer \| <Exists>` |


## `GET /api/attendance/rooms`

**Rooms** · controller `AttendanceController@rooms`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`

## `GET /api/attendance/rooms-admin`

**Rooms Index** · controller `AttendanceController@roomsIndex`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`

## `POST /api/attendance/rooms-admin`

**Rooms Store** · controller `AttendanceController@roomsStore`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Corpo/validação** (`RoomsStoreAttendanceRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()` |
| `name` | `'required', 'string', 'max:100', EscopoAlmoxarifado::unico('attendance_rooms', 'name', app(ActiveSecretaria::class)->forCreate($this->input('secretaria_id')) ?? 0)` |
| `description` | `nullable \| string \| max:255` |
| `active` | `nullable \| boolean` |


## `DELETE /api/attendance/rooms-admin/{id}`

**Rooms Destroy** · controller `AttendanceController@roomsDestroy`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Parâmetros de rota:** `id`

## `GET /api/attendance/rooms-admin/{id}`

**Rooms Show** · controller `AttendanceController@roomsShow`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Parâmetros de rota:** `id`

## `PUT /api/attendance/rooms-admin/{id}`

**Rooms Update** · controller `AttendanceController@roomsUpdate`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`RoomsUpdateAttendanceRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `name` | `'sometimes', 'required', 'string', 'max:100', EscopoAlmoxarifado::unico('attendance_rooms', 'name', $sid)->ignore($id)` |
| `description` | `nullable \| string \| max:255` |
| `active` | `nullable \| boolean` |


## `PATCH /api/attendance/rooms-admin/{id}/inactivate`

**Rooms Inactivate** · controller `AttendanceController@roomsInactivate`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Parâmetros de rota:** `id`

## `GET /api/attendance/service/{ticketId}`

**Service Data** · controller `AttendanceController@serviceData`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Parâmetros de rota:** `ticketId`

## `POST /api/attendance/service/{ticketId}/finish`

**Service Finish** · controller `AttendanceController@serviceFinish`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Parâmetros de rota:** `ticketId`
- **Corpo/validação** (`ServiceFinishAttendanceRequest`):

| Campo | Regras |
|---|---|
| `notes` | `nullable \| string` |


## `PATCH /api/attendance/service/{ticketId}/notes`

**Service Notes** · controller `AttendanceController@serviceNotes`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Parâmetros de rota:** `ticketId`
- **Corpo/validação** (`ServiceNotesAttendanceRequest`):

| Campo | Regras |
|---|---|
| `notes` | `nullable \| string` |


## `POST /api/attendance/service/{ticketId}/start`

**Service Start** · controller `AttendanceController@serviceStart`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Parâmetros de rota:** `ticketId`

## `GET /api/attendance/tickets`

**List Tickets** · controller `AttendanceController@listTickets`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Corpo/validação** (`ListTicketsAttendanceRequest`):

| Campo | Regras |
|---|---|
| `status` | `nullable \| in:aguardando,chamada,em_atendimento,finalizada,cancelada,nao_compareceu` |
| `clientId` | `nullable \| integer \| exists:clients,id` |
| `roomId` | `nullable \| integer \| <Exists>` |
| `assignedUserId` | `nullable \| integer \| exists:users,id` |
| `issuedFrom` | `nullable \| date` |
| `issuedTo` | `nullable \| date` |
| `serviceFrom` | `nullable \| date` |
| `serviceTo` | `nullable \| date` |


## `POST /api/attendance/tickets`

**Create Ticket** · controller `AttendanceController@createTicket`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Corpo/validação** (`CreateTicketAttendanceRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |
| `clientId` | `required \| integer \| exists:clients,id` |
| `prefix` | `nullable \| string \| max:3` |
| `roomId` | `nullable \| integer \| <Exists>` |


## `GET /api/attendance/tickets/{id}`

**Show Ticket** · controller `AttendanceController@showTicket`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Parâmetros de rota:** `id`

## `PATCH /api/attendance/tickets/{id}/cancel`

**Cancel Ticket** · controller `AttendanceController@cancelTicket`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Parâmetros de rota:** `id`

## `PATCH /api/attendance/tickets/{id}/no-show`

**No Show Ticket** · controller `AttendanceController@noShowTicket`

- **Acesso:** Token Sanctum; página em Perfis: `/attendance*`
- **Parâmetros de rota:** `id`

## `GET /api/queue-treatment-plans`

**Listar** · controller `QueueTreatmentPlanController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/queue*`
- **Limite de requisições:** 60 por minuto
- **Outros parâmetros lidos pelo controller:** `status`

## `POST /api/queue-treatment-plans`

**Criar** · controller `QueueTreatmentPlanController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/queue*`
- **Limite de requisições:** 60 por minuto
- **Corpo/validação** (`StoreTreatmentPlanRequest`):

| Campo | Regras |
|---|---|
| `queue_id` | `required \| integer \| <Exists>` |
| `weekdays` | `required \| array \| min:1` |
| `weekdays.*` | `integer \| between:1,7` |
| `total_sessions` | `required \| integer \| min:1 \| max:200` |


## `POST /api/queue-treatment-plans/preview`

**Preview** · controller `QueueTreatmentPlanController@preview`

- **Acesso:** Token Sanctum; página em Perfis: `/queue*`
- **Limite de requisições:** 60 por minuto
- **Corpo/validação** (`PreviewTreatmentPlanRequest`):

| Campo | Regras |
|---|---|
| `speciality_id` | `required \| integer \| <Exists>` |
| `weekdays` | `required \| array \| min:1` |
| `weekdays.*` | `integer \| between:1,7` |
| `total_sessions` | `required \| integer \| min:1 \| max:200` |


## `GET /api/queue-treatment-plans/{plan}`

**Detalhar** · controller `QueueTreatmentPlanController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/queue*`
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `plan`

## `PUT /api/queue-treatment-plans/{plan}/cancel`

**Cancel** · controller `QueueTreatmentPlanController@cancel`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `plan`
- **Corpo/validação** (`CancelTreatmentPlanRequest`):

| Campo | Regras |
|---|---|
| `reason` | `required \| string \| min:10` |


## `PUT /api/queue-treatment-sessions/{session}/complete`

**Complete** · controller `QueueTreatmentSessionController@complete`

- **Acesso:** Token Sanctum; página em Perfis: `/queue*`
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `session`

## `PUT /api/queue-treatment-sessions/{session}/reschedule`

**Reschedule** · controller `QueueTreatmentSessionController@reschedule`

- **Acesso:** Token Sanctum; página em Perfis: `/queue*`
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `session`
- **Corpo/validação** (`RescheduleTreatmentSessionRequest`):

| Campo | Regras |
|---|---|
| `new_date` | `required \| date` |
| `reason` | `required \| string \| min:5` |


## `GET /api/queues`

**Listar** · controller `QueueController@index`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Corpo/validação** (`ListQueuesRequest`):

| Campo | Regras |
|---|---|
| `search` | `nullable \| string \| max:120` |
| `per_page` | `nullable \| integer \| min:1 \| max:100` |
| `done` | `nullable \| integer \| in:0,1,2` |
| `urgency` | `nullable \| integer \| in:0,1,2` |
| `speciality_id` | `nullable \| integer \| <Exists>` |
| `date_from` | `nullable \| date_format:Y-m-d` |
| `date_to` | `nullable \| date_format:Y-m-d \| after_or_equal:date_from` |
| `sort_by` | `nullable \| string \| in:done_at,date_of_realized,id` |
| `sort_dir` | `nullable \| string \| in:asc,desc` |


## `POST /api/queues`

**Criar** · controller `QueueController@store`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Corpo/validação** (`StoreQueueRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `\App\Support\EscopoAlmoxarifado::regraCampo()` |
| `id_client` | `required \| exists:clients,id` |
| `id_specialities` | `'required', 'integer', \App\Support\EscopoAlmoxarifado::existe('specialities', $sid)` |
| `id_user` | `'required', \App\Services\Secretarias\SecretariaMembership::regraUsuario($sid)` |
| `done` | `boolean` |
| `date_of_realized` | `nullable \| date` |
| `urgency` | `required \| boolean` |
| `obs` | `nullable \| string \| max:1000` |

- **Outros parâmetros lidos pelo controller:** `secretaria_id`

## `POST /api/queues/log-location`

**Store Location Log** · controller `QueueController@storeLocationLog`

- **Acesso:** Pública (sem login)
- **Corpo/validação** (`StoreLocationLogQueueRequest`):

| Campo | Regras |
|---|---|
| `uuid` | `required \| uuid` |
| `latitude` | `required \| numeric` |
| `longitude` | `required \| numeric` |


## `GET /api/queues/specialities-options`

**Speciality Options** · controller `QueueController@specialityOptions`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto

## `GET /api/queues/{queueId}/treatment-plan`

**For Queue** · controller `QueueTreatmentPlanController@forQueue`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `queueId`

## `DELETE /api/queues/{queue}`

**Remover** · controller `QueueController@destroy`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `queue`

## `GET /api/queues/{queue}`

**Detalhar** · controller `QueueController@show`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `queue`

## `PATCH /api/queues/{queue}`

**Atualizar** · controller `QueueController@update`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `queue`
- **Corpo/validação** (`UpdateQueueRequest`):

| Campo | Regras |
|---|---|
| `id_client` | `sometimes \| required \| exists:clients,id` |
| `id_specialities` | `sometimes \| required \| integer \| <Exists>` |
| `id_user` | `sometimes \| required \| exists:users,id` |
| `done` | `boolean` |
| `date_of_realized` | `nullable \| date` |
| `urgency` | `sometimes \| required \| boolean` |
| `obs` | `nullable \| string \| max:1000` |


## `PUT /api/queues/{queue}`

**Atualizar** · controller `QueueController@update`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `queue`
- **Corpo/validação** (`UpdateQueueRequest`):

| Campo | Regras |
|---|---|
| `id_client` | `sometimes \| required \| exists:clients,id` |
| `id_specialities` | `sometimes \| required \| integer \| <Exists>` |
| `id_user` | `sometimes \| required \| exists:users,id` |
| `done` | `boolean` |
| `date_of_realized` | `nullable \| date` |
| `urgency` | `sometimes \| required \| boolean` |
| `obs` | `nullable \| string \| max:1000` |


## `GET /api/queues/{queue}/attachments`

**Listar** · controller `QueueAttachmentController@index`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `queue`

## `POST /api/queues/{queue}/attachments`

**Criar** · controller `QueueAttachmentController@store`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `queue`
- **Corpo/validação** (`StoreQueueAttachmentRequest`):

| Campo | Regras |
|---|---|
| `file` | `nullable \| file \| mimes:pdf,jpg,jpeg,png \| max:30720 \| required_without:files` |
| `files` | `nullable \| array \| required_without:file` |
| `files.*` | `file \| mimes:pdf,jpg,jpeg,png \| max:30720` |


## `DELETE /api/queues/{queue}/attachments/{attachment}`

**Remover** · controller `QueueAttachmentController@destroy`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `queue`, `attachment`

## `GET /api/queues/{queue}/attachments/{attachment}/download`

**Download** · controller `QueueAttachmentController@download`

- **Acesso:** Token Sanctum
- **Limite de requisições:** 60 por minuto
- **Parâmetros de rota:** `queue`, `attachment`

