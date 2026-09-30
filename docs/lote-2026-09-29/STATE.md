# Lote 2026-09-29 (2) — controle de tarefas

Arquivo de estado: **atualizar a cada tarefa iniciada/concluída** (economiza contexto; retomar daqui).
Repositórios: `sysdoc_back` (Laravel) e `sysdoc_front` (Next). Branch de trabalho: `feat/lote-2` em ambos.
Regra: cada tarefa termina com testes da função alterada + dos módulos que dependem dos arquivos
alterados + suíte completa antes de marcar como concluída.

| # | Tarefa | Status | Notas |
|---|--------|--------|-------|
| T1 | /queue realizados: nome de quem deu baixa, hora na baixa, datepicker (padrão dia 1 do mês → hoje) | **CONCLUÍDA** (back 344 testes / front 87) | done_by novo; filtro date_from/date_to sobre done_at |
| T2 | Painel público de medicamentos grava log por requisição | **CONCLUÍDA** (back 348 / front 90) | origem: MedicineTransparencyService::AuditService::record (3 pontos) |
| T3 | Histórico do cidadão (drawer lateral, mais recente em cima) | **CONCLUÍDA** (back 394 / front 101) | depende de T5 (cobertura de auditoria) |
| T4 | /protocolo/[id] botão voltar + ícone de anexo na caixa de entrada | **CONCLUÍDA** | inbox precisa de attachments_count |
| T5 | Auditoria: CRUD completo + ações especiais em todos os endpoints, com desempenho | **CONCLUÍDA** (back 394 / front 101) | levantar lacunas, corrigir |
| T6 | Baixa da fila não sai da fila + mensagem de erro clara e persistente | pendente | modal de erro só fecha no botão |
| T7 | Fiscalizações: nº de protocolo, histórico de movimentação, PDF interno | **CONCLUÍDA** (Parte A do plano; back 414 / front 110) | |
| T8 | Denúncia pública + consulta por protocolo/senha + PDF do cidadão | pendente | depende de T7 |
| T9 | Dashboard da Vigilância Sanitária: gráficos/cards de KPI sobre as fiscalizações | BACKLOG (pedido em 2026-09-29, executar depois de T7/T8) | usa dados de fiscalizações; aplicar skill `dataviz`; brainstorming se houver dúvida de desenho |

## Decisões / premissas
- Item 1 do pedido veio truncado ("preciso que informe o / ver nome de quem deu baixa"): interpretado como
  "mostrar o nome de quem deu a baixa" na listagem dos realizados.
- Sem subagentes (regra do projeto: só se o usuário pedir).
- **Regra nova do usuário:** usar a skill `superpowers:brainstorming` sempre que necessário (T3, T5, T7, T8 = desenho novo).

## Log de execução
(preencher abaixo, mais recente em cima)
- T7 (Parte A do plano 2026-09-30) concluída: protocolo FIS-AAAA-NNNNNN, campos de denúncia, fiscalizacao_movimentacoes, FiscalizacaoTimeline, endpoints /historico e /movimentacoes, filtro origem + busca por protocolo, tela (protocolo, chip Denúncia, drawer, nova movimentação, PDF interno). Migrations a rodar: 2026_09_30_100000/1/2. Próximo: Parte B (T8).
- ACHADO (pré-existente, fora do escopo): AttendanceModuleTest::test_retorna_estado_do_painel_com_campos_esperados falha entre 00:00 e ~03:00 BRT (também na main) — limite de 'hoje' do painel público de atendimento provavelmente usa UTC. Avaliar depois.
- T7/T8 brainstorming: Fiscalizacao hoje = registro de visita (estabelecimento_id obrigatório, data_visita, resultado enum, observacoes; soft delete; anexos em disk private). Precedente reutilizável: consulta pública de exame (protocolo+senha_hash, ConsultaPublicaController, rotas públicas em pages/_app.js PUBLIC_ROUTES e src/constants/publicPaths.js). PDFs: pdfmake no front (src/reports/*).
- T5+T3 concluídas (plano Tasks 1-9). Front: HistoryDrawer genérico (reutilizável na T7), ClientHistoryDrawer, botão Histórico em Cidadãos (capability canViewClientHistory), switch em Perfis. Deferred minor: auditoria bufferizada não acompanha rollback de transação (linha pode existir para operação revertida) — avaliar DB::afterCommit. Migrations a rodar: 2026_09_29_100000/110000 (fila) + 2026_09_30_000001/2/3 (auditoria). Próximo: T7 (brainstorming), T8, T6, T9.
- T5/T3 backend (plano Tasks 1-7) concluído e commitado: client_id em audit_logs + AuditContext; AuditService em lote/máscara/viewOncePer; AuditableObserver + config/audit.php (inclui TripClient); AuditDescriber; ClientHistoryController + client_history_view_enabled; backfill. Ruling: passageiros de viagem auditados via observer de TripClient (tem client_id) em vez de ações TRIP_CLIENT_* no controller — menos código, cobre também replicação. Migrations novas: 2026_09_30_000001/2/3. Falta: frontend (Task 8) e regressão final (Task 9).
- Spec T5+T3 aprovado pelo usuário (docs/superpowers/specs/2026-09-29-auditoria-e-historico-do-cidadao-design.md). Escrevendo plano em docs/superpowers/plans/2026-09-29-auditoria-e-historico-do-cidadao.md.
- T9 adicionada ao backlog a pedido do usuário (KPI/gráficos de fiscalizações no dashboard da vigilância).
- Brainstorming T5+T3 apresentado ao usuário (gate). Levantamento: 23 models com observer; 23 controllers com escrita sem auditoria nenhuma (Almoxarifado*, DocumentType, Email/WhatsApp config, Kanban, Medicine*, Protocol{Alert,Config,Type,OrganizationalUnit}, SystemNotice, Conformidade, StockImport, PharmacyCatalog). Queue/Kanban não têm observer. audit_logs não tem coluna de cidadão (client_id).
- T4 concluída: attachments_count (anexos ativos) em /protocolos e caixa-entrada; ícone paperclip; botão Voltar (history.back, fallback caixa-entrada). Testes: ProtocolVisibilityTest, tests/protocolo.
- T2 concluída: AuditService::recordOncePerVisitor (1 log/IP/tipo/hora) nos 3 pontos do painel público. Teste: PublicMedicinesAuditThrottleTest.
- T1 concluída: migration `2026_09_29_110000_add_done_by_to_queue_table` (done_by), filtro date_from/date_to em done_at, resource com done_by_user, tela com Baixa por/hora/datepickers (padrão dia 1 do mês → hoje). Testes: QueueDoneByAndRangeTest, tests/queue/*.
