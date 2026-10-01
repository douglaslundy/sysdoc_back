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
| P6 | Página pública /petition (+ /petition/track, redirects, API /public/petitions, origem `peticao`, motivo_id) | **CONCLUÍDA** |
| P7 | Kanban: card por petição na unidade do motivo (unit_id, fiscalizacao_id) | **CONCLUÍDA** |

## Fora do escopo
- Sincronizar status do card com a fiscalização.
- Alterar fiscalizações internas.

## Rulings / log
- P1-P3 concluídas: Voltar na estrutura, coluna Criado em (colSpan 7), modal com árvore completa + usuarios-elegiveis estrito por lotação ativa (unidade + descendentes). Teste antigo 'mesmo sem lotação' invertido conforme decisão. 'Encaminhar' não usa esse endpoint (não alterado). back 499, front 177.
- P4 concluída: update da fiscalização registra 'observacao' (texto da obs alterada; público só com a caixa), situação continua automática; removidos POST /movimentacoes, StoreFiscalizacaoMovimentacaoRequest e o campo mensagem_publica (linhas antigas 'mensagem_publica' seguem legíveis no histórico). Regra: a caixa só publica quando a obs muda naquela gravação. back 501, front 180.
- P5 concluída: migration 2026_10_01_100000 (peticao_motivos + página /peticao-motivos em system_pages, idempotente), CRUD /api/peticao-motivos (regra route.pages '/peticao-motivos'; admin sempre), GET público /api/public/petition/reasons (só ativos, sem unit_id), auditoria do model; front: MotivosPeticao + pages/peticao-motivos.js + menu Vigilância. Exclusão livre (motivo_id das fiscalizações será nullOnDelete em P6). back 506, front 183. Deploy: rodar migrate e liberar a página em Perfis.
- P6 concluída: migration 2026_10_01_110000 (fiscalizacoes.motivo_id nullOnDelete); POST /api/public/petitions e /consulta (rotas /public/denuncias mantidas); motivo obrigatório só se houver motivo ativo; consulta devolve 'motivo'; aviso WhatsApp 'Nova petição' com Motivo; url_consulta -> /petition/track. Front: pages/petition.js, petition/track.js, services/peticaoPublica.js, redirects 301 /denuncia(/consulta) em next.config.js, rotas públicas (publicPaths + _app). Ruling: valor de origem no banco continua 'denuncia' (só os rótulos mudaram) — evita migrar dados/quebrar dashboards e app móvel; custo se errado: rotular 'peticao' depois exige migração. Correção extra: change() do formulário lia target.value tardiamente. back 514, front 189.
- P7 concluída: migration 2026_10_01_120000 (kanban_tasks.unit_id + fiscalizacao_id); petição com motivo de unidade cria card público restrito (lotados na unidade/ancestrais da lotação + admin; update/destroy 403 para os demais); card sem dados do denunciante; FiscalizacaoObserver::deleted apaga o card (Fiscalizacao usa SoftDeletes, cascade do banco não dispara); UnitTree compartilhada (ProtocolController usa). Front: PeticaoChips no card, botão 'Abrir fiscalização' -> /fiscalizacoes?busca=PROTOCOLO (lista lê ?busca=). back 520, front 195.

## Deploy do Lote 4
Migrations novas (ordem): 2026_10_01_100000_create_peticao_motivos_table, 2026_10_01_110000_add_motivo_id_to_fiscalizacoes, 2026_10_01_120000_add_unit_and_fiscalizacao_to_kanban_tasks. Depois: `php artisan migrate --force`, `config:cache`; liberar a página 'Motivos de Petição' (/peticao-motivos) em Perfis a quem for cadastrar; cadastrar os motivos (Denúncia, Solicitar vistoria...) com a unidade responsável; cadastrar a lotação dos usuários em /protocolo/estrutura (usuários de destino e cards do kanban dependem dela).

## Origem gravada como `peticao` (2026-10-01)
Decisão do usuário: gravar `peticao` de fato. Migration `2026_10_01_130000_rename_origem_denuncia_to_peticao` converte as linhas antigas (`denuncia` -> `peticao`; `down` reverte). Código, rótulos ("Petição") e testes atualizados; o filtro `?origem=denuncia` ainda é aceito e tratado como `peticao` (compatibilidade). back 520 / front: testes de fiscalizações 18 ok. Deploy: rodar a migration depois das três do Lote 4.
