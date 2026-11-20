# 01 — Autenticação

A API usa **tokens de acesso pessoais do Laravel Sanctum**, enviados como `Authorization: Bearer <token>`. Não há cookie de sessão nem CSRF na API: ela é *stateless*, e o CORS está configurado com `supports_credentials = false`.

## Fluxo resumido

1. `POST /api/login` com CPF e senha → recebe `token`.
2. Envie `Authorization: Bearer <token>` em toda chamada autenticada.
3. (Opcional) `GET /api/auth/my-permissions` e `GET /api/auth/secretarias` para saber o que o usuário pode ver e em qual secretaria atuar.
4. Ao terminar, `POST /api/logout`.

## Login

`POST /api/login` — público, limite de **5 tentativas por minuto por CPF + IP**.

**Cabeçalhos**

```
Accept: application/json
Content-Type: application/json
```

**Corpo**

| Campo | Tipo | Obrigatório | Observação |
|---|---|---|---|
| `cpf` | string | sim | Somente os **11 dígitos**, sem pontos nem traço (é assim que o cadastro grava o CPF dos usuários) |
| `password` | string | sim | Senha do usuário |

**Sucesso — `201 Created`**

```json
{
  "token": "15|Qm3x...",
  "user": { "id": 1, "name": "Maria Souza", "email": "maria@exemplo.gov.br", "profile": "admin" }
}
```

`profile` é o *slug* do perfil de acesso (por exemplo `admin`, `gestor`, `tecnico`, `user`).

**Erros**

| Status | Quando | Corpo |
|---|---|---|
| `401` | CPF inexistente, senha errada ou usuário inativo | `{"message":"Usuário ou senha inválidos."}` |
| `422` | `cpf` ou `password` ausentes | `{"message":"...","errors":{"cpf":["..."]}}` |
| `429` | Mais de 5 tentativas por minuto | `{"message":"Muitas tentativas de login. Aguarde um instante e tente novamente."}` |

A mensagem de `401` é a mesma para CPF inexistente e senha errada (não revela qual dos dois falhou).

## Usar o token

```
Authorization: Bearer 15|Qm3x...
Accept: application/json
```

- O token tem o formato `id|segredo`. Envie **inteiro**.
- Tokens **expiram em 24 horas** (`expiration = 1440` minutos). Depois disso a API responde `401` e é preciso fazer login de novo. Não há *refresh token*.
- Cada login cria um token novo; os anteriores continuam válidos até expirar ou até o logout.
- Guarde o token com o cuidado que daria a uma senha: use HTTPS sempre e nunca coloque o token em URL.

## Validar o token e ler o usuário

`POST /api/validate` — confere se o token ainda vale.

```json
{ "message": "Authenticated", "user": { "id": 1, "name": "Maria Souza", "email": "...", "profile": "admin" } }
```

`GET /api/user` devolve o registro do usuário autenticado.

## Logout

`POST /api/logout` — **revoga todos os tokens do usuário** (em todos os dispositivos), não só o token usado na chamada.

```json
{ "message": "Logout efetuado com sucesso." }
```

## Permissões e secretarias do usuário logado

`GET /api/auth/my-permissions`

```json
{
  "paths": ["/dashboard", "/clients", "/ordem-servico"],
  "pages": [
    { "id": 12, "titulo": "Ordens de Serviço", "path": "/ordem-servico", "icone": "...", "categoria": "...", "category_id": 4, "ordem": 1, "ativo": true, "category": { "id": 4, "nome": "...", "icone": "...", "ordem": 2, "ativo": true } }
  ],
  "capabilities": {
    "chat": true,
    "almoxarifado_create": false,
    "almoxarifado_approve": false,
    "almoxarifado_deliver": false,
    "client_trips_view": true,
    "client_report_view": true,
    "client_history_view": false
  }
}
```

- `pages`/`paths`: páginas que o usuário pode usar = **páginas do perfil ∩ páginas liberadas na secretaria ativa** (administrador em "todas as secretarias" não é filtrado).
- `capabilities`: permissões operacionais do perfil (chat, almoxarifado, botões do cidadão etc.). A lista completa pode crescer com novas permissões de perfil.

`GET /api/auth/secretarias`

```json
{ "secretarias": [ { "id": 1, "nome": "Saúde" }, { "id": 2, "nome": "Obras" } ], "pode_todas": false }
```

`pode_todas` é `true` só para administrador (que pode usar `X-Secretaria-Id: all`). Veja [Permissões e secretarias](03-permissoes-e-secretarias.md).

## Recuperação de senha

1. `POST /api/forgot-password` com `{"email":"..."}` — limite de **5 a cada 5 minutos por e-mail + IP**. A resposta é sempre a mesma (`{"message":"Se o e-mail estiver cadastrado, você receberá o link de redefinição."}`), exista o e-mail ou não, para não revelar quem tem cadastro.
2. O usuário recebe um e-mail com link para a tela de redefinição, contendo `token` e `email`. O token vale **60 minutos**.
3. `POST /api/reset-password` — limite de 5 por minuto:

| Campo | Regras |
|---|---|
| `email` | obrigatório, e-mail |
| `token` | obrigatório (o do link) |
| `password` | obrigatório, mínimo 8 caracteres |
| `password_confirmation` | igual a `password` |

Erros comuns: `422 {"message":"Token invalido ou expirado."}` e `422 {"message":"Token expirado. Solicite um novo link."}`.

## Cadastro público com aprovação

Quando a flag `cadastro.publico_habilitado` está ligada no servidor, uma pessoa pode pedir acesso sem login:

1. `GET /api/public/cadastro/secretarias` — lista as secretarias disponíveis.
2. `GET /api/public/cadastro/desafio` — pergunta anti-robô.
3. `POST /api/public/cadastro` — envia o pedido (limite: 5 por hora por IP).

O pedido **não** cria o usuário: ele vira um pedido pendente (e um protocolo no kanban) que um administrador, ou o gestor da secretaria, aprova ou recusa. O solicitante é avisado por e-mail e WhatsApp. Com a flag desligada, essas rotas respondem `404`.

## Bloqueio automático de IP

Chamadas **sem token** que geram muitos `404` (comportamento típico de varredura de sites) levam ao bloqueio do IP: por padrão **5 respostas 404 em 10 minutos** bloqueiam o IP por 30 dias (o limite e os dias de bloqueio são configuráveis pelo administrador). Pedir um caminho que só robôs procuram (`.env`, `wp-login.php`, `phpmyadmin`, `.git` e semelhantes) bloqueia o IP **na primeira tentativa**. IP bloqueado recebe `403 {"message":"Acesso bloqueado."}` nas chamadas sem token, com os cabeçalhos CORS normais, então o navegador mostra a mensagem em vez de um erro de CORS. Chamadas que enviam token passam pelo bloqueio e são decididas pela autenticação.

Por isso: em integrações, nunca chame URLs que não existem sem enviar o token, e prefira sempre as URLs documentadas aqui.

## Respostas de autenticação e autorização

| Status | Significado | Corpo típico |
|---|---|---|
| `401` | Sem token, token inválido ou expirado | `{"message":"Unauthenticated."}` (com `Accept: application/json`) |
| `403` | Autenticado, mas sem permissão para esta página/ação | `{"message":"Usuário sem permissão para esta página."}` |
| `403` | Secretaria inválida no `X-Secretaria-Id` | `{"message":"Secretaria não vinculada ao usuário."}` ou `{"message":"Secretaria não permitida."}` |
| `403` | Rota restrita a administrador | `{"message":"Acesso restrito a administradores."}` |
| `403` | Recurso exclusivo da secretaria de Saúde | `{"message":"Recurso disponível apenas para a secretaria de Saúde."}` |

> Sem `Accept: application/json`, a falta de token redireciona para `/api/401`, que responde `{"error":"Não autorizado"}` com status 401. Envie sempre o `Accept` para ter um comportamento previsível.
