# Lote 4 — Protocolo (ajustes) e Petições públicas

Branch: `feat/lote-4` (back e front). Aprovado pelo usuário em 2026-09-30 após brainstorming.

## Decisões do usuário
- Usuários do novo protocolo/encaminhar: **somente lotados** na unidade escolhida ou em suas subunidades (lista vazia se ninguém).
- Histórico da fiscalização: **automático + uma caixa** "visível ao denunciante" junto do campo de obs; sem campo de movimentação manual.
- URL pública: **/petition** (acompanhamento em **/petition/track**); `/denuncia` e `/denuncia/consulta` redirecionam.
- Kanban: **card direto na unidade** (`kanban_tasks.unit_id` + `fiscalizacao_id`), visível a quem está lotado na unidade.

## Tarefas

| # | Tarefa | Status |
|---|--------|--------|
| P1 | /protocolo/estrutura: botão Voltar | **CONCLUÍDA** |
| P2 | Listagem de protocolos: coluna "Criado em" | **CONCLUÍDA** |
| P3 | Novo protocolo: select com toda a árvore de unidades + usuários lotados (estrito) | **CONCLUÍDA** |
| P4 | Fiscalização: histórico automático + caixa "visível ao denunciante"; remove movimentação manual | **CONCLUÍDA** |
| P5 | Motivos de petição: tabela + CRUD + página /peticao-motivos (admin + permissão em Perfis) | **CONCLUÍDA** |
| P6 | Página pública /petition (+ /petition/track, redirects, API /public/petitions, origem `peticao`, motivo_id) | pendente |
| P7 | Kanban: card por petição na unidade do motivo (unit_id, fiscalizacao_id) | pendente |

## Fora do escopo
- Sincronizar status do card com a fiscalização.
- Alterar fiscalizações internas.

## Rulings / log
- P1-P3 concluídas: Voltar na estrutura, coluna Criado em (colSpan 7), modal com árvore completa + usuarios-elegiveis estrito por lotação ativa (unidade + descendentes). Teste antigo 'mesmo sem lotação' invertido conforme decisão. 'Encaminhar' não usa esse endpoint (não alterado). back 499, front 177.
- P4 concluída: update da fiscalização registra 'observacao' (texto da obs alterada; público só com a caixa), situação continua automática; removidos POST /movimentacoes, StoreFiscalizacaoMovimentacaoRequest e o campo mensagem_publica (linhas antigas 'mensagem_publica' seguem legíveis no histórico). Regra: a caixa só publica quando a obs muda naquela gravação. back 501, front 180.
- P5 concluída: migration 2026_10_01_100000 (peticao_motivos + página /peticao-motivos em system_pages, idempotente), CRUD /api/peticao-motivos (regra route.pages '/peticao-motivos'; admin sempre), GET público /api/public/petition/reasons (só ativos, sem unit_id), auditoria do model; front: MotivosPeticao + pages/peticao-motivos.js + menu Vigilância. Exclusão livre (motivo_id das fiscalizações será nullOnDelete em P6). back 506, front 183. Deploy: rodar migrate e liberar a página em Perfis.
