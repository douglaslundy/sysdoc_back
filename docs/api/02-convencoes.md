# 02 — Convenções da API

## Cabeçalhos

### Em toda chamada

| Cabeçalho | Valor | Por quê |
|---|---|---|
| `Accept` | `application/json` | **Obrigatório na prática.** Sem ele, erros de validação (`422`) e de autenticação (`401`) podem virar redirecionamentos HTML (`302`) em vez de JSON |
| `Content-Type` | `application/json` (ou `multipart/form-data` em upload) | Define como o corpo é lido |

### Em chamadas autenticadas

| Cabeçalho | Valor | Por quê |
|---|---|---|
| `Authorization` | `Bearer <token>` | Token obtido em `POST /api/login` |
| `X-Secretaria-Id` | id da secretaria, ou `all` (somente administrador) | Escolhe a **secretaria ativa**; sem ele, vale a primeira secretaria do usuário (administrador: todas). Ver [Permissões e secretarias](03-permissoes-e-secretarias.md) |

### Opcional

| Cabeçalho | Valor | Por quê |
|---|---|---|
| `X-Client-Info` | JSON codificado em URL (fuso, tela, modelo do aparelho etc.) | Enriquece o registro de auditoria e de erros com informações do cliente. Só chaves conhecidas são lidas; não é necessário para o funcionamento |

### Exemplo completo

```http
GET /api/clients?page=1&per_page=20 HTTP/1.1
Host: dlsistemas.com.br
Accept: application/json
Authorization: Bearer 15|Qm3x...
X-Secretaria-Id: 1
```

## URL base e versão

- Produção: `https://dlsistemas.com.br/api` · Local: `http://localhost:8000/api`.
- Não há versão na URL (`/v1`). Mudanças são feitas de forma compatível sempre que possível; confira o `openapi.json` da versão que você usa.

## Formatos de dados

- **Corpo:** JSON em `UTF-8`. IDs são inteiros. Booleanos são `true`/`false` (em `multipart/form-data`, use `1`/`0`).
- **Datas de entrada:** `YYYY-MM-DD` (por exemplo `2026-11-19`); data e hora costumam aceitar `YYYY-MM-DD HH:MM:SS`. As regras de cada campo estão na [referência](referencia/).
- **Datas de saída:** os campos `created_at`/`updated_at` vêm em ISO 8601 (UTC, por exemplo `2026-11-19T14:30:00.000000Z`). O fuso de negócio do sistema é `America/Sao_Paulo`; datas simples (`born_date`, `data`) vêm como `YYYY-MM-DD`.
- **CPF:** nos usuários, somente 11 dígitos. Em cidadãos (`clients`) o campo aceita até 18 caracteres e a busca ignora a formatação.
- **Texto e idioma:** mensagens de erro misturam português e inglês (o idioma padrão do framework é inglês). **Não programe contra o texto das mensagens**; use o status HTTP e as chaves de `errors`.

## Respostas e códigos de status

| Status | Uso |
|---|---|
| `200 OK` | Leitura ou atualização bem-sucedida |
| `201 Created` | Criação (inclui o login) |
| `204 No Content` | Remoção sem corpo (alguns endpoints devolvem `200` com `{"message": "..."}`) |
| `401 Unauthorized` | Token ausente, inválido ou expirado |
| `403 Forbidden` | Sem permissão (perfil, secretaria, administrador, IP bloqueado) |
| `404 Not Found` | Registro inexistente **ou fora do seu escopo** (por exemplo, de outra secretaria): em vários módulos o filtro de escopo faz o registro "não existir" para quem não pode vê-lo |
| `409 Conflict` | Conflito de estado em alguns endpoints (por exemplo, ação inválida para o status atual) |
| `422 Unprocessable Entity` | Validação falhou |
| `429 Too Many Requests` | Limite de requisições excedido |
| `5xx` | Erro do servidor — registrado no log de erros; tente de novo e, se persistir, avise o suporte |

### Erro de validação (`422`)

```json
{
  "message": "The cpf field is required.",
  "errors": {
    "cpf": ["The cpf field is required."],
    "password": ["The password field is required."]
  }
}
```

Leia `errors.<campo>` (lista de mensagens). Campos aninhados usam notação com ponto (`itens.0.quantidade`).

### Erro de regra de negócio

Normalmente `{"message": "Texto explicando o motivo."}` com `403`, `404`, `409` ou `422`. Alguns endpoints antigos usam `{"error": "..."}`; trate os dois formatos.

## Paginação

Listas grandes são paginadas pelo paginador do Laravel. Parâmetros de consulta (na maioria dos módulos):

| Parâmetro | Padrão | Observação |
|---|---|---|
| `page` | `1` | Página desejada |
| `per_page` | de 10 a 50, conforme o módulo | Em vários módulos o teto é **100** |

Resposta típica:

```json
{
  "current_page": 1,
  "data": [ { "id": 1 } ],
  "first_page_url": "https://dlsistemas.com.br/api/almoxarifado/produtos?page=1",
  "from": 1,
  "last_page": 7,
  "last_page_url": "https://dlsistemas.com.br/api/almoxarifado/produtos?page=7",
  "next_page_url": "https://dlsistemas.com.br/api/almoxarifado/produtos?page=2",
  "path": "https://dlsistemas.com.br/api/almoxarifado/produtos",
  "per_page": 15,
  "prev_page_url": null,
  "to": 15,
  "total": 98
}
```

Alguns endpoints devolvem o formato "resource" do Laravel: `{"data": [...], "links": {...}, "meta": {...}}`. Outros devolvem uma **lista simples** (array JSON) quando o volume é pequeno. Para saber qual é o caso, chame o endpoint uma vez em desenvolvimento.

## Filtros, busca e ordenação

Cada endpoint define seus próprios filtros. A referência de cada módulo lista, em "Corpo/validação" (para `GET`, parâmetros de consulta validados) e em "Outros parâmetros lidos pelo controller", os nomes que o endpoint aceita, por exemplo `search`, `status`, `de`, `ate`, `secretaria_id`.

## Limites de requisições

| Escopo | Limite |
|---|---|
| Geral, autenticado | **1500 por minuto por usuário** |
| Geral, sem login | 240 por minuto por IP |
| Login | 5 por minuto por CPF + IP |
| Recuperação de senha | 5 a cada 5 minutos por e-mail + IP |
| Denúncia/petição pública | 5 por hora por IP (consulta: 10 por minuto) |
| Consulta de exame pública | 10 por minuto |
| Cadastro público | 5 por hora por IP |
| Dashboards | 120 por minuto |
| Monitor APS | 180 por minuto |
| Geração de PDF de OS | 20 por minuto |
| Chat | limites configuráveis pelo administrador (sincronização, mensagens, digitação, presença) |

Ao exceder, a resposta é `429` com os cabeçalhos `Retry-After` (segundos) e `X-RateLimit-Limit`/`X-RateLimit-Remaining`. Faça *backoff* e tente de novo depois do tempo indicado.

Dica: usuários da mesma prefeitura costumam sair por **um único IP**, por isso o limite autenticado é por usuário, não por IP.

## Uploads

- `multipart/form-data` com os campos de texto e o(s) arquivo(s). **Não** defina `Content-Type` manualmente em clientes que montam o *boundary* sozinhos (fetch, axios, requests, cURL `-F`).
- Os nomes dos campos de arquivo, os tipos aceitos e os tamanhos máximos estão nas regras do endpoint (por exemplo `mimes`, `max`). Exemplo: fotos de poste de iluminação aceitam até 4 por poste e 8 MB cada.
- **PUT/PATCH com arquivo:** o PHP não lê `multipart/form-data` em `PUT`. Envie `POST` com o campo extra `_method=PUT` (ou `PATCH`).
- Arquivos são guardados em disco **privado**: não há URL pública direta; a leitura passa por um endpoint autenticado.

## Downloads (PDF, laudos, fotos, anexos)

Endpoints de arquivo devolvem o binário (`application/pdf`, `image/*`...). Como exigem o cabeçalho `Authorization`, **não funcionam como link direto** em `<a href>`: baixe com a requisição autenticada e entregue o arquivo ao usuário.

```js
const res = await fetch(`${API}/ordens-servico/42/pdf`, { headers: { Authorization: `Bearer ${token}`, Accept: 'application/pdf' } });
const blob = await res.blob();               // use URL.createObjectURL(blob) para abrir/baixar
```

A consulta pública de laudos usa `POST /api/consulta-exame/pdf/{protocolo}` (sem token).

## CORS (chamadas a partir do navegador)

A API só aceita requisições de navegador vindas das origens liberadas: `https://sysdoc.vercel.app`, `https://sysvendas.vercel.app`, `http://localhost:3000` e `http://127.0.0.1:3000`. Métodos: `GET, POST, PUT, PATCH, DELETE, OPTIONS`. Cabeçalhos permitidos: `Content-Type`, `Authorization`, `X-Requested-With`, `Accept`, `X-Client-Info`, `X-Secretaria-Id`.

- Integrações **servidor a servidor** (PHP, Python, Node, cURL) não passam por CORS.
- Para consumir direto do navegador a partir de outro domínio, o domínio precisa ser incluído em `config/cors.php` no servidor.
- Se você criar um cabeçalho personalizado novo, ele também precisa estar em `allowed_headers`; do contrário o navegador bloqueia o *preflight*.

## Idempotência e concorrência

- A API **não** exige chave de idempotência. Repetir um `POST` pode criar um segundo registro; trate *timeouts* verificando antes de reenviar.
- Operações sensíveis (numeração de protocolo, estoque, alvarás) usam transações e travas no banco; em conflito raro a API pode responder com erro de concorrência. Repetir a chamada costuma resolver.

## Auditoria

As requisições autenticadas são registradas (quem, quando, IP, endpoint) e, nos cadastros auditados, as alterações guardam também o antes e o depois dos campos. Isso vale para integrações também: crie um usuário próprio para cada integração, em vez de compartilhar um usuário de pessoa, para que a trilha de auditoria identifique a origem.
