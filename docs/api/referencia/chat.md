# Chat interno

Conversas, mensagens, anexos, presença e autorização de canais em tempo real.

> Gerado a partir das rotas reais da aplicação (`php artisan route:list`). Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs.

| Método | URL | Ação |
|---|---|---|
| `GET` | `/api/broadcasting/auth` | Authenticate |
| `POST` | `/api/broadcasting/auth` | Authenticate |
| `GET` | `/api/chat/attachments/{attachment}` | Attachment |
| `DELETE` | `/api/chat/config` | Remover |
| `GET` | `/api/chat/config` | Detalhar |
| `PUT` | `/api/chat/config` | Atualizar |
| `PATCH` | `/api/chat/config/chat-enabled` | Set Chat Enabled |
| `PATCH` | `/api/chat/config/status` | Set Active |
| `POST` | `/api/chat/config/test` | Test |
| `GET` | `/api/chat/conversations` | Conversations |
| `POST` | `/api/chat/conversations` | Start Conversation |
| `DELETE` | `/api/chat/conversations/{conversation}` | Delete Conversation |
| `GET` | `/api/chat/conversations/{conversation}/messages` | Messages |
| `POST` | `/api/chat/conversations/{conversation}/messages` | Send Message |
| `POST` | `/api/chat/conversations/{conversation}/read` | Mark Read |
| `POST` | `/api/chat/conversations/{conversation}/typing` | Typing |
| `DELETE` | `/api/chat/messages` | Delete Messages |
| `DELETE` | `/api/chat/messages/{message}` | Delete Message |
| `POST` | `/api/chat/messages/{message}/delivered` | Mark Delivered |
| `POST` | `/api/chat/presence` | Presence |
| `GET` | `/api/chat/realtime-config` | Public Config |
| `GET` | `/api/chat/unread` | Unread |
| `GET` | `/api/chat/users` | Users |

---

## `GET /api/broadcasting/auth`

**Authenticate** · controller `Illuminate\Broadcasting\BroadcastController@authenticate`

- **Acesso:** Token Sanctum

## `POST /api/broadcasting/auth`

**Authenticate** · controller `Illuminate\Broadcasting\BroadcastController@authenticate`

- **Acesso:** Token Sanctum

## `GET /api/chat/attachments/{attachment}`

**Attachment** · controller `ChatController@attachment`

- **Acesso:** Token Sanctum; perfil com chat liberado
- **Parâmetros de rota:** `attachment`

## `DELETE /api/chat/config`

**Remover** · controller `ChatRealtimeConfigController@destroy`

- **Acesso:** Token Sanctum; somente administrador

## `GET /api/chat/config`

**Detalhar** · controller `ChatRealtimeConfigController@show`

- **Acesso:** Token Sanctum; somente administrador

## `PUT /api/chat/config`

**Atualizar** · controller `ChatRealtimeConfigController@update`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`UpdateChatRealtimeConfigRequest`):

| Campo | Regras |
|---|---|
| `engine` | `required \| <In>` |
| `active` | `required \| boolean` |
| `app_id` | `nullable \| string \| max:255` |
| `app_key` | `nullable \| string \| max:255` |
| `app_secret` | `nullable \| string \| max:500` |
| `cluster` | `nullable \| string \| max:40 \| required_if:engine,pusher` |
| `host` | `nullable \| string \| max:255 \| required_if:engine,soketi` |
| `port` | `nullable \| integer \| min:1 \| max:65535 \| required_if:engine,soketi` |
| `scheme` | `nullable \| <In> \| required_if:engine,soketi` |
| `use_tls` | `nullable \| boolean` |
| `rate_limit_decay_minutes` | `nullable \| integer \| min:1 \| max:60` |
| `rate_limit_global` | `nullable \| integer \| min:0 \| max:5000` |
| `rate_limit_sync` | `nullable \| integer \| min:0 \| max:5000` |
| `rate_limit_messages` | `nullable \| integer \| min:0 \| max:5000` |
| `rate_limit_typing` | `nullable \| integer \| min:0 \| max:5000` |
| `rate_limit_presence` | `nullable \| integer \| min:0 \| max:5000` |
| `auto_open_on_message` | `nullable \| boolean` |
| `play_sound_on_message` | `nullable \| boolean` |


## `PATCH /api/chat/config/chat-enabled`

**Set Chat Enabled** · controller `ChatRealtimeConfigController@setChatEnabled`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`SetChatEnabledChatRealtimeConfigRequest`):

| Campo | Regras |
|---|---|
| `chat_enabled` | `required \| boolean` |


## `PATCH /api/chat/config/status`

**Set Active** · controller `ChatRealtimeConfigController@setActive`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`SetActiveChatRealtimeConfigRequest`):

| Campo | Regras |
|---|---|
| `active` | `required \| boolean` |


## `POST /api/chat/config/test`

**Test** · controller `ChatRealtimeConfigController@test`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`TestChatRealtimeConfigRequest`):

| Campo | Regras |
|---|---|
| `engine` | `required \| <In>` |
| `active` | `nullable \| boolean` |
| `app_id` | `nullable \| string \| max:255` |
| `app_key` | `nullable \| string \| max:255` |
| `app_secret` | `nullable \| string \| max:500` |
| `cluster` | `nullable \| string \| max:40 \| required_if:engine,pusher` |
| `host` | `nullable \| string \| max:255 \| required_if:engine,soketi` |
| `port` | `nullable \| integer \| min:1 \| max:65535 \| required_if:engine,soketi` |
| `scheme` | `nullable \| <In> \| required_if:engine,soketi` |
| `use_tls` | `nullable \| boolean` |
| `rate_limit_decay_minutes` | `nullable \| integer \| min:1 \| max:60` |
| `rate_limit_global` | `nullable \| integer \| min:0 \| max:5000` |
| `rate_limit_sync` | `nullable \| integer \| min:0 \| max:5000` |
| `rate_limit_messages` | `nullable \| integer \| min:0 \| max:5000` |
| `rate_limit_typing` | `nullable \| integer \| min:0 \| max:5000` |
| `rate_limit_presence` | `nullable \| integer \| min:0 \| max:5000` |
| `auto_open_on_message` | `nullable \| boolean` |
| `play_sound_on_message` | `nullable \| boolean` |


## `GET /api/chat/conversations`

**Conversations** · controller `ChatController@conversations`

- **Acesso:** Token Sanctum; perfil com chat liberado
- **Limite de requisições:** limite configurável do chat

## `POST /api/chat/conversations`

**Start Conversation** · controller `ChatController@startConversation`

- **Acesso:** Token Sanctum; perfil com chat liberado
- **Corpo/validação** (`StartConversationChatRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |
| `user_id` | `required \| integer \| <Exists>` |


## `DELETE /api/chat/conversations/{conversation}`

**Delete Conversation** · controller `ChatController@deleteConversation`

- **Acesso:** Token Sanctum; perfil com chat liberado
- **Parâmetros de rota:** `conversation`

## `GET /api/chat/conversations/{conversation}/messages`

**Messages** · controller `ChatController@messages`

- **Acesso:** Token Sanctum; perfil com chat liberado
- **Limite de requisições:** limite configurável do chat
- **Parâmetros de rota:** `conversation`
- **Outros parâmetros lidos pelo controller:** `per_page`, `search`

## `POST /api/chat/conversations/{conversation}/messages`

**Send Message** · controller `ChatController@sendMessage`

- **Acesso:** Token Sanctum; perfil com chat liberado
- **Limite de requisições:** limite configurável do chat
- **Parâmetros de rota:** `conversation`
- **Corpo/validação** (`SendMessageChatRequest`):

| Campo | Regras |
|---|---|
| `body` | `nullable \| string \| max:4000` |
| `file` | `nullable \| file \| max:2048 \| mimes:jpg,jpeg,png,webp,txt,pdf \| mimetypes:image/jpeg,image/png,image/webp,text/plain,application/pdf` |


## `POST /api/chat/conversations/{conversation}/read`

**Mark Read** · controller `ChatController@markRead`

- **Acesso:** Token Sanctum; perfil com chat liberado
- **Limite de requisições:** limite configurável do chat
- **Parâmetros de rota:** `conversation`

## `POST /api/chat/conversations/{conversation}/typing`

**Typing** · controller `ChatController@typing`

- **Acesso:** Token Sanctum; perfil com chat liberado
- **Limite de requisições:** limite configurável do chat
- **Parâmetros de rota:** `conversation`
- **Corpo/validação** (`TypingChatRequest`):

| Campo | Regras |
|---|---|
| `typing` | `required \| boolean` |


## `DELETE /api/chat/messages`

**Delete Messages** · controller `ChatController@deleteMessages`

- **Acesso:** Token Sanctum; perfil com chat liberado
- **Corpo/validação** (`DeleteMessagesChatRequest`):

| Campo | Regras |
|---|---|
| `message_ids` | `required \| array \| min:1 \| max:100` |
| `message_ids.*` | `required \| integer \| distinct \| <Exists>` |


## `DELETE /api/chat/messages/{message}`

**Delete Message** · controller `ChatController@deleteMessage`

- **Acesso:** Token Sanctum; perfil com chat liberado
- **Parâmetros de rota:** `message`

## `POST /api/chat/messages/{message}/delivered`

**Mark Delivered** · controller `ChatController@markDelivered`

- **Acesso:** Token Sanctum; perfil com chat liberado
- **Limite de requisições:** limite configurável do chat
- **Parâmetros de rota:** `message`

## `POST /api/chat/presence`

**Presence** · controller `ChatController@presence`

- **Acesso:** Token Sanctum; perfil com chat liberado
- **Limite de requisições:** limite configurável do chat
- **Corpo/validação** (`PresenceChatRequest`):

| Campo | Regras |
|---|---|
| `state` | `required \| <In>` |
| `path` | `nullable \| string \| max:255` |
| `connection_id` | `required \| uuid` |


## `GET /api/chat/realtime-config`

**Public Config** · controller `ChatRealtimeConfigController@publicConfig`

- **Acesso:** Token Sanctum; perfil com chat liberado

## `GET /api/chat/unread`

**Unread** · controller `ChatController@unread`

- **Acesso:** Token Sanctum; perfil com chat liberado
- **Limite de requisições:** limite configurável do chat

## `GET /api/chat/users`

**Users** · controller `ChatController@users`

- **Acesso:** Token Sanctum; perfil com chat liberado
- **Limite de requisições:** limite configurável do chat

