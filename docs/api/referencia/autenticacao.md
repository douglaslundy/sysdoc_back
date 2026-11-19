# Autenticação e conta

Login por CPF, token Sanctum, validação do token, recuperação de senha e permissões do usuário logado.

> Gerado a partir das rotas reais da aplicação (`php artisan route:list`). Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs.

| Método | URL | Ação |
|---|---|---|
| `GET` | `/api/401` | Resposta 401 padrão |
| `GET` | `/api/auth/my-permissions` | Permissões do usuário logado |
| `GET` | `/api/auth/secretarias` | Secretarias do usuário logado |
| `POST` | `/api/forgot-password` | Enviar link de recuperação |
| `POST` | `/api/login` | Login |
| `POST` | `/api/logout` | Logout |
| `GET` | `/api/ping` | Verificar disponibilidade |
| `POST` | `/api/reset-password` | Redefinir senha |
| `GET` | `/api/user` | Usuário autenticado |
| `POST` | `/api/validate` | Validar token |

---

## `GET /api/401`

**Resposta 401 padrão** · controller `AuthController@unauthorized`

- **Acesso:** Pública (sem login)

## `GET /api/auth/my-permissions`

**Permissões do usuário logado** · controller `AccessProfileController@myPermissions`

- **Acesso:** Token Sanctum

## `GET /api/auth/secretarias`

**Secretarias do usuário logado** · controller `AccessProfileController@mySecretarias`

- **Acesso:** Token Sanctum

## `POST /api/forgot-password`

**Enviar link de recuperação** · controller `PasswordResetController@sendLink`

- **Acesso:** Pública (sem login)
- **Limite de requisições:** 5 a cada 5 minutos por e-mail + IP
- **Corpo/validação** (feita no controller):

| Campo | Regras |
|---|---|
| `email` | `required \| email` |


## `POST /api/login`

**Login** · controller `AuthController@login`

- **Acesso:** Pública (sem login)
- **Limite de requisições:** 5 por minuto por CPF + IP
- **Corpo/validação** (feita no controller):

| Campo | Regras |
|---|---|
| `cpf` | `required \| string` |
| `password` | `required` |


## `POST /api/logout`

**Logout** · controller `AuthController@logout`

- **Acesso:** Token Sanctum

## `GET /api/ping`

**Verificar disponibilidade** · controller `Closure`

- **Acesso:** Pública (sem login)

## `POST /api/reset-password`

**Redefinir senha** · controller `PasswordResetController@reset`

- **Acesso:** Pública (sem login)
- **Limite de requisições:** 5 por minuto
- **Corpo/validação** (feita no controller):

| Campo | Regras |
|---|---|
| `email` | `required \| email` |
| `token` | `required \| string` |
| `password` | `required \| string \| min:8 \| confirmed` |


## `GET /api/user`

**Usuário autenticado** · controller `Closure`

- **Acesso:** Token Sanctum

## `POST /api/validate`

**Validar token** · controller `AuthController@validateToken`

- **Acesso:** Token Sanctum

