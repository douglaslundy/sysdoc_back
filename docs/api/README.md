# API do Sysdoc — guia para desenvolvedores

Documentação da API REST do Sysdoc, o sistema de gestão municipal (saúde, protocolo, almoxarifado, ordens de serviço, iluminação pública e mais). Quem lê aqui consegue **autenticar, montar os cabeçalhos corretos, entender as permissões e consumir os dados** do sistema sem precisar abrir o código.

- **URL base (produção):** `https://dlsistemas.com.br/api`
- **URL base (desenvolvimento local):** `http://localhost:8000/api`
- **Formato:** JSON (`application/json`); uploads em `multipart/form-data`; PDFs e fotos como arquivo binário.
- **Autenticação:** token Bearer (Laravel Sanctum), obtido com CPF e senha.
- **Escopo de dados:** cada usuário enxerga os dados da **secretaria ativa** e das páginas liberadas no **perfil de acesso** dele.
- **Tamanho:** 489 rotas (510 combinações de método + URL), em 16 grupos de módulos.

> Levantamento feito em 2026-11-19 a partir das rotas reais da aplicação. Se um módulo mudar, a referência em `referencia/` e o `openapi.json` precisam ser regenerados a partir de `php artisan route:list`.

## Comece em 3 passos

```bash
# 1) Login: troque CPF (11 dígitos) e senha por um token
curl -s -X POST https://dlsistemas.com.br/api/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"cpf":"12345678909","password":"sua-senha"}'
# → 201 {"token":"15|AbC...","user":{"id":1,"name":"...","email":"...","profile":"admin"}}

# 2) Use o token em toda chamada autenticada
curl -s https://dlsistemas.com.br/api/auth/my-permissions \
  -H "Accept: application/json" -H "Authorization: Bearer 15|AbC..."

# 3) (opcional) escolha a secretaria ativa
curl -s https://dlsistemas.com.br/api/clients \
  -H "Accept: application/json" -H "Authorization: Bearer 15|AbC..." \
  -H "X-Secretaria-Id: 1"
```

Sempre envie `Accept: application/json`. Sem ele, erros de validação e de autenticação viram redirecionamentos HTML em vez de JSON (ver [Convenções](02-convencoes.md)).

## Índice

| Documento | Para que serve |
|---|---|
| [01 — Autenticação](01-autenticacao.md) | Login, token, expiração, logout, recuperação de senha, cadastro público |
| [02 — Convenções](02-convencoes.md) | Cabeçalhos, formatos, paginação, erros, limites de requisição, CORS, uploads e downloads |
| [03 — Permissões e secretarias](03-permissoes-e-secretarias.md) | Perfis, páginas, secretaria ativa, administrador, como a API decide o que você pode ver |
| [04 — Exemplos](04-exemplos.md) | Fluxos completos em cURL, JavaScript, Python e PHP |
| [05 — Modelo de dados](05-modelo-de-dados.md) | Tabelas e colunas do banco, por módulo, para entender de onde vêm os dados |
| [`openapi.json`](openapi.json) | Especificação OpenAPI 3 (importe no Postman, Insomnia ou Swagger UI) |
| [`referencia/`](referencia/) | Referência de **todos** os endpoints, um arquivo por módulo |

## Módulos (referência dos endpoints)

| Módulo | Arquivo | O que cobre |
|---|---|---|
| Autenticação e conta | [autenticacao](referencia/autenticacao.md) | Login, token, permissões do usuário, recuperação de senha |
| Usuários, perfis e secretarias | [usuarios-e-acesso](referencia/usuarios-e-acesso.md) | Usuários, perfis de acesso, páginas, secretarias, setores, especialidades, pedidos de cadastro |
| Administração e auditoria | [administracao-e-auditoria](referencia/administracao-e-auditoria.md) | Configurações, auditoria, log de erros, IPs bloqueados, mensagens enviadas, avisos |
| Cidadãos | [cidadaos](referencia/cidadaos.md) | Cadastro do cidadão, histórico, relatório detalhado, conformidade |
| Atendimento e fila | [atendimento-fila](referencia/atendimento-fila.md) | Senhas, salas, chamadas, fila por especialidade, sessões de tratamento |
| Laboratório | [laboratorio](referencia/laboratorio.md) | Exames, pedidos, resultados, laudos, agenda de coleta |
| TFD e viagens | [tfd](referencia/tfd.md) | Viagens, agenda, pacientes e acompanhantes, veículos, rotas |
| Farmácia | [farmacia](referencia/farmacia.md) | Medicamentos, disponibilidade, aquisições, importação, transparência |
| Almoxarifado | [almoxarifado](referencia/almoxarifado.md) | Produtos, estoque, movimentações, requisições |
| Protocolo, documentos e kanban | [protocolo-e-documentos](referencia/protocolo-e-documentos.md) | Protocolos, documentos, ofícios e portarias (IA), kanban |
| Vigilância sanitária | [vigilancia](referencia/vigilancia.md) | Estabelecimentos, alvarás, fiscalizações, denúncias |
| Iluminação pública e OS | [iluminacao-e-os](referencia/iluminacao-e-os.md) | Postes, fotos, desfechos, ordens de serviço |
| Monitor APS e painel e-SUS | [monitor-aps-esus](referencia/monitor-aps-esus.md) | Indicadores da Atenção Primária, painéis |
| Chat interno | [chat](referencia/chat.md) | Conversas, mensagens, presença |
| Dashboards | [dashboard](referencia/dashboard.md) | Indicadores por módulo, atalhos |
| Endpoints públicos | [publicos](referencia/publicos.md) | Rotas sem login |

## Endpoints públicos (sem token)

São os únicos que dispensam `Authorization`. Todos têm limite de requisições por IP (ver [Convenções](02-convencoes.md#limites-de-requisições)).

| Método | URL | Limite |
|---|---|---|
| `GET` | `/api/ping` | — |
| `POST` | `/api/login` | 5 por minuto por CPF + IP |
| `POST` | `/api/forgot-password` | 5 a cada 5 minutos por e-mail + IP |
| `POST` | `/api/reset-password` | 5 por minuto |
| `POST` | `/api/consulta-exame` | 10 por minuto |
| `POST` | `/api/consulta-exame/pdf/{protocolo}` | 10 por minuto |
| `GET` | `/api/attendance/panel/state` | — |
| `POST` | `/api/queues/log-location` | — |
| `GET` | `/api/public/painel-publico` | — |
| `GET` | `/api/public/painel-esus/estado` | 30 por minuto |
| `GET` | `/api/public/painel-esus/validar-cnes` | 30 por minuto |
| `GET` | `/api/public/pharmacy/medicines/daily` | — |
| `GET` | `/api/public/pharmacy/medicines/panel` | — |
| `GET` | `/api/public/pharmacy/medicines/monthly-acquisitions` | — |
| `GET` | `/api/public/petition/reasons` | — |
| `POST` | `/api/public/petitions` (e `/api/public/denuncias`, rota antiga) | 5 por hora por IP |
| `POST` | `/api/public/petitions/consulta` (e `/api/public/denuncias/consulta`) | 10 por minuto por IP |
| `GET` | `/api/public/cadastro/secretarias` | 30 por minuto por IP |
| `GET` | `/api/public/cadastro/desafio` | 30 por minuto por IP |
| `POST` | `/api/public/cadastro` | 5 por hora por IP |
| `GET` | `/api/401` | — (resposta padrão de "não autenticado") |

Observações:
- O cadastro público (`/api/public/cadastro*`) só responde quando a flag `cadastro.publico_habilitado` está ligada no servidor; desligada, devolve 404.
- `/api/queues/log-location` aceita registros identificados por UUID, sem login.

## Convenções deste guia

- Todas as URLs incluem o prefixo `/api`.
- `{id}` em uma URL é um parâmetro de rota.
- "Página em Perfis" significa que o perfil de acesso do usuário precisa ter aquela página liberada (ver [Permissões](03-permissoes-e-secretarias.md)).
- Campos listados como "Corpo/validação" vêm das regras de validação reais (FormRequests ou `validate()` do controller). Respostas variam por endpoint; os campos exatos de cada resposta devem ser conferidos chamando o endpoint em ambiente de desenvolvimento.
