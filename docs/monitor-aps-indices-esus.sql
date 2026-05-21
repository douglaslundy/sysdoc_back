-- =============================================================================
-- MONITOR APS — Índices recomendados para o banco eSUS PEC DW
-- =============================================================================
-- Execute contra o banco PostgreSQL do eSUS PEC (esus / public).
-- Todos os comandos usam CONCURRENTLY — não bloqueiam leitura/escrita,
-- mas requerem conexão fora de bloco de transação (sem BEGIN).
-- Rode em horário de baixo uso; cada CREATE CONCURRENTLY pode demorar minutos.
-- Estes índices são SOMENTE DE LEITURA para o Monitor APS — o eSUS continua
-- gerenciando seus próprios dados normalmente.
-- =============================================================================

-- Verifique antes quais índices já existem:
-- SELECT indexname, tablename, indexdef FROM pg_indexes
-- WHERE schemaname = 'public' ORDER BY tablename, indexname;

-- =============================================================================
-- PRIORIDADE 1 — Impacto máximo (usados em TODAS as queries do Monitor APS)
-- =============================================================================

-- [T-1] tb_dim_tempo: filtro (nu_ano, nu_mes BETWEEN) em toda query de fatos
--       Sem este índice, cada join com tb_dim_tempo faz seq scan na tabela inteira.
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_dim_tempo_ano_mes
    ON tb_dim_tempo (nu_ano, nu_mes);

-- [T-2] tb_dim_equipe: lookup por nu_ine (filtro IN e WHERE de todos os endpoints)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_dim_equipe_nu_ine
    ON tb_dim_equipe (nu_ine)
    WHERE st_registro_valido = 1;

-- [T-3] tb_fat_atendimento_individual: maior tabela de fatos, acessada por ind1-7,11
--       Composite equipe+tempo elimina seq scan; inclui co_fat_cidadao_pec para
--       evitar heap fetch em JOINs com tb_fat_cad_individual.
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fai_equipe_tempo
    ON tb_fat_atendimento_individual (co_dim_equipe_1, co_dim_tempo);

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fai_cidadao
    ON tb_fat_atendimento_individual (co_fat_cidadao_pec);

-- [T-4] tb_fat_cad_individual: denominador de quase todos os indicadores
--       Composite com st_ficha_inativa evita filtro tardio sobre milhões de linhas.
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fci_equipe_ativo
    ON tb_fat_cad_individual (co_dim_equipe, st_ficha_inativa);

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fci_cidadao
    ON tb_fat_cad_individual (co_fat_cidadao_pec);

-- =============================================================================
-- PRIORIDADE 2 — Alto impacto (usados por 3+ indicadores cada)
-- =============================================================================

-- [T-5] tb_fat_visita_domiciliar: ind2 (criança), ind8 (visita ACS)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fvd_equipe_tempo
    ON tb_fat_visita_domiciliar (co_dim_equipe, co_dim_tempo);

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fvd_cidadao
    ON tb_fat_visita_domiciliar (co_fat_cidadao_pec);

-- [T-6] tb_fat_vacinacao: ind2 (criança), ind9 (vacinação), ind11 (mulher/HPV)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fv_equipe_tempo
    ON tb_fat_vacinacao (co_dim_equipe, co_dim_tempo);

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fv_cidadao
    ON tb_fat_vacinacao (co_fat_cidadao_pec);

-- [T-7] tb_fat_atendimento_odonto: ind13 (acesso bucal), ind14 (conclusão), ind15
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fao_equipe_tempo
    ON tb_fat_atendimento_odonto (co_dim_equipe_1, co_dim_tempo);

-- [T-8] tb_fat_atividade_coletiva: ind10 (interprofissional), ind15 (coletivas ESB)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fac_equipe_tempo
    ON tb_fat_atividade_coletiva (co_dim_equipe, co_dim_tempo);

-- =============================================================================
-- PRIORIDADE 3 — Otimizações adicionais por indicador
-- =============================================================================

-- [T-9] tb_fat_cad_individual: denominadores específicos por condição
--       Ind3 (gestante), Ind4 (hipertensão), Ind5 (diabetes)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fci_equipe_gestante
    ON tb_fat_cad_individual (co_dim_equipe, st_gestante, st_ficha_inativa)
    WHERE st_gestante = 1;

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fci_equipe_hipertensao
    ON tb_fat_cad_individual (co_dim_equipe, st_hipertensao_arterial, st_ficha_inativa)
    WHERE st_hipertensao_arterial = 1;

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fci_equipe_diabetes
    ON tb_fat_cad_individual (co_dim_equipe, st_diabete, st_ficha_inativa)
    WHERE st_diabete = 1;

-- [T-10] tb_fat_cad_individual: denominadores com faixa etária (ind2, ind6, ind11, ind15)
--        dt_nascimento permite filtros como "< CURRENT_DATE - INTERVAL '60 years'"
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fci_equipe_nascimento
    ON tb_fat_cad_individual (co_dim_equipe, dt_nascimento, st_ficha_inativa);

-- [T-11] tb_fat_cad_individual: denominador ind11 (mulher por gênero+faixa etária)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fci_equipe_sexo_nasc
    ON tb_fat_cad_individual (co_dim_equipe, co_dim_sexo, dt_nascimento, st_ficha_inativa)
    WHERE co_dim_sexo = 2;

-- [T-12] tb_fat_atendimento_individual: filtro por CBO (ind2, ind3)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fai_cbo1
    ON tb_fat_atendimento_individual (co_dim_cbo_1);

-- [T-13] tb_fat_atendimento_individual: filtro por tipo de atendimento (ind1)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fai_tipo_atend
    ON tb_fat_atendimento_individual (co_dim_equipe_1, co_dim_tipo_atendimento);

-- [T-14] tb_fat_atd_ind_procedimentos: ind11 (citopatológico e mamografia)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_faip_equipe_proc
    ON tb_fat_atd_ind_procedimentos (co_dim_equipe_1, co_dim_procedimento_avaliado);

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_faip_cidadao
    ON tb_fat_atd_ind_procedimentos (co_fat_cidadao_pec);

-- [T-15] tb_fat_atendimento_odonto: filtro por tipo de consulta (ind13, ind14)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fao_tipo_consulta
    ON tb_fat_atendimento_odonto (co_dim_equipe_1, co_dim_tipo_consulta);

-- [T-16] tb_fat_visita_domiciliar: filtro por CBO (ACS/TACS) e desfecho (ind2, ind8)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fvd_cbo_desfecho
    ON tb_fat_visita_domiciliar (co_dim_cbo, co_dim_desfecho_visita);

-- =============================================================================
-- PRIORIDADE 4 — GIN/trgm para LIKE e regex (requer extensão pg_trgm)
-- =============================================================================
-- Estas queries não podem usar B-tree: LIKE '%|15|%' e ~ '\\|F[0-9]'
-- Se pg_trgm estiver disponível, ative com:
--   CREATE EXTENSION IF NOT EXISTS pg_trgm;
-- Depois crie os índices abaixo. Eles podem usar vários GB de espaço.

-- CREATE EXTENSION IF NOT EXISTS pg_trgm;

-- [T-17] tb_fat_vacinacao: ds_filtro_imunobiologico LIKE '%|id|%' (ind2, ind9, ind11)
-- CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fv_filtro_imu_trgm
--     ON tb_fat_vacinacao USING gin (ds_filtro_imunobiologico gin_trgm_ops);

-- [T-18] tb_fat_atendimento_individual: ds_filtro_ciaps ~ pattern (ind7, ind11)
-- CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fai_filtro_ciaps_trgm
--     ON tb_fat_atendimento_individual USING gin (ds_filtro_ciaps gin_trgm_ops);

-- [T-19] tb_fat_atendimento_individual: ds_filtro_cids ~ pattern (ind7)
-- CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_monitor_fai_filtro_cids_trgm
--     ON tb_fat_atendimento_individual USING gin (ds_filtro_cids gin_trgm_ops);

-- =============================================================================
-- EXPLAIN ANALYZE — rode cada bloco abaixo para confirmar uso dos índices
-- Substitua os literais pelos valores reais antes de executar.
-- =============================================================================

-- [EA-1] tb_dim_tempo — deve mostrar "Index Scan" após T-1
EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT)
SELECT co_seq_dim_tempo FROM tb_dim_tempo
WHERE nu_ano = 2025 AND nu_mes BETWEEN 1 AND 4;

-- [EA-2] tb_dim_equipe — deve mostrar "Index Scan" após T-2
EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT)
SELECT co_seq_dim_equipe, nu_ine, no_equipe
FROM tb_dim_equipe
WHERE nu_ine IN ('0000001','0000002','0000003')
  AND st_registro_valido = 1;

-- [EA-3] tb_fat_atendimento_individual × tb_dim_tempo — maior bottleneck (ind1-7,11)
EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT)
SELECT de.nu_ine, COUNT(*) AS total
FROM tb_fat_atendimento_individual fai
JOIN tb_dim_equipe de ON fai.co_dim_equipe_1 = de.co_seq_dim_equipe
JOIN tb_dim_tempo  dt ON fai.co_dim_tempo    = dt.co_seq_dim_tempo
WHERE dt.nu_ano = 2025 AND dt.nu_mes BETWEEN 1 AND 4
  AND de.nu_ine IN ('0000001','0000002') AND de.st_registro_valido = 1
GROUP BY de.nu_ine;

-- [EA-4] tb_fat_cad_individual denominador ativo (ind4, ind5, ind8, ind10, ind13)
EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT)
SELECT de.nu_ine, COUNT(DISTINCT fci.co_fat_cidadao_pec) AS total
FROM tb_fat_cad_individual fci
JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
WHERE de.nu_ine IN ('0000001','0000002') AND fci.st_ficha_inativa = 0
GROUP BY de.nu_ine;

-- [EA-5] tb_fat_cad_individual com faixa etária (ind2, ind6, ind15)
EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT)
SELECT de.nu_ine, COUNT(DISTINCT fci.co_fat_cidadao_pec) AS total
FROM tb_fat_cad_individual fci
JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
WHERE de.nu_ine IN ('0000001','0000002') AND fci.st_ficha_inativa = 0
  AND fci.dt_nascimento > CURRENT_DATE - INTERVAL '24 months'
GROUP BY de.nu_ine;

-- [EA-6] tb_fat_visita_domiciliar (ind8)
EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT)
SELECT de.nu_ine, COUNT(DISTINCT fvd.co_fat_cidadao_pec) AS total
FROM tb_fat_visita_domiciliar fvd
JOIN tb_dim_equipe de ON fvd.co_dim_equipe = de.co_seq_dim_equipe
JOIN tb_dim_tempo  dt ON fvd.co_dim_tempo  = dt.co_seq_dim_tempo
WHERE de.nu_ine IN ('0000001','0000002')
  AND dt.nu_ano = 2025 AND dt.nu_mes BETWEEN 1 AND 4
GROUP BY de.nu_ine;

-- [EA-7] tb_fat_vacinacao com LIKE (ind9) — antes e depois de T-17
EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT)
SELECT de.nu_ine, COUNT(*) AS total FROM (
    SELECT de.nu_ine, fv.co_fat_cidadao_pec
    FROM tb_fat_vacinacao fv
    JOIN tb_dim_equipe de ON fv.co_dim_equipe = de.co_seq_dim_equipe
    JOIN tb_dim_tempo  dt ON fv.co_dim_tempo  = dt.co_seq_dim_tempo
    WHERE de.nu_ine IN ('0000001','0000002')
      AND dt.nu_ano = 2025 AND dt.nu_mes BETWEEN 1 AND 4
    GROUP BY de.nu_ine, fv.co_fat_cidadao_pec
    HAVING BOOL_OR(fv.ds_filtro_imunobiologico LIKE '%|15|%')
       AND BOOL_OR(fv.ds_filtro_imunobiologico LIKE '%|14|%')
) sq GROUP BY nu_ine;

-- =============================================================================
-- MANUTENÇÃO — após criar os índices, rode ANALYZE nas tabelas principais
-- =============================================================================
ANALYZE tb_dim_tempo;
ANALYZE tb_dim_equipe;
ANALYZE tb_fat_atendimento_individual;
ANALYZE tb_fat_cad_individual;
ANALYZE tb_fat_visita_domiciliar;
ANALYZE tb_fat_vacinacao;
ANALYZE tb_fat_atendimento_odonto;
ANALYZE tb_fat_atividade_coletiva;
ANALYZE tb_fat_atd_ind_procedimentos;
