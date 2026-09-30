# Auditoria completa e histórico do cidadão — desenho

Data: 2026-09-29 · Tarefas do lote: T5 (auditoria) + T3 (histórico do cidadão)
Base: `docs/lote-2026-09-29/STATE.md`

## 1. Objetivo
1. Toda criação, edição, exclusão e ação relevante do sistema fica registrada em `audit_logs`, sem deixar o
   sistema mais lento.
2. Tela de **Histórico do cidadão**: painel lateral (mesmo padrão de Movimentação em protocolo/documentos),
   do mais recente para o mais antigo, mostrando **quem**, **quando** e **o que** foi feito com/sobre o
   cidadão (visualizou, editou, entrou na fila, deu baixa, viagem, relatório, exames...).
3. O mesmo painel é reutilizado na Fiscalização (T7).

## 2. Decisões do usuário (2026-09-29)
| Tema | Decisão |
|------|---------|
| Quem vê o histórico do cidadão | **Permissão própria por perfil** (nova coluna em `access_profiles`, editável em Perfis; admin sempre vê) |
| Registros antigos | **Entram**: vínculo com o cidadão preenchido retroativamente onde for possível (cadastro, fila, pedidos de exame, planos de tratamento) |
| Visualização do cadastro | **1 registro a cada 10 min por usuário e cidadão** |
| Cobertura | **Todas as áreas sem auditoria hoje** |

## 3. Situação atual (levantamento)
- 23 models têm observer; 25 controllers chamam `AuditService::record`.
- **23 controllers com escrita sem auditoria nenhuma**: Almoxarifado (catálogos, produtos, configuração,
  requisições), tipos de documento, configuração de e-mail e de WhatsApp, Kanban, medicamentos (itens, status
  diário, aquisições, painel, publicações, importação, catálogos), protocolo (alertas, configuração, tipos,
  unidades), avisos do sistema, conformidade do cidadão, modelos.
- `Queue` (fila) não tem observer; a auditoria dela está espalhada no controller.
- `audit_logs` não sabe de qual cidadão cada linha trata → impossível montar o histórico com uma consulta simples.

## 4. Abordagem escolhida
**Observer genérico + coluna `client_id` denormalizada em `audit_logs`** (descartadas: tabela paralela só do
cidadão — grava duas vezes e diverge; e montar o histórico juntando tabelas na consulta — lento e frágil).

### 4.1 Componentes
| Unidade | Responsabilidade | Depende de |
|---------|------------------|------------|
| `config/audit.php` | Lista de models auditados automaticamente, campos ignorados e mascarados | — |
| `App\Observers\AuditableObserver` | `created/updated/deleted` genérico: grava só os campos alterados (`old`/`new`), ignora mudança só de timestamps | `AuditService` |
| `App\Services\Audit\AuditContext` | Descobre o `client_id` de um model (Client→id; Queue→`id_client`; PedidoExame/QueueTreatmentPlan→`client_id`; Addresses→`id_client`; ResultadoExame→pedido→cliente; anexos da fila→fila→cliente) | models |
| `AuditService` (alterações) | `record()` passa a gravar `client_id`; **buffer em memória e inserção única em lote ao fim da requisição** (`AfterResponse`), imediata em console/testes; `recordViewOncePer()` para leituras; máscara ampliada (`password`, `token`, `secret`, `api_key`, `smtp_password`, `app_secret`...) | `AuditContext` |
| `App\Services\Audit\AuditDescriber` | Converte uma linha bruta em texto para humanos: título (“Editou o cadastro”, “Deu baixa na fila de Fisioterapia”) + detalhe (“nome: A → B”) | — |
| `ClientHistoryController@index` | `GET /api/clients/{client}/historico` paginado (30), mais recente primeiro; exige a permissão nova | `audit_logs`, `AuditDescriber` |
| Frontend `HistoryDrawer` (genérico) | Painel lateral reutilizável: recebe itens `{id, titulo, detalhe, usuario, data}` | MUI |
| Frontend `ClientHistoryDrawer` | Busca o endpoint e alimenta o `HistoryDrawer`; botão “Histórico” no cadastro do cidadão | `HistoryDrawer` |

### 4.2 Banco
- `audit_logs`: `client_id` (nullable), índice `(client_id, id)`; migration com backfill em lotes (Client, Queue,
  Addresses, PedidoExame, QueueTreatmentPlan). Registros sem vínculo derivável ficam `NULL`.
- `access_profiles`: `client_history_view_enabled` (boolean, padrão false), seguindo o padrão de
  `client_trips_view_enabled`; exposto em `/auth/my-permissions` (`capabilities.client_history_view`) e no
  formulário de Perfis.

### 4.3 Fluxo de dados
1. Ação do usuário → observer (ou chamada explícita) → `AuditService::record()` → linha no buffer com `client_id`.
2. Fim da requisição → 1 `INSERT` em lote de todas as linhas do buffer (sem atrasar a resposta).
3. Tela → `GET /clients/{id}/historico?page=n` → linhas por `client_id` → `AuditDescriber` → JSON → drawer.

### 4.4 Desempenho
- 1 insert em lote por requisição (em vez de 1 por evento), depois da resposta.
- Leitura de cadastro gravada 1x/10 min por usuário+cidadão (`Cache::add`).
- Nenhum registro para ruído: presença, digitando, heartbeats, mensagens de chat (já auditadas à parte).
- Histórico: consulta indexada e paginada; nunca carrega a tabela inteira.

### 4.5 Erros e segurança
- Falha ao gravar auditoria **nunca** derruba a requisição (já é o comportamento; mantido e testado).
- Campos sensíveis mascarados antes de gravar; e-mail/WhatsApp registram “senha/chave alterada”, sem valor.
- Endpoint do histórico: 403 sem a permissão; 404 para cidadão inexistente.

## 5. Ações do cidadão que devem aparecer
Visualizou cadastro · editou/criou/inativou · inseriu na fila · deu baixa/reabriu/excluiu da fila · anexos da
fila · plano e sessões de tratamento · inseriu/removeu/confirmou em viagem · pedido/resultado de exame ·
relatório do cidadão consultado · conformidade aplicada.

## 6. Testes
- Observer genérico: criar/editar/excluir grava 1 linha com diff correto; só timestamps → nada; segredos mascarados.
- `AuditContext`: `client_id` para cada tipo de model.
- Buffer: várias ações na mesma requisição → 1 insert; falha de gravação não quebra a requisição.
- Dedupe da visualização (10 min); expiração.
- Endpoint: paginação, ordem, permissão (403), cidadão inexistente (404), texto do `AuditDescriber`.
- Backfill: linhas antigas de Client/Queue recebem `client_id`.
- Front: drawer (ordem, vazio, erro, paginação), botão só com permissão.
- Regressão: suíte completa dos dois repositórios.

## 7. Fora de escopo (YAGNI)
Exportar/imprimir o histórico do cidadão; filtros avançados no drawer; retenção/expurgo da auditoria
(sugerido no relatório da auditoria de 29/09, tarefa separada).
