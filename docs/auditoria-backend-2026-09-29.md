# Auditoria do backend — 29/09/2026

Escopo: 441 rotas, 89 controllers, 20 services, 105 models. Método: leitura do código, varredura
dinâmica (todas as rotas GET sem parâmetro chamadas em base de teste, como administrador e como
usuário comum), sondas de escrita com usuário sem permissão e suíte de testes.

Legenda: **[APLICADO]** já corrigido e coberto por teste · **[PROPOSTO]** precisa de decisão/validação.

---

## 1. Segurança

### S1 — Configurações sensíveis abertas a qualquer usuário logado — Crítico **[APLICADO]**
Um usuário de perfil `user`, sem nenhuma página liberada, conseguia (comprovado por sonda):
- ler **e sobrescrever** `/email/config` (a resposta devolvia a **senha SMTP em texto puro**);
- ler e sobrescrever `/whatsapp/config` (URL e chave da API Evolution);
- ler `/errorlogs` inteiro (stack traces, caminhos, dados de requisições e e-mails);
- alterar `/protocolos/configuracoes`, tipos, estrutura organizacional e alertas.

Correção: middleware `page.permission` (padrão que o próprio sistema já usa) em `routes/api.php`:
`/configuracoes/email`, `/configuracoes/whatsapp`, `/errorlogs`, `/protocolo/configuracoes`,
`/protocolo/tipos`, `/protocolo/estrutura`, `/protocolo/alertas` (ou `/sistema/alertas`).
As leituras que o formulário de novo protocolo usa (tipos, unidades) continuam abertas.
Testes: `SensitiveRoutesAuthorizationTest`.

### S2 — Cadastro público de contas — Crítico **[APLICADO]**
`POST /api/register` era público e criava usuário **ativo, com perfil `user` e token de acesso**
(limitado a 5/min por IP). O frontend e o app mobile não usam essa rota. Removida.
O método `AuthController::register` ficou sem uso e pode ser apagado.

### S3 — 305 das 419 rotas autenticadas dependem só do login — Alto **[PROPOSTO]**
Só 114 rotas têm middleware de permissão. As demais confiam no frontend esconder o menu. Na varredura
como usuário comum, **68 rotas GET responderam 200** (protocolos, laboratório, almoxarifado, alvarás,
fiscalizações, estabelecimentos, cadastros base, dashboards de conformidade/farmácia/vigilância...).
Plano sugerido, por módulo, do mais sensível ao menos:
1. Laboratório (pedidos/resultados/exames/médicos/agenda) — dados de saúde.
2. Alvarás, fiscalizações, estabelecimentos, almoxarifado (`produtos`, `estoque`, `movimentacoes`, `requisicoes`).
3. Cadastros base com escrita aberta: `medicines`, `system-notices`, `sector`, `specialities`, `routes`, `vehicles`, `models`, `qrcode-logs`.
4. Ofícios/portarias (`letters`, `ordinances`): hoje qualquer usuário lista **todos** os documentos.
5. Dashboards: `Gate` por dashboard só existe em 7 dos 11.

Cada grupo precisa do mapeamento rota → página (`page.permission`) e uma validação com o perfil real
de cada área antes de ativar, para não bloquear quem hoje usa por hábito.

### S4 — Ignition/debug — Baixo **[PROPOSTO]**
A lista de rotas mostra o middleware `RunnableSolutionsEnabled` (Ignition). Confirmar `APP_DEBUG=false`
no servidor de produção.

---

## 2. Desempenho e escala (100 → 300 usuários)

### P1 — Chat: consultas em excesso e trabalho síncrono — Alto **[APLICADO]**
- `GET /chat/users`: uma consulta por usuário (`canUseChat()`) e escrita no banco a cada leitura.
  Agora 3 consultas fixas (teste garante que não cresce com o nº de usuários).
- `GET /chat/conversations`: `limit(1)` global no eager load fazia **só uma conversa** receber a
  última mensagem, mais um `COUNT` por conversa. Agora 3 consultas fixas e cada conversa traz a sua.
- Configuração do chat lida 6–10 vezes por requisição (`Schema::hasTable` + `first()` a cada limitador
  e a cada broadcast): agora memoizada por requisição.
- Presença: o heartbeat comum só atualiza `last_seen_at`; estatística, pico e broadcast só quando o estado muda.
- Contadores diários: `firstOrCreate + increment` (3 consultas) numa linha disputada por todos →
  uma instrução atômica.
- Envio ao tempo real (HTTP para Pusher/Soketi, `ShouldBroadcastNow`) e alertas de mensagem passam a
  rodar **depois** da resposta (`App\Support\AfterResponse`).
- Índices: `chat_messages(conversation_id, read_at, sender_id)` e `chat_connections(last_seen_at)`.

### P2 — Chat no frontend: ~5 requisições por mensagem — Alto **[APLICADO]**
Cada mensagem disparava 3 GET (usuários, conversas, não lidas) + entrega + leitura. Agora o estado é
atualizado pelo conteúdo do próprio evento (funções puras em `chatState.js`, com testes):
- mensagem de conversa conhecida: 0 GET; conversa aberta: só o "lido" (1 POST); fechada: só o "entregue";
- usuários só são buscados ao abrir o painel (mín. 30 s entre buscas) e a presença chega por evento;
- total de não lidas derivado da lista (sem `/chat/unread`);
- reconciliação de segurança a cada 2 min (20 s se o tempo real estiver inativo), só com a aba visível;
- "ausente" só após 20 s com a aba oculta (evita rajada de eventos de presença);
- sino do Protocolo e atalho do Kanban não consultam com a aba oculta.

### P3 — Alertas WhatsApp/e-mail síncronos (timeout de 25 s) — Alto **[APLICADO]**
`SystemAlertService::dispatch` roda em 17 pontos e a notificação de protocolo dentro da transação.
Uma API de WhatsApp lenta segurava um processo PHP por envio (× destinatários). Agora rodam após a
resposta (`AfterResponse`). Testes: `AlertDeferralTest`.

### P4 — Sanctum grava `last_used_at` em toda requisição — Médio **[APLICADO]**
Um `UPDATE` por chamada autenticada. `App\Models\PersonalAccessToken` só grava se o valor tiver mais de
5 min.

### P5 — Erros 429 gravavam log completo (efeito bola de neve) — Médio **[APLICADO]**
Cada requisição barrada gravava `ErrorLog` com trace, aumentando a escrita justo na sobrecarga.
Agora no máximo 1 registro por minuto por usuário/IP e rota.

### P6 — Limites de requisição — Médio **[APLICADO]**
Limite geral por usuário 300 → 600/min; por IP (não autenticado) 60 → 240/min (uma prefeitura sai por
um único IP); `chat-sync` 120 → 300/min (linhas ainda no padrão antigo migram; valores customizados
pelo administrador são preservados). `CACHE_LIMITER` permite apontar o limitador para Redis/Memcached.

### P7 — `env()` fora de `config/` — Alto **[APLICADO]**
`env()` retorna `null` com `php artisan config:cache`. Havia 48 usos (modelo da OpenAI, banco do e-SUS,
credenciais Pusher de fallback). Migrados para `config/monitor_aps.php`, `config/chat.php` e
`config/openai.php`. Sem isso o servidor não podia usar `config:cache`.

### P8 — `UserController::index` com N+1 — Médio **[APLICADO]**
Uma consulta por usuário para `can_chat`. Agora usa o mesmo mapa perfil→chat do chat.

### P9 — Listagens sem paginação — Alto **[PROPOSTO]**
Retornam a tabela inteira: `letters` (com textos longos), `ordinances`, `qrcode-logs`, `models`,
`kanban`, `trips`, `agenda-coleta`, `queue-treatment-plans`. (`errorlogs` já é paginado, 50 por página,
mas não tem retenção.) Proposta: `paginate()` + filtros no servidor e, em paralelo, ajuste das telas
correspondentes, que hoje paginam no cliente — por isso **não foi aplicado**: mudar só o backend
quebraria as telas. Também sugerida uma rotina agendada de expurgo de `error_logs`/`logs` antigos.

### P10 — Consultas não indexáveis — Médio **[PROPOSTO]**
`TripController::index` usa `whereYear/whereMonth/whereDay(departure_date)`, que impedem o uso de
índice. Trocar por faixa (`whereBetween`) e indexar `departure_date`.

### P11 — Sem fila (`QUEUE_CONNECTION=sync`) — Médio **[PROPOSTO]**
`AfterResponse` tira a espera do usuário, mas continua ocupando o processo do PHP. O ideal é fila em
banco (`QUEUE_CONNECTION=database` + `php artisan queue:work` supervisionado) para WhatsApp, e-mail,
PDFs e principalmente a geração por IA (`LetterController`/`OrdinanceController` chamam a OpenAI de
forma síncrona).

### P12 — Cache — Médio **[PROPOSTO]**
- `CACHE_DRIVER=file`: com centenas de usuários vira disputa de disco; usar Redis (ou banco) e `CACHE_LIMITER`.
- Dashboards: 8 dos 11 já tinham `Cache::remember`; **[APLICADO]** cache de 60 s em `almoxarifado` e
  `arquivo` (`DashboardCacheTest`). O dashboard `chat` é só de administrador e ficou como está.
- `/auth/my-permissions` **não** foi colocado em cache de propósito: uma revogação de permissão só
  valeria depois do TTL, e o ganho é pequeno (1 consulta de perfil por carga de página).
- Painéis públicos consultados por TVs a cada 10–60 s (`attendance/panel/state`, `public/painel-esus/*`,
  `public/pharmacy/*`): cache de 5–10 s no servidor.
- `KanbanShortcut` baixa a lista inteira só para contar: criar `GET /kanban/count`.

### P13 — Infraestrutura — Alto **[PROPOSTO]**
- **Pusher** (só afeta quem usa o chat): o limite padrão é de 100 conexões simultâneas (plano gratuito). Cada usuário com o chat aberto ocupa uma conexão; usuários sem acesso ao chat não conectam. Se mais de 100 usuários tiverem o chat aberto ao mesmo tempo, é preciso plano maior ou Soketi próprio.
- PHP-FPM: `pm.max_children` dimensionado para os usuários simultâneos; OPcache ligado.
- Deploy: `php artisan config:cache route:cache event:cache` e `composer install --optimize-autoloader`.
- MySQL: `max_connections`, `innodb_buffer_pool_size` e slow query log ligado.

---

## 3. Código morto e erros

### E1 — Módulo de chamadas legado quebrado — Alto **[PROPOSTO]**
As rotas `rooms`, `calls`, `services`, `endedcalls` (`RoomController`, `CallController`,
`CallServiceController`, `EndedController` e models) apontam para tabelas removidas pela migration
`2026_05_17_230134_drop_attendance_tables` e respondem **500**; `EndedController` importa
`App\Model\EndedCall` (namespace inexistente). O frontend não os usa. Remover rotas, controllers e models.

### E2 — e-SUS indisponível: 500 e 503 misturados — Baixo **[PROPOSTO]**
Alguns endpoints do Monitor APS devolvem 503 e outros 500 (`visitas/evolucao`, `config/equipes`,
`config/explorar`). Padronizar 503 com mensagem clara no `MonitorApsBaseController`.

### E3 — `SystemAlertService` (`aprovadores_almoxarifado`) — Baixo **[PROPOSTO]**
Carrega todos os usuários ativos e faz uma consulta por usuário para checar permissão. Usar mapa de perfis.

---

## 4. Padrões do projeto

### D1 — Validação inline — Médio **[PROPOSTO]**
O `CLAUDE.md` exige `FormRequest` (`App\Http\Requests\*Request`), mas há ~100 chamadas `validate()` /
`Validator::make` dentro de controllers (Protocol 9, Attendance 9, VisitaAcs 6, Letter 5, Chat 5...).
Migrar aos poucos, começando pelos controllers que mais mudam.

### D2 — Controllers gigantes — Médio **[PROPOSTO]**
`VisitaAcsController` (2155 linhas), `MonitorApsController` (1799), `DashboardController` (1171),
`ProtocolController` (1041), `PainelEsusController` (826). Extrair consultas para services/queries.

### D3 — Middleware sem efeito — Baixo **[PROPOSTO]**
`LogUserAction` está no grupo `api` mas só age em `terminate` para logout; pode sair do grupo.

---

## 5. Como conferir

- Backend: `./vendor/bin/phpunit` → 331 testes, 2 pulados (dependem do PostgreSQL do e-SUS).
- Frontend: `npx jest` → 77 testes.
- Antes do deploy: rodar a migration `2026_09_29_000001_tune_chat_for_high_concurrency`.
- Depois do deploy: `php artisan config:clear && php artisan config:cache`.
