# Lote 2026-09-29 (2) — controle de tarefas

Arquivo de estado: **atualizar a cada tarefa iniciada/concluída** (economiza contexto; retomar daqui).
Repositórios: `sysdoc_back` (Laravel) e `sysdoc_front` (Next). Branch de trabalho: `feat/lote-2` em ambos.
Regra: cada tarefa termina com testes da função alterada + dos módulos que dependem dos arquivos
alterados + suíte completa antes de marcar como concluída.

| # | Tarefa | Status | Notas |
|---|--------|--------|-------|
| T1 | /queue realizados: nome de quem deu baixa, hora na baixa, datepicker (padrão dia 1 do mês → hoje) | **CONCLUÍDA** (back 344 testes / front 87) | done_by novo; filtro date_from/date_to sobre done_at |
| T2 | Painel público de medicamentos grava log por requisição | pendente | origem: MedicineTransparencyService::AuditService::record (3 pontos) |
| T3 | Histórico do cidadão (drawer lateral, mais recente em cima) | pendente | depende de T5 (cobertura de auditoria) |
| T4 | /protocolo/[id] botão voltar + ícone de anexo na caixa de entrada | pendente | inbox precisa de attachments_count |
| T5 | Auditoria: CRUD completo + ações especiais em todos os endpoints, com desempenho | pendente | levantar lacunas, corrigir |
| T6 | Baixa da fila não sai da fila + mensagem de erro clara e persistente | pendente | modal de erro só fecha no botão |
| T7 | Fiscalizações: nº de protocolo, histórico de movimentação, PDF interno | pendente | |
| T8 | Denúncia pública + consulta por protocolo/senha + PDF do cidadão | pendente | depende de T7 |

## Decisões / premissas
- Item 1 do pedido veio truncado ("preciso que informe o / ver nome de quem deu baixa"): interpretado como
  "mostrar o nome de quem deu a baixa" na listagem dos realizados.
- Sem subagentes (regra do projeto: só se o usuário pedir).
- **Regra nova do usuário:** usar a skill `superpowers:brainstorming` sempre que necessário (T3, T5, T7, T8 = desenho novo).

## Log de execução
(preencher abaixo, mais recente em cima)
- T1 concluída: migration `2026_09_29_110000_add_done_by_to_queue_table` (done_by), filtro date_from/date_to em done_at, resource com done_by_user, tela com Baixa por/hora/datepickers (padrão dia 1 do mês → hoje). Testes: QueueDoneByAndRangeTest, tests/queue/*.
