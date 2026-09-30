# Checklist de infraestrutura — de ~100 para 300 usuários

Itens de **servidor**: o código já está preparado (cache, limitadores e trabalho adiado usam configuração), mas o ganho só aparece quando o ambiente é ajustado. Ordem sugerida = maior impacto primeiro.

## 1. Cache e limitadores (maior impacto)

| Item | Hoje (`.env.example`) | Recomendado | Por quê |
|---|---|---|---|
| `CACHE_DRIVER` | `file` | `redis` (ou `memcached`) | Cache de dashboards, `recordViewOncePer` e presença do chat leem/escrevem a cada requisição; `file` serializa em disco. |
| `CACHE_LIMITER` | vazio (usa o store padrão) | `redis` (ou `array` em servidor único de baixo tráfego) | O `throttle` consulta o store a cada requisição autenticada. |
| `SESSION_DRIVER` | `file` | `redis` ou `cookie` | A API é por token; sessão só importa para rotas web. |
| `REDIS_*` | — | host/porta/senha do Redis | Necessário para os três itens acima. |

Depois de trocar: `php artisan config:cache && php artisan cache:clear`.

## 2. PHP-FPM / servidor web

- `pm = dynamic` (ou `static`) com `pm.max_children` dimensionado pela memória: `(RAM livre) / (memória média por processo, ~60–100 MB)`.
- `pm.max_requests = 500` para reciclar processos.
- OPcache ligado: `opcache.enable=1`, `opcache.memory_consumption=256`, `opcache.validate_timestamps=0` em produção (reiniciar o FPM a cada deploy).
- `php artisan config:cache`, `route:cache`, `view:cache` e `composer install --no-dev --optimize-autoloader` a cada deploy.
- Nginx/Apache: `keepalive`, gzip para `application/json`, `fastcgi_read_timeout` ≥ 60 s.

## 3. Banco de dados (MySQL)

- `innodb_buffer_pool_size` ≈ 50–70% da RAM dedicada ao banco.
- `max_connections` ≥ `pm.max_children` + folga (jobs, artisan, backup).
- Slow query log ligado (`long_query_time = 1`) na primeira semana com 300 usuários; ver as consultas de `audit_logs`, `protocolos`, `attendance_*`.
- Rotina de retenção: `error_logs` e `audit_logs` crescem sem limite (ver T14 — retenção de `error_logs`). Enquanto não houver, agendar limpeza manual/mensal.
- Backup diário testado (restaurar em base de teste ao menos uma vez).

## 4. Tempo real (chat)

- `BROADCAST_DRIVER=pusher`. O sistema limita por configuração: `PUSHER_CONNECTION_LIMIT` (padrão **100**) e `PUSHER_DAILY_MESSAGE_LIMIT` (padrão 200 000). **Com 300 usuários, o plano do Pusher precisa cobrir as conexões simultâneas reais** (nem todos usam o chat, mas quem abre o sistema conecta); ajuste esses dois valores ao plano contratado ou migre para Soketi/Reverb próprio.
- `CHAT_DEFER_BROADCAST` vazio = em produção (web) o envio ao Pusher já roda **depois** da resposta HTTP; não forçar `false`.
- O front já reduz polling quando o WebSocket está conectado; manter as rotas `chat-*` no limitador dedicado.

## 5. Fila e trabalho em segundo plano

- Hoje `QUEUE_CONNECTION=sync`. O envio de WhatsApp/e-mail e a gravação de auditoria já são adiados por `AfterResponse` (depois da resposta, no mesmo processo), então **não há fila obrigatória**.
- Se o volume de avisos crescer ou o envio externo ficar lento: `QUEUE_CONNECTION=redis`, `php artisan queue:work --tries=3` sob Supervisor/systemd, e mover os envios de `AfterResponse` para jobs.
- Agendador: cron `* * * * * php /caminho/artisan schedule:run` (relatórios, limpezas).

## 6. Variáveis de ambiente novas deste lote

| Variável | Uso |
|---|---|
| `FRONTEND_URL` | Link da consulta pública enviado no aviso de WhatsApp da vigilância. |
| `MODEL` | Modelo da OpenAI para ofícios/portarias (deixar definido). |

## 7. Migrações a rodar no deploy (ordem)

`2026_09_29_000001`, `2026_09_29_100000`, `2026_09_29_110000`, `2026_09_30_000001`, `000002`, `000003`, `2026_09_30_100000`, `100001`, `100002`, `2026_09_30_120000`, `2026_09_30_130000` — via `php artisan migrate --force`.

## 8. Verificação depois do deploy

1. `php artisan route:list` sem erro e `php artisan config:cache` ok.
2. Login com um usuário comum e abrir as telas do seu perfil (as rotas agora exigem a página liberada em **Perfis** — ver `config/route_permissions.php`).
3. Painel de atendimento à meia-noite e à 01h (janela do dia em Brasília).
4. Nova fiscalização/denúncia gera aviso no WhatsApp dos contatos ativos em **Vigilância > Configurações**.
5. Acompanhar `storage/logs` e o slow query log por 48 h.

## 9. Riscos conhecidos

- O aplicativo móvel `sysdocmobile` **não foi verificado** contra as rotas que passaram a exigir página liberada (T11): se algum endpoint usado por ele agora responde 403, liberar a página correspondente ao perfil ou acrescentá-la à regra em `config/route_permissions.php`.
- Listagens sem paginação no servidor (T14) ainda carregam tudo: com 300 usuários e base maior, lentidão nessas telas é esperada até a paginação ser feita.
