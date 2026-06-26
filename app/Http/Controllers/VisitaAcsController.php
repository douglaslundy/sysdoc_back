<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitaAcsController extends MonitorApsBaseController
{
    private const ACS_CBOS = ['515105', '322255'];

    private const CBO_LABELS = [
        '515105' => 'ACS',
        '322255' => 'TACS',
    ];

    // 1=Visita realizada, 2=Visita recusada, 3=Ausente, 4=NÃƒÂ£o informado
    private const OUTCOME_COLORS = [
        1 => 'success',
        2 => 'error',
        3 => 'warning',
        4 => 'default',
    ];

    /**
     * WHERE clause + bindings compartilhados por todos os endpoints de visitas.
     * $agentName aplica filtro textual exato (usado só em index).
     */
    private function buildWhere(
        int $ano, int $mes, ?string $ine,
        ?string $agentName = null,
        ?string $desfecho = null,
        ?string $hasGeo = null,
        ?array $allowedInes = null
    ): array {
        $cbos = implode("','", self::ACS_CBOS);
        $where = "c.nu_cbo IN ('{$cbos}') AND t.nu_ano = ? AND t.nu_mes = ?";
        $params = [$ano, $mes];

        if ($ine) {
            $where .= ' AND e.nu_ine = ?';
            $params[] = $ine;
        } elseif ($allowedInes !== null) {
            if (empty($allowedInes)) {
                $where .= ' AND 1=0';
            } else {
                $ph = implode(',', array_fill(0, count($allowedInes), '?'));
                $where .= " AND e.nu_ine IN ({$ph})";
                $params = array_merge($params, $allowedInes);
            }
        }

        if ($agentName && trim($agentName) !== '') {
            $where .= ' AND ' . $this->agentFilterClause('p.no_profissional');
            $params[] = $this->agentFilterValue($agentName);
        }

        if ($desfecho !== null && $desfecho !== '') {
            $where .= ' AND d.co_seq_dim_desfecho_visita = ?';
            $params[] = (int) $desfecho;
        }

        if ($hasGeo === 'sim') {
            $where .= ' AND v.nu_latitude IS NOT NULL AND v.nu_longitude IS NOT NULL';
        } elseif ($hasGeo === 'nao') {
            $where .= ' AND (v.nu_latitude IS NULL OR v.nu_longitude IS NULL)';
        }

        return [$where, $params];
    }

    /**
     * Filtros opcionais sem fixar ano/mês — usado por evolucao().
     */
    private function buildWhereFilters(
        ?string $ine,
        ?string $agentName = null,
        ?string $desfecho = null,
        ?string $hasGeo = null,
        ?array $allowedInes = null
    ): array {
        $cbos = implode("','", self::ACS_CBOS);
        $where = "c.nu_cbo IN ('{$cbos}')";
        $params = [];

        if ($ine) {
            $where .= ' AND e.nu_ine = ?';
            $params[] = $ine;
        } elseif ($allowedInes !== null) {
            if (empty($allowedInes)) {
                $where .= ' AND 1=0';
            } else {
                $ph = implode(',', array_fill(0, count($allowedInes), '?'));
                $where .= " AND e.nu_ine IN ({$ph})";
                $params = array_merge($params, $allowedInes);
            }
        }

        if ($agentName && trim($agentName) !== '') {
            $where .= ' AND ' . $this->agentFilterClause('p.no_profissional');
            $params[] = $this->agentFilterValue($agentName);
        }

        if ($desfecho !== null && $desfecho !== '') {
            $where .= ' AND d.co_seq_dim_desfecho_visita = ?';
            $params[] = (int) $desfecho;
        }

        if ($hasGeo === 'sim') {
            $where .= ' AND v.nu_latitude IS NOT NULL AND v.nu_longitude IS NOT NULL';
        } elseif ($hasGeo === 'nao') {
            $where .= ' AND (v.nu_latitude IS NULL OR v.nu_longitude IS NULL)';
        }

        return [$where, $params];
    }

    private function agentFilterClause(string $column): string
    {
        return "LOWER(BTRIM(COALESCE({$column}, ''))) LIKE ?";
    }

    private function agentFilterValue(?string $agentName): string
    {
        return '%' . mb_strtolower(trim((string) $agentName), 'UTF-8') . '%';
    }

    private function baseJoins(): string
    {
        $joins = '
            JOIN tb_dim_profissional    p  ON p.co_seq_dim_profissional    = v.co_dim_profissional
            JOIN tb_dim_cbo             c  ON c.co_seq_dim_cbo             = v.co_dim_cbo
            JOIN tb_dim_equipe          e  ON e.co_seq_dim_equipe          = v.co_dim_equipe
            JOIN tb_dim_tempo           t  ON t.co_seq_dim_tempo           = v.co_dim_tempo
            JOIN tb_dim_desfecho_visita d  ON d.co_seq_dim_desfecho_visita = v.co_dim_desfecho_visita
            JOIN tb_dim_tipo_ficha      tf ON tf.co_seq_dim_tipo_ficha     = v.co_dim_tipo_ficha
        ';

        if ($this->hasCdsFichaOrigem()) {
            $joins .= '
                LEFT JOIN tb_cds_ficha_visita_domiciliar cfv
                    ON cfv.co_unico_ficha = v.nu_uuid_ficha
                LEFT JOIN tb_cds_tipo_origem cto
                    ON cto.co_cds_tipo_origem = cfv.tp_cds_origem
            ';
        }

        return $joins;
    }

    private function hasCdsFichaOrigem(): bool
    {
        return $this->hasColumn('tb_fat_visita_domiciliar', 'nu_uuid_ficha')
            && $this->hasTable('tb_cds_ficha_visita_domiciliar')
            && $this->hasColumn('tb_cds_ficha_visita_domiciliar', 'co_unico_ficha')
            && $this->hasColumn('tb_cds_ficha_visita_domiciliar', 'tp_cds_origem')
            && $this->hasTable('tb_cds_tipo_origem')
            && $this->hasColumn('tb_cds_tipo_origem', 'co_cds_tipo_origem')
            && $this->hasColumn('tb_cds_tipo_origem', 'no_cds_tipo_origem');
    }

    /**
     * Expressão SQL para o instrumento de registro.
     *
     * tb_dim_tipo_ficha.ds_tipo_ficha armazena o nome da ficha/template, que é igual
     * para todas as visitas domiciliares. A coluna da tela precisa refletir a origem
     * real do dado, quando disponível no DW.
     */
    private function instrumentExpr(string $alias = 'v'): string
    {
        $parts = [];

        if ($this->hasCdsFichaOrigem()) {
            $parts[] = 'NULLIF(cto.no_cds_tipo_origem, \'\')';
        }

        if ($this->hasColumn('tb_fat_visita_domiciliar', 'st_tipo_instrumento_registro')) {
            $parts[] = "CASE {$alias}.st_tipo_instrumento_registro
                        WHEN 1 THEN 'CDS'
                        WHEN 3 THEN 'PEC (Tablet)'
                        WHEN 4 THEN 'App e-SUS APS'
                        ELSE NULL
                    END";
        }

        $parts[] = 'NULLIF(tf.ds_tipo_ficha, \'\')';
        $parts[] = "'Desconhecido'";

        return 'COALESCE(' . implode(', ', $parts) . ')';
    }

    private function listColumns(string $table): array
    {
        try {
            $rows = $this->db()->select("
                SELECT attname::text AS col
                FROM pg_catalog.pg_attribute
                WHERE attrelid = ?::regclass AND attnum > 0 AND NOT attisdropped
                ORDER BY attnum
            ", [$table]);
            return array_column($rows, 'col');
        } catch (\Throwable) {
            return ["(tabela não encontrada: {$table})"];
        }
    }

    /**
     * Builds the SQL expression for the visit annotation/notes.
     *
     * Priority:
     *   0. tb_visita_domiciliar_acs (OLTP) linked via UUID — has free-text ds_anotacao
     *   1. Direct text column in tb_fat_visita_domiciliar (rare — some e-SUS versions populate it)
     *   2. Direct FK from fat table to tb_cds_visita_domiciliar
     */
    private function buildNotesExpr(string $alias = 'v'): string
    {
        // 0. OLTP ACS visit table — contains free-text annotation (ds_anotacao)
        if ($this->hasTable('tb_visita_domiciliar_acs')) {
            $fatUuidCol  = $this->firstExistingColumn('tb_fat_visita_domiciliar', [
                'nu_uuid_ficha', 'co_unico_ficha', 'nu_uuid',
            ]);
            $acsUuidCol  = $this->firstExistingColumn('tb_visita_domiciliar_acs', [
                'co_unico_visita_domiciliar', 'nu_uuid_ficha', 'nu_uuid', 'co_unico',
            ]);
            $acsAnnotCol = $this->firstExistingColumn('tb_visita_domiciliar_acs', [
                'ds_anotacao', 'ds_observacao', 'ds_relato', 'ds_anotacao_visita',
            ]);
            if ($fatUuidCol && $acsUuidCol && $acsAnnotCol) {
                return "(SELECT a.{$acsAnnotCol}::text FROM tb_visita_domiciliar_acs a WHERE a.{$acsUuidCol} = {$alias}.{$fatUuidCol} LIMIT 1)";
            }
        }

        // 1. Direct column in fact table
        $col = $this->firstExistingColumn('tb_fat_visita_domiciliar', [
            'ds_anotacao', 'ds_observacao', 'ds_relato',
            'ds_anotacao_visita', 'ds_observacao_visita',
            'tx_anotacao', 'tx_observacao', 'tx_relato',
        ]);
        if ($col) {
            return "{$alias}.{$col}";
        }

        // 2. Direct FK: fat → cds visit row
        $fatCdsFk = $this->firstExistingColumn('tb_fat_visita_domiciliar', [
            'co_cds_visita_domiciliar', 'co_seq_cds_visita_domiciliar',
        ]);
        $cdsAnnot = $this->firstExistingColumn('tb_cds_visita_domiciliar', [
            'ds_anotacao', 'ds_observacao', 'ds_relato',
        ]);
        if ($fatCdsFk && $cdsAnnot) {
            return "(SELECT cds.{$cdsAnnot}::text FROM tb_cds_visita_domiciliar cds WHERE cds.co_seq_cds_visita_domiciliar = {$alias}.{$fatCdsFk} LIMIT 1)";
        }

        return 'NULL::text';
    }

    private function familyRespCol(): ?string
    {
        if (!$this->hasTable('tb_fat_cad_individual')) {
            return null;
        }

        return $this->firstExistingColumn('tb_fat_cad_individual', [
            'co_responsavel_familiar',
            'co_fat_cidadao_pec_responsavel',
        ]);
    }

    private function familyStCol(): ?string
    {
        if (!$this->hasTable('tb_fat_cad_individual')) {
            return null;
        }

        return $this->firstExistingColumn('tb_fat_cad_individual', [
            'st_responsavel_familiar',
            'tp_responsavel_familiar',
        ]);
    }

    /**
     * Expressão SQL que identifica a família de um cidadão via JOIN com tb_fat_cad_individual (alias ci).
     *
     * Retorna null quando as colunas necessárias não existem — o chamador deve omitir
     * o LEFT JOIN e retornar null nos campos de família da resposta.
     *
     * Lógica:
     *   - Responsável: co_responsavel_familiar IS NULL → usa seu próprio co_fat_cidadao_pec
     *   - Membro: co_responsavel_familiar aponta para o co_fat_cidadao_pec do responsável
     *   - Sem vínculo familiar: expressão retorna NULL (excluído do COUNT DISTINCT)
     */
    private function familyIdExpr(string $alias = 'ci'): ?string
    {
        $stCol   = $this->familyStCol();
        $respCol = $this->familyRespCol();

        if (! $respCol && ! $stCol) {
            return null;
        }

        if ($stCol && $respCol) {
            return "COALESCE({$alias}.{$respCol}, CASE WHEN {$alias}.{$stCol} = 1 THEN {$alias}.co_fat_cidadao_pec END)";
        }

        if ($respCol) {
            // Sem coluna de flag: assume NULL no co_responsavel = próprio responsável
            return "COALESCE({$alias}.{$respCol}, {$alias}.co_fat_cidadao_pec)";
        }

        // Apenas flag disponível: identifica somente os responsáveis
        return "CASE WHEN {$alias}.{$stCol} = 1 THEN {$alias}.co_fat_cidadao_pec ELSE NULL END";
    }

    private function hasDomicilioCadastro(): bool
    {
        return $this->hasTable('tb_fat_cad_domiciliar')
            && $this->hasColumn('tb_fat_cad_domiciliar', 'co_seq_fat_cad_domiciliar')
            && $this->hasColumn('tb_fat_cad_domiciliar', 'co_dim_profissional')
            && $this->hasColumn('tb_fat_cad_domiciliar', 'co_dim_equipe')
            && $this->hasTable('tb_fat_cad_dom_familia')
            && $this->hasColumn('tb_fat_cad_dom_familia', 'co_fat_cad_domiciliar');
    }

    private function domicilioFamiliaWhere(string $alias = 'f'): string
    {
        $where = ["{$alias}.co_fat_cad_domiciliar = d.co_seq_fat_cad_domiciliar"];

        if ($this->hasColumn('tb_fat_cad_dom_familia', 'st_recusa_cadastro')) {
            $where[] = "COALESCE({$alias}.st_recusa_cadastro, 0) = 0";
        }
        if ($this->hasColumn('tb_fat_cad_dom_familia', 'st_mudou')) {
            $where[] = "COALESCE({$alias}.st_mudou, 0) = 0";
        }
        if ($this->hasColumn('tb_fat_cad_dom_familia', 'co_dim_tempo_validade')) {
            $where[] = "{$alias}.co_dim_tempo_validade = 30001231";
        }

        return implode(' AND ', $where);
    }

    /**
     * Condições de status de tb_fat_cad_dom_familia sem a cláusula de JOIN.
     * Usado em CTEs que precisam do cadastro mais recente por cidadão.
     */
    private function domicilioFamiliaFilters(string $alias = 'f'): string
    {
        $conds = [];

        if ($this->hasColumn('tb_fat_cad_dom_familia', 'st_recusa_cadastro')) {
            $conds[] = "COALESCE({$alias}.st_recusa_cadastro, 0) = 0";
        }
        if ($this->hasColumn('tb_fat_cad_dom_familia', 'st_mudou')) {
            $conds[] = "COALESCE({$alias}.st_mudou, 0) = 0";
        }
        if ($this->hasColumn('tb_fat_cad_dom_familia', 'co_dim_tempo_validade')) {
            $conds[] = "{$alias}.co_dim_tempo_validade = 30001231";
        }

        return $conds ? implode(' AND ', $conds) : 'TRUE';
    }

    private function domicilioCadastroStats(?string $ine, ?string $agentName, ?array $allowedInes = null): array
    {
        $empty = [
            'domicilios_total' => null,
            'domicilios_com_moradores' => null,
            'domicilios_casa_vazia' => null,
            'domicilios_fa' => null,
        ];

        if (!$this->hasDomicilioCadastro()) {
            return $empty;
        }

        $where = ['de.st_registro_valido = 1'];
        $params = [];

        if ($this->hasColumn('tb_fat_cad_domiciliar', 'co_dim_tempo_validade')) {
            $where[] = 'd.co_dim_tempo_validade = 30001231';
        }
        if ($this->hasColumn('tb_fat_cad_domiciliar', 'st_recusa_cadastro')) {
            $where[] = 'COALESCE(d.st_recusa_cadastro, 0) = 0';
        }
        if ($ine) {
            $where[] = 'de.nu_ine = ?';
            $params[] = $ine;
        } elseif ($allowedInes !== null) {
            if (empty($allowedInes)) {
                $where[] = '1=0';
            } else {
                $ph = implode(',', array_fill(0, count($allowedInes), '?'));
                $where[] = "de.nu_ine IN ({$ph})";
                $params = array_merge($params, $allowedInes);
            }
        }
        if ($agentName && trim($agentName) !== '') {
            $where[] = $this->agentFilterClause('dp.no_profissional');
            $params[] = $this->agentFilterValue($agentName);
        }

        $hasMoradores = "EXISTS (SELECT 1 FROM tb_fat_cad_dom_familia f WHERE {$this->domicilioFamiliaWhere('f')})";
        $regularDomicilio = $this->hasColumn('tb_fat_cad_domiciliar', 'nu_micro_area')
            ? "COALESCE(UPPER(TRIM(d.nu_micro_area)), '') <> 'FA'"
            : 'TRUE';
        $domicilioFa = $this->hasColumn('tb_fat_cad_domiciliar', 'nu_micro_area')
            ? "COALESCE(UPPER(TRIM(d.nu_micro_area)), '') = 'FA'"
            : 'FALSE';

        try {
            $row = $this->db()->selectOne("
                SELECT
                    COUNT(DISTINCT d.co_seq_fat_cad_domiciliar) FILTER (WHERE {$regularDomicilio}) AS domicilios_total,
                    COUNT(DISTINCT d.co_seq_fat_cad_domiciliar) FILTER (WHERE {$regularDomicilio} AND {$hasMoradores}) AS domicilios_com_moradores,
                    COUNT(DISTINCT d.co_seq_fat_cad_domiciliar) FILTER (WHERE {$regularDomicilio} AND NOT {$hasMoradores}) AS domicilios_casa_vazia,
                    COUNT(DISTINCT d.co_seq_fat_cad_domiciliar) FILTER (WHERE {$domicilioFa}) AS domicilios_fa
                FROM tb_fat_cad_domiciliar d
                JOIN tb_dim_equipe de
                    ON de.co_seq_dim_equipe = d.co_dim_equipe
                LEFT JOIN tb_dim_profissional dp
                    ON dp.co_seq_dim_profissional = d.co_dim_profissional
                WHERE " . implode(' AND ', $where) . "
            ", $params);
        } catch (\Throwable) {
            return $empty;
        }

        return [
            'domicilios_total' => (int) ($row->domicilios_total ?? 0),
            'domicilios_com_moradores' => (int) ($row->domicilios_com_moradores ?? 0),
            'domicilios_casa_vazia' => (int) ($row->domicilios_casa_vazia ?? 0),
            'domicilios_fa' => (int) ($row->domicilios_fa ?? 0),
        ];
    }

    private function domicilioCadastroStatsPorAgente(?string $ine, ?string $agentName, ?array $allowedInes = null): array
    {
        if (!$this->hasDomicilioCadastro()) {
            return [];
        }

        $where = ['de.st_registro_valido = 1'];
        $params = [];

        if ($this->hasColumn('tb_fat_cad_domiciliar', 'co_dim_tempo_validade')) {
            $where[] = 'd.co_dim_tempo_validade = 30001231';
        }
        if ($this->hasColumn('tb_fat_cad_domiciliar', 'st_recusa_cadastro')) {
            $where[] = 'COALESCE(d.st_recusa_cadastro, 0) = 0';
        }
        if ($ine) {
            $where[] = 'de.nu_ine = ?';
            $params[] = $ine;
        } elseif ($allowedInes !== null) {
            if (empty($allowedInes)) {
                $where[] = '1=0';
            } else {
                $ph = implode(',', array_fill(0, count($allowedInes), '?'));
                $where[] = "de.nu_ine IN ({$ph})";
                $params = array_merge($params, $allowedInes);
            }
        }
        if ($agentName && trim($agentName) !== '') {
            $where[] = $this->agentFilterClause('dp.no_profissional');
            $params[] = $this->agentFilterValue($agentName);
        }

        $hasMoradores = "EXISTS (SELECT 1 FROM tb_fat_cad_dom_familia f WHERE {$this->domicilioFamiliaWhere('f')})";
        $regularDomicilio = $this->hasColumn('tb_fat_cad_domiciliar', 'nu_micro_area')
            ? "COALESCE(UPPER(TRIM(d.nu_micro_area)), '') <> 'FA'"
            : 'TRUE';
        $domicilioFa = $this->hasColumn('tb_fat_cad_domiciliar', 'nu_micro_area')
            ? "COALESCE(UPPER(TRIM(d.nu_micro_area)), '') = 'FA'"
            : 'FALSE';

        try {
            $rows = $this->db()->select("
                SELECT
                    dp.no_profissional AS agente,
                    COUNT(DISTINCT d.co_seq_fat_cad_domiciliar) FILTER (WHERE {$regularDomicilio}) AS domicilios_total,
                    COUNT(DISTINCT d.co_seq_fat_cad_domiciliar) FILTER (WHERE {$regularDomicilio} AND {$hasMoradores}) AS domicilios_com_moradores,
                    COUNT(DISTINCT d.co_seq_fat_cad_domiciliar) FILTER (WHERE {$regularDomicilio} AND NOT {$hasMoradores}) AS domicilios_casa_vazia,
                    COUNT(DISTINCT d.co_seq_fat_cad_domiciliar) FILTER (WHERE {$domicilioFa}) AS domicilios_fa
                FROM tb_fat_cad_domiciliar d
                JOIN tb_dim_equipe de
                    ON de.co_seq_dim_equipe = d.co_dim_equipe
                LEFT JOIN tb_dim_profissional dp
                    ON dp.co_seq_dim_profissional = d.co_dim_profissional
                WHERE " . implode(' AND ', $where) . "
                GROUP BY dp.no_profissional
            ", $params);
        } catch (\Throwable) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $map[$row->agente ?? ''] = [
                'domicilios_total' => (int) ($row->domicilios_total ?? 0),
                'domicilios_com_moradores' => (int) ($row->domicilios_com_moradores ?? 0),
                'domicilios_casa_vazia' => (int) ($row->domicilios_casa_vazia ?? 0),
                'domicilios_fa' => (int) ($row->domicilios_fa ?? 0),
            ];
        }

        return $map;
    }

    private function domicilioVisitaStats(int $ano, int $mes, ?string $ine, ?string $agentName, ?array $allowedInes = null): array
    {
        $empty = [
            'domicilios_visitados' => null,
            'domicilios_acompanhados' => null,
            'domicilios_recusados' => null,
            'domicilios_ausentes' => null,
        ];

        if (!$this->hasDomicilioCadastro() || !$this->hasColumn('tb_fat_cad_dom_familia', 'co_fat_cidadao_pec')) {
            return $empty;
        }

        [$where, $params] = $this->buildWhere($ano, $mes, $ine, $agentName, null, null, $allowedInes);
        $familiaFilters = $this->domicilioFamiliaFilters('f');
        $regularDomicilio = $this->hasColumn('tb_fat_cad_domiciliar', 'nu_micro_area')
            ? "COALESCE(UPPER(TRIM(dom.nu_micro_area)), '') <> 'FA'"
            : 'TRUE';

        try {
            $row = $this->db()->selectOne("
                WITH citizen_domicilio AS (
                    -- Um domicílio por cidadão: o mais recentemente cadastrado (maior PK).
                    SELECT DISTINCT ON (f.co_fat_cidadao_pec)
                        f.co_fat_cidadao_pec,
                        f.co_fat_cad_domiciliar
                    FROM tb_fat_cad_dom_familia f
                    WHERE {$familiaFilters}
                    ORDER BY f.co_fat_cidadao_pec, f.co_fat_cad_domiciliar DESC
                ),
                visitas_domicilio AS (
                    SELECT DISTINCT
                        dom.co_seq_fat_cad_domiciliar AS domicilio_id,
                        d.co_seq_dim_desfecho_visita  AS desfecho
                    FROM tb_fat_visita_domiciliar v
                    {$this->baseJoins()}
                    LEFT JOIN citizen_domicilio cd
                        ON cd.co_fat_cidadao_pec = v.co_fat_cidadao_pec
                    LEFT JOIN tb_fat_cad_domiciliar dom
                        ON dom.co_seq_fat_cad_domiciliar = cd.co_fat_cad_domiciliar
                    WHERE {$where}
                      AND {$regularDomicilio}
                      AND dom.co_seq_fat_cad_domiciliar IS NOT NULL
                ),
                domicilio_status AS (
                    SELECT
                        domicilio_id,
                        BOOL_OR(desfecho = 1) AS tem_realizada,
                        BOOL_OR(desfecho = 2) AS tem_recusada,
                        BOOL_OR(desfecho = 3) AS tem_ausente
                    FROM visitas_domicilio
                    GROUP BY domicilio_id
                )
                SELECT
                    COUNT(*) AS domicilios_visitados,
                    COUNT(*) FILTER (WHERE tem_realizada AND NOT tem_ausente) AS domicilios_acompanhados,
                    COUNT(*) FILTER (WHERE tem_recusada) AS domicilios_recusados,
                    COUNT(*) FILTER (WHERE tem_ausente) AS domicilios_ausentes
                FROM domicilio_status
            ", $params);
        } catch (\Throwable) {
            return $empty;
        }

        return [
            'domicilios_visitados' => (int) ($row->domicilios_visitados ?? 0),
            'domicilios_acompanhados' => (int) ($row->domicilios_acompanhados ?? 0),
            'domicilios_recusados' => (int) ($row->domicilios_recusados ?? 0),
            'domicilios_ausentes' => (int) ($row->domicilios_ausentes ?? 0),
        ];
    }

    private function domicilioVisitaStatsPorAgente(int $ano, int $mes, ?string $ine, ?string $agentName, ?array $allowedInes = null): array
    {
        if (!$this->hasDomicilioCadastro() || !$this->hasColumn('tb_fat_cad_dom_familia', 'co_fat_cidadao_pec')) {
            return [];
        }

        [$where, $params] = $this->buildWhere($ano, $mes, $ine, $agentName, null, null, $allowedInes);
        $familiaFilters = $this->domicilioFamiliaFilters('f');
        $regularDomicilio = $this->hasColumn('tb_fat_cad_domiciliar', 'nu_micro_area')
            ? "COALESCE(UPPER(TRIM(dom.nu_micro_area)), '') <> 'FA'"
            : 'TRUE';

        try {
            $rows = $this->db()->select("
                WITH citizen_domicilio AS (
                    SELECT DISTINCT ON (f.co_fat_cidadao_pec)
                        f.co_fat_cidadao_pec,
                        f.co_fat_cad_domiciliar
                    FROM tb_fat_cad_dom_familia f
                    WHERE {$familiaFilters}
                    ORDER BY f.co_fat_cidadao_pec, f.co_fat_cad_domiciliar DESC
                ),
                visitas_domicilio AS (
                    SELECT DISTINCT
                        p.no_profissional                 AS agente,
                        dom.co_seq_fat_cad_domiciliar     AS domicilio_id,
                        d.co_seq_dim_desfecho_visita      AS desfecho
                    FROM tb_fat_visita_domiciliar v
                    {$this->baseJoins()}
                    LEFT JOIN citizen_domicilio cd
                        ON cd.co_fat_cidadao_pec = v.co_fat_cidadao_pec
                    LEFT JOIN tb_fat_cad_domiciliar dom
                        ON dom.co_seq_fat_cad_domiciliar = cd.co_fat_cad_domiciliar
                    WHERE {$where}
                      AND {$regularDomicilio}
                      AND dom.co_seq_fat_cad_domiciliar IS NOT NULL
                ),
                domicilio_status AS (
                    SELECT
                        agente,
                        domicilio_id,
                        BOOL_OR(desfecho = 1) AS tem_realizada,
                        BOOL_OR(desfecho = 2) AS tem_recusada,
                        BOOL_OR(desfecho = 3) AS tem_ausente
                    FROM visitas_domicilio
                    GROUP BY agente, domicilio_id
                )
                SELECT
                    agente,
                    COUNT(*) AS domicilios_visitados,
                    COUNT(*) FILTER (WHERE tem_realizada AND NOT tem_ausente) AS domicilios_acompanhados,
                    COUNT(*) FILTER (WHERE tem_recusada) AS domicilios_recusados,
                    COUNT(*) FILTER (WHERE tem_ausente) AS domicilios_ausentes
                FROM domicilio_status
                GROUP BY agente
            ", $params);
        } catch (\Throwable) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $map[$row->agente ?? ''] = [
                'domicilios_visitados'    => (int) ($row->domicilios_visitados ?? 0),
                'domicilios_acompanhados' => (int) ($row->domicilios_acompanhados ?? 0),
                'domicilios_recusados'    => (int) ($row->domicilios_recusados ?? 0),
                'domicilios_ausentes'     => (int) ($row->domicilios_ausentes ?? 0),
            ];
        }

        return $map;
    }

    private function textColumnExpr(string $tableAlias, ?string $column): string
    {
        return $column ? "{$tableAlias}.{$column}::text" : 'NULL::text';
    }

    private function citizenNameExpr(string $visitAlias = 'v'): string
    {
        // Filtra hashes/UUIDs que o e-SUS armazena no lugar do nome (SHA-256, MD5, UUID).
        // ~* = case-insensitive; {32,} cobre MD5(32), SHA-1(40), SHA-256(64) e outros.
        $hashGuard = fn(string $col) =>
            "NULLIF(CASE WHEN {$col} ~* '^[0-9a-f]{32,}$'"
            . "             OR {$col} ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$'"
            . "          THEN NULL ELSE {$col} END, '')";

        if (
            $this->hasTable('tb_fat_cad_individual')
            && $this->hasColumn('tb_fat_cad_individual', 'no_cidadao')
            && $this->hasColumn('tb_fat_cad_individual', 'co_fat_cidadao_pec')
        ) {
            $expr = $hashGuard('ci.no_cidadao');
            return "(SELECT {$expr} FROM tb_fat_cad_individual ci WHERE ci.co_fat_cidadao_pec = {$visitAlias}.co_fat_cidadao_pec LIMIT 1)";
        }

        if (
            $this->hasTable('tb_fat_cidadao_pec')
            && $this->hasColumn('tb_fat_cidadao_pec', 'no_cidadao')
            && $this->hasColumn('tb_fat_cidadao_pec', 'co_seq_fat_cidadao_pec')
        ) {
            $expr = $hashGuard('cp.no_cidadao');
            return "(SELECT {$expr} FROM tb_fat_cidadao_pec cp WHERE cp.co_seq_fat_cidadao_pec = {$visitAlias}.co_fat_cidadao_pec LIMIT 1)";
        }

        return 'NULL::text';
    }

    private function normalizeAddressValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }

        return preg_match('/^[a-f0-9]{12,}$/i', $trimmed) ? null : $trimmed;
    }

    private function formatMotives(object $row): array
    {
        $labels = [
            'st_mot_vis_cad_att' => 'Cadastramento/atualização',
            'st_mot_vis_visita_periodica' => 'Visita periódica',
            'st_mot_vis_busca_ativa' => 'Busca ativa',
            'st_mot_vis_acompanhamento' => 'Acompanhamento',
            'st_mot_vis_egresso_internacao' => 'Egresso de internação',
            'st_mot_vis_ctrl_ambnte_vetor' => 'Controle ambiental/vetorial',
            'st_mot_vis_convte_atvidd_cltva' => 'Convite para atividade coletiva',
            'st_mot_vis_orintacao_prevncao' => 'Orientação/prevenção',
            'st_mot_vis_outros' => 'Outros',
        ];

        $active = [];
        foreach ($labels as $field => $label) {
            if (isset($row->$field) && (int) $row->$field === 1) {
                $active[] = $label;
            }
        }

        return $active;
    }

    private function formatAccompaniments(object $row): array
    {
        $fields = [
            'st_acomp_gestante' => 'Gestante',
            'st_acomp_puerpera' => 'Puérpera',
            'st_acomp_recem_nascido' => 'Recém-nascido',
            'st_acomp_crianca' => 'Criança',
            'st_acomp_pessoa_hipertensao' => 'Hipertensão',
            'st_acomp_pessoa_diabetes' => 'Diabetes',
            'st_acomp_pessoa_cancer' => 'Câncer',
            'st_acomp_pessoa_idosa' => 'Pessoa idosa',
            'st_acomp_saude_mental' => 'Saúde mental',
            'st_acomp_tabagista' => 'Tabagismo',
            'st_acomp_domiciliados_acamados' => 'Domiciliado/acamado',
            'st_acomp_pessoa_tuberculose' => 'Tuberculose',
            'st_acomp_pessoa_hanseniase' => 'Hanseníase',
            'st_acomp_condi_bolsa_familia' => 'Bolsa Família',
        ];

        $active = [];
        foreach ($fields as $field => $label) {
            if (isset($row->$field) && (int) $row->$field === 1) {
                $active[] = $label;
            }
        }

        return $active;
    }

    private function formatVisita(object $row, bool $detail = false): array
    {
        $outcomeCode = (int) ($row->outcome_code ?? 4);

        $result = [
            'id' => (int) $row->id,
            'agent_name' => $row->agent_name,
            'cbo' => $row->cbo,
            'cbo_label' => self::CBO_LABELS[$row->cbo] ?? $row->cbo,
            'team_ine' => $row->team_ine,
            'team_name' => $row->team_name,
            'visited_date' => $row->visited_date,
            'hora' => isset($row->hora) ? (int) $row->hora : null,
            'instrument_label' => $row->instrument_label,
            'outcome_code' => $outcomeCode,
            'outcome_label' => $row->outcome_label,
            'outcome_color' => self::OUTCOME_COLORS[$outcomeCode] ?? 'default',
            'has_geolocation' => (bool) $row->has_geo,
            'motives' => $this->formatMotives($row),
        ];

        if ($detail) {
            $logradouro = $this->normalizeAddressValue($row->logradouro ?? null);
            $numero = $this->normalizeAddressValue($row->num_endereco ?? null);
            $complemento = $this->normalizeAddressValue($row->complemento ?? null);
            $bairro = $this->normalizeAddressValue($row->bairro ?? null);
            $cep = $this->normalizeAddressValue($row->cep ?? null);

            $result['citizen_name'] = $row->citizen_name ?? null;
            $result['notes'] = $row->notes ?? null;
            $result['lat'] = isset($row->lat) && $row->lat !== null ? (float) $row->lat : null;
            $result['lng'] = isset($row->lng) && $row->lng !== null ? (float) $row->lng : null;
            $result['accompaniments'] = $this->formatAccompaniments($row);
            $result['logradouro'] = $logradouro;
            $result['num_endereco'] = $numero;
            $result['complemento'] = $complemento;
            $result['bairro'] = $bairro;
            $result['cep'] = $cep;
            $result['address'] = [
                'logradouro' => $logradouro,
                'numero' => $numero,
                'complemento' => $complemento,
                'bairro' => $bairro,
                'cep' => $cep,
            ];
        }

        return $result;
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Endpoints Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    /**
     * GET /visitas?ano=X&mes=Y[&ine=Z&agente=W&page=N&per_page=N]
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'ano' => 'required|integer|min:2020|max:2030',
            'mes' => 'required|integer|min:1|max:12',
            'ine' => 'nullable|string',
            'agente' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $ano = (int) $request->ano;
        $mes = (int) $request->mes;
        $perPage = (int) ($request->per_page ?? 20);
        $page = (int) ($request->page ?? 1);
        $offset = ($page - 1) * $perPage;

        $ine = $request->query('ine') ?: null;
        $this->assertIneAllowed($request, $ine);
        $allowedInes = $this->resolveAllowedInes($request);

        [$where, $params] = $this->buildWhere($ano, $mes, $ine, $request->agente, null, null, $allowedInes);

        try {
            $rows = $this->db()->select("
                SELECT
                    v.co_seq_fat_visita_domiciliar   AS id,
                    p.no_profissional                AS agent_name,
                    c.nu_cbo                         AS cbo,
                    e.nu_ine                         AS team_ine,
                    e.no_equipe                      AS team_name,
                    t.dt_registro                    AS visited_date,
                    {$this->instrumentExpr()}        AS instrument_label,
                    d.co_seq_dim_desfecho_visita     AS outcome_code,
                    d.ds_desfecho_visita             AS outcome_label,
                    v.st_mot_vis_cad_att,
                    v.st_mot_vis_visita_periodica,
                    v.st_mot_vis_busca_ativa,
                    v.st_mot_vis_acompanhamento,
                    v.st_mot_vis_egresso_internacao,
                    v.st_mot_vis_ctrl_ambnte_vetor,
                    v.st_mot_vis_convte_atvidd_cltva,
                    v.st_mot_vis_orintacao_prevncao,
                    v.st_mot_vis_outros,
                    CASE WHEN v.nu_latitude IS NOT NULL AND v.nu_longitude IS NOT NULL
                         THEN true ELSE false END    AS has_geo,
                    COUNT(*) OVER()                  AS total_count
                FROM tb_fat_visita_domiciliar v
                {$this->baseJoins()}
                WHERE {$where}
                ORDER BY t.dt_registro DESC, v.co_seq_fat_visita_domiciliar DESC
                LIMIT ? OFFSET ?
            ", array_merge($params, [$perPage, $offset]));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('VisitaAcs.index: ' . $e->getMessage());
            return response()->json(['error' => 'Não foi possível consultar o banco eSUS PEC.'], 503);
        }

        $total = (int) ($rows[0]->total_count ?? 0);

        return response()->json([
            'data' => array_map(fn ($r) => $this->formatVisita($r), $rows),
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'pages' => (int) ceil($total / max($perPage, 1)),
            ],
        ]);
    }

    /**
     * GET /visitas/resumo?ano=X&mes=Y[&ine=Z]
     * Cards de totais + grÃƒÂ¡fico de barras (VisitasAcs.js).
     */
    public function resumo(Request $request): JsonResponse
    {
        $request->validate([
            'ano' => 'required|integer|min:2020|max:2030',
            'mes' => 'required|integer|min:1|max:12',
            'ine' => 'nullable|string',
            'agente' => 'nullable|string',
            'desfecho' => 'nullable|integer|in:1,2,3',
            'has_geo' => 'nullable|string|in:sim,nao',
        ]);

        $ano = (int) $request->ano;
        $mes = (int) $request->mes;

        $ine = $request->query('ine') ?: null;
        $this->assertIneAllowed($request, $ine);
        $allowedInes = $this->resolveAllowedInes($request);

        [$where, $params] = $this->buildWhere(
            $ano, $mes, $ine,
            $request->agente,
            $request->desfecho,
            $request->has_geo,
            $allowedInes,
        );

        try {
            $totRow = $this->db()->selectOne("
                SELECT
                    COUNT(DISTINCT v.co_seq_fat_visita_domiciliar) AS total,
                    COUNT(DISTINCT CASE WHEN d.co_seq_dim_desfecho_visita = 1 THEN v.co_seq_fat_visita_domiciliar END) AS realizadas,
                    COUNT(DISTINCT CASE WHEN d.co_seq_dim_desfecho_visita = 2 THEN v.co_seq_fat_visita_domiciliar END) AS recusadas,
                    COUNT(DISTINCT CASE WHEN d.co_seq_dim_desfecho_visita = 3 THEN v.co_seq_fat_visita_domiciliar END) AS ausentes,
                    COUNT(DISTINCT v.co_fat_cidadao_pec)                                 AS cidadaos
                FROM tb_fat_visita_domiciliar v
                {$this->baseJoins()}
                WHERE {$where}
            ", $params);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('VisitaAcs.resumo: ' . $e->getMessage());
            return response()->json(['error' => 'Não foi possível consultar o banco eSUS PEC.'], 503);
        }

        $domicilioCadastro = $this->domicilioCadastroStats($ine, $request->agente, $allowedInes);
        $domicilioVisitas  = $this->domicilioVisitaStats($ano, $mes, $ine, $request->agente, $allowedInes);

        if (false) {
            // Breakdown de famílias visitadas no mês (sem filtro de desfecho/geo)
            [$familyWhere, $familyParams] = $this->buildWhere($ano, $mes, $ine, $request->agente, null, null, $allowedInes);

            try {
                $famRow = $this->db()->selectOne("
                    SELECT
                        COUNT(DISTINCT {$familyExpr})
                            FILTER (WHERE d.co_seq_dim_desfecho_visita = 1) AS familias_acompanhadas,
                        COUNT(DISTINCT {$familyExpr})
                            FILTER (WHERE d.co_seq_dim_desfecho_visita = 2) AS familias_recusadas,
                        COUNT(DISTINCT {$familyExpr})
                            FILTER (WHERE d.co_seq_dim_desfecho_visita = 3) AS familias_ausentes
                    FROM tb_fat_visita_domiciliar v
                    {$this->baseJoins()}
                    LEFT JOIN tb_fat_cad_individual ci ON ci.co_fat_cidadao_pec = v.co_fat_cidadao_pec
                    WHERE {$familyWhere}
                ", $familyParams);

                if ($famRow) {
                    $familyData = [
                        'familias_acompanhadas' => (int) ($famRow->familias_acompanhadas ?? 0),
                        'familias_recusadas'    => (int) ($famRow->familias_recusadas ?? 0),
                        'familias_ausentes'     => (int) ($famRow->familias_ausentes ?? 0),
                    ];
                }
            } catch (\Throwable) {}
        }

        // Total de famílias sob responsabilidade do ACS (sem filtro de mês/desfecho)
        $familiasTotal = null;
        if (false) {
            try {
                $totFamWhere  = 'ci.st_ficha_inativa = 0 AND de.st_registro_valido = 1';
                $totFamParams = [];

                if ($request->ine) {
                    $totFamWhere  .= ' AND de.nu_ine = ?';
                    $totFamParams[] = $request->ine;
                }
                if ($request->agente && trim($request->agente) !== '') {
                    $totFamWhere  .= ' AND ' . $this->agentFilterClause('dp.no_profissional');
                    $totFamParams[] = $this->agentFilterValue($request->agente);
                }

                $totFamRow = $this->db()->selectOne("
                    SELECT COUNT(DISTINCT {$familyExpr}) AS familias_total
                    FROM tb_fat_cad_individual ci
                    JOIN tb_dim_equipe de
                        ON de.co_seq_dim_equipe = ci.co_dim_equipe
                    LEFT JOIN tb_dim_profissional dp
                        ON dp.co_seq_dim_profissional = ci.co_dim_profissional
                    WHERE {$totFamWhere}
                ", $totFamParams);

                $familiasTotal = (int) ($totFamRow->familias_total ?? 0);
            } catch (\Throwable) {}
        }

        $nullFamily = [
            'familias_total'        => null,
            'familias_acompanhadas' => null,
            'familias_recusadas'    => null,
            'familias_ausentes'     => null,
        ];

        return response()->json([
            'totais' => array_merge(
                [
                    'total'      => (int) ($totRow->total ?? 0),
                    'realizadas' => (int) ($totRow->realizadas ?? 0),
                    'recusadas'  => (int) ($totRow->recusadas ?? 0),
                    'ausentes'   => (int) ($totRow->ausentes ?? 0),
                    'cidadaos'   => (int) ($totRow->cidadaos ?? 0),
                ],
                $domicilioCadastro,
                $domicilioVisitas
            ),
        ]);
    }

    /**
     * GET /visitas/lista?ano=X&mes=Y[&ine=Z&agente=W&desfecho=N&page=N&per_page=N]
     * Lista paginada Ã¢â‚¬â€ aba Tabela do VisitasAcs.js.
     * Inclui instrumento e has_geo para exibir na tabela e habilitar o botÃƒÂ£o Ver.
     */
    public function lista(Request $request): JsonResponse
    {
        $request->validate([
            'ano' => 'required|integer|min:2020|max:2030',
            'mes' => 'required|integer|min:1|max:12',
            'ine' => 'nullable|string',
            'agente' => 'nullable|string',
            'desfecho' => 'nullable|integer',
            'has_geo' => 'nullable|in:sim,nao',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $ano = (int) $request->ano;
        $mes = (int) $request->mes;
        $perPage = (int) ($request->per_page ?? 50);
        $page = (int) ($request->page ?? 1);
        $offset = ($page - 1) * $perPage;

        $ine = $request->query('ine') ?: null;
        $this->assertIneAllowed($request, $ine);
        $allowedInes = $this->resolveAllowedInes($request);

        [$where, $params] = $this->buildWhere($ano, $mes, $ine, $request->agente, null, null, $allowedInes);

        if ($request->desfecho) {
            $where .= ' AND d.co_seq_dim_desfecho_visita = ?';
            $params[] = (int) $request->desfecho;
        }

        if ($request->has_geo === 'sim') {
            $where .= ' AND v.nu_latitude IS NOT NULL AND v.nu_longitude IS NOT NULL';
        } elseif ($request->has_geo === 'nao') {
            $where .= ' AND (v.nu_latitude IS NULL OR v.nu_longitude IS NULL)';
        }

        $queryParams = array_merge($params, [$perPage, $offset]);
        $citizenExpr = $this->citizenNameExpr('v');

        // Query completa: citizen via citizenNameExpr (tb_fat_cidadao_pec) + nu_hora
        $sqlFull = "
            SELECT
                v.co_seq_fat_visita_domiciliar   AS id,
                t.dt_registro                    AS data,
                t.nu_hora                        AS hora,
                {$citizenExpr}                   AS cidadao,
                p.no_profissional                AS agente,
                c.nu_cbo                         AS cbo,
                e.nu_ine                         AS equipe_ine,
                e.no_equipe                      AS equipe_nome,
                v.nu_micro_area                  AS micro_area,
                {$this->instrumentExpr()}        AS instrumento,
                d.co_seq_dim_desfecho_visita     AS desfecho_id,
                d.ds_desfecho_visita             AS desfecho_label,
                CASE WHEN v.nu_latitude IS NOT NULL AND v.nu_longitude IS NOT NULL
                     THEN true ELSE false END    AS has_geo,
                v.st_acomp_gestante,
                v.st_acomp_puerpera,
                v.st_acomp_recem_nascido,
                v.st_acomp_crianca,
                v.st_acomp_pessoa_hipertensao,
                v.st_acomp_pessoa_diabetes,
                v.st_acomp_pessoa_cancer,
                v.st_acomp_pessoa_idosa,
                v.st_acomp_saude_mental,
                v.st_acomp_tabagista,
                v.st_acomp_domiciliados_acamados,
                v.st_acomp_pessoa_tuberculose,
                v.st_acomp_pessoa_hanseniase,
                v.st_acomp_condi_bolsa_familia,
                COUNT(*) OVER()                  AS total_count
            FROM tb_fat_visita_domiciliar v
            {$this->baseJoins()}
            WHERE {$where}
            ORDER BY t.dt_registro DESC, v.co_seq_fat_visita_domiciliar DESC
            LIMIT ? OFFSET ?
        ";

        // Fallback seguro: sem LATERAL e sem nu_hora (tabelas base garantidas)
        $sqlBase = "
            SELECT
                v.co_seq_fat_visita_domiciliar   AS id,
                t.dt_registro                    AS data,
                {$citizenExpr}                   AS cidadao,
                p.no_profissional                AS agente,
                c.nu_cbo                         AS cbo,
                e.nu_ine                         AS equipe_ine,
                e.no_equipe                      AS equipe_nome,
                v.nu_micro_area                  AS micro_area,
                {$this->instrumentExpr()}        AS instrumento,
                d.co_seq_dim_desfecho_visita     AS desfecho_id,
                d.ds_desfecho_visita             AS desfecho_label,
                CASE WHEN v.nu_latitude IS NOT NULL AND v.nu_longitude IS NOT NULL
                     THEN true ELSE false END    AS has_geo,
                v.st_acomp_gestante,
                v.st_acomp_puerpera,
                v.st_acomp_recem_nascido,
                v.st_acomp_crianca,
                v.st_acomp_pessoa_hipertensao,
                v.st_acomp_pessoa_diabetes,
                v.st_acomp_pessoa_cancer,
                v.st_acomp_pessoa_idosa,
                v.st_acomp_saude_mental,
                v.st_acomp_tabagista,
                v.st_acomp_domiciliados_acamados,
                v.st_acomp_pessoa_tuberculose,
                v.st_acomp_pessoa_hanseniase,
                v.st_acomp_condi_bolsa_familia,
                COUNT(*) OVER()                  AS total_count
            FROM tb_fat_visita_domiciliar v
            {$this->baseJoins()}
            WHERE {$where}
            ORDER BY t.dt_registro DESC, v.co_seq_fat_visita_domiciliar DESC
            LIMIT ? OFFSET ?
        ";

        $sql = $this->hasColumn('tb_dim_tempo', 'nu_hora') ? $sqlFull : $sqlBase;

        try {
            $rows = $this->db()->select($sql, $queryParams);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('VisitaAcs.lista: ' . $e->getMessage());
            return response()->json(['error' => 'Não foi possível consultar o banco eSUS PEC.'], 503);
        }

        $total = (int) ($rows[0]->total_count ?? 0);

        $visitas = array_map(fn ($r) => [
            'id' => (int) $r->id,
            'data' => $r->data,
            'hora' => isset($r->hora) ? (int) $r->hora : null,
            'cidadao' => $r->cidadao ?? null,
            'agente' => $r->agente,
            'cbo' => self::CBO_LABELS[$r->cbo] ?? $r->cbo,
            'equipe' => ['ine' => $r->equipe_ine, 'nome' => $r->equipe_nome],
            'micro_area' => $r->micro_area,
            'instrumento' => $r->instrumento,
            'desfecho_id' => (int) $r->desfecho_id,
            'desfecho_label' => $r->desfecho_label,
            'has_geo' => (bool) $r->has_geo,
            'acompanhamentos' => $this->formatAccompaniments($r),
        ], $rows);

        return response()->json(['visitas' => $visitas, 'total' => $total]);
    }

    /**
     * GET /visitas/{id}
     * Detalhe completo Ã¢â‚¬â€ abre ao clicar em "Ver" ou em um pin no mapa.
     */
    public function show(int $id): JsonResponse
    {
        $hasHora = $this->hasColumn('tb_dim_tempo', 'nu_hora');

        $notesExpr = $this->buildNotesExpr('v');

        $citizenNameExpr = $this->citizenNameExpr('v');

        // Endereço — fonte 1: tb_fat_cad_individual (Ficha de Cadastro Individual, por cidadão)
        $indHasTable = $this->hasTable('tb_fat_cad_individual')
                    && $this->hasColumn('tb_fat_cad_individual', 'co_fat_cidadao_pec');
        $indLogCol   = $indHasTable ? $this->firstExistingColumn('tb_fat_cad_individual', ['ds_logradouro', 'no_logradouro']) : null;
        $indNumCol   = $indHasTable ? $this->firstExistingColumn('tb_fat_cad_individual', ['nu_numero', 'nu_num_logradouro']) : null;
        $indCompCol  = $indHasTable ? $this->firstExistingColumn('tb_fat_cad_individual', ['ds_complemento', 'no_complemento']) : null;
        $indBaiCol   = $indHasTable ? $this->firstExistingColumn('tb_fat_cad_individual', ['ds_bairro', 'no_bairro']) : null;
        $indCepCol   = $indHasTable ? $this->firstExistingColumn('tb_fat_cad_individual', ['nu_cep']) : null;
        $indBase     = $indHasTable ? 'FROM tb_fat_cad_individual ci WHERE ci.co_fat_cidadao_pec = v.co_fat_cidadao_pec LIMIT 1' : null;

        // Endereço — fonte 2: DW (tb_fat_cad_dom_familia → tb_fat_cad_domiciliar)
        $domPkCol         = $this->firstExistingColumn('tb_fat_cad_domiciliar', ['co_seq_fat_cad_domiciliar']);
        $familyDomFkCol   = $this->firstExistingColumn('tb_fat_cad_dom_familia', ['co_fat_cad_domiciliar']);
        $familyCitizenCol = $this->firstExistingColumn('tb_fat_cad_dom_familia', ['co_fat_cidadao_pec', 'co_seq_fat_cidadao_pec']);
        $logradouroCol    = $this->firstExistingColumn('tb_fat_cad_domiciliar', ['no_logradouro', 'ds_logradouro', 'logradouro']);
        $numeroCol        = $this->firstExistingColumn('tb_fat_cad_domiciliar', ['nu_num_logradouro', 'nu_numero', 'nu_endereco']);
        $complementoCol   = $this->firstExistingColumn('tb_fat_cad_domiciliar', ['no_complemento', 'ds_complemento', 'complemento']);
        $bairroCol        = $this->firstExistingColumn('tb_fat_cad_domiciliar', ['no_bairro', 'ds_bairro', 'bairro']);
        $cepCol           = $this->firstExistingColumn('tb_fat_cad_domiciliar', ['nu_cep', 'cep']);

        $dwAddrBase = ($domPkCol && $familyDomFkCol && $familyCitizenCol)
            ? "FROM tb_fat_cad_dom_familia f JOIN tb_fat_cad_domiciliar d ON d.{$domPkCol} = f.{$familyDomFkCol} WHERE f.{$familyCitizenCol} = v.co_fat_cidadao_pec LIMIT 1"
            : null;

        // Endereço — fonte 3: tb_cidadao (tabela OLTP mestre)
        $cidPkCol    = $this->firstExistingColumn('tb_cidadao', ['co_seq_cidadao']);
        $pecCidCol   = $this->firstExistingColumn('tb_fat_cidadao_pec', ['co_cidadao']);
        $cidLogCol   = $this->firstExistingColumn('tb_cidadao', ['no_logradouro', 'ds_logradouro']);
        $cidNumCol   = $this->firstExistingColumn('tb_cidadao', ['nu_numero', 'nu_num_logradouro']);
        $cidCompCol  = $this->firstExistingColumn('tb_cidadao', ['ds_complemento', 'no_complemento']);
        $cidBaiCol   = $this->firstExistingColumn('tb_cidadao', ['no_bairro', 'ds_bairro']);
        $cidCepCol   = $this->firstExistingColumn('tb_cidadao', ['nu_cep']);

        $cidAddrBase = ($cidPkCol && $pecCidCol)
            ? "FROM tb_cidadao cid JOIN tb_fat_cidadao_pec cp ON cp.{$pecCidCol} = cid.{$cidPkCol} WHERE cp.co_seq_fat_cidadao_pec = v.co_fat_cidadao_pec LIMIT 1"
            : null;

        $makeAddr = function (?string $indCol, ?string $dwCol, ?string $cidCol) use ($indBase, $dwAddrBase, $cidAddrBase): string {
            $ind = $indBase     && $indCol ? "(SELECT ci.{$indCol}::text {$indBase})"      : null;
            $dw  = $dwAddrBase  && $dwCol  ? "(SELECT d.{$dwCol}::text {$dwAddrBase})"    : null;
            $cid = $cidAddrBase && $cidCol ? "(SELECT cid.{$cidCol}::text {$cidAddrBase})" : null;
            $parts = array_filter([$ind, $dw, $cid]);
            if (count($parts) > 1) return 'COALESCE(' . implode(', ', $parts) . ')';
            return $parts[0] ?? 'NULL::text';
        };

        $logradouroExpr  = $makeAddr($indLogCol,  $logradouroCol, $cidLogCol);
        $numeroExpr      = $makeAddr($indNumCol,  $numeroCol,     $cidNumCol);
        $complementoExpr = $makeAddr($indCompCol, $complementoCol, $cidCompCol);
        $bairroExpr      = $makeAddr($indBaiCol,  $bairroCol,     $cidBaiCol);
        $cepExpr         = $makeAddr($indCepCol,  $cepCol,        $cidCepCol);

        $sqlFull = "
            SELECT
                v.co_seq_fat_visita_domiciliar   AS id,
                p.no_profissional                AS agent_name,
                c.nu_cbo                         AS cbo,
                e.nu_ine                         AS team_ine,
                e.no_equipe                      AS team_name,
                t.dt_registro                    AS visited_date,
                t.nu_hora                        AS hora,
                {$this->instrumentExpr()}        AS instrument_label,
                d.co_seq_dim_desfecho_visita     AS outcome_code,
                d.ds_desfecho_visita             AS outcome_label,
                v.nu_latitude                    AS lat,
                v.nu_longitude                   AS lng,
                v.co_fat_cidadao_pec            AS citizen_pec,
                {$notesExpr}                     AS notes,
                {$citizenNameExpr}               AS citizen_name,
                {$logradouroExpr}                AS logradouro,
                {$numeroExpr}                    AS num_endereco,
                {$complementoExpr}               AS complemento,
                {$bairroExpr}                    AS bairro,
                {$cepExpr}                       AS cep,
                v.st_mot_vis_cad_att,
                v.st_mot_vis_visita_periodica,
                v.st_mot_vis_busca_ativa,
                v.st_mot_vis_acompanhamento,
                v.st_mot_vis_egresso_internacao,
                v.st_mot_vis_ctrl_ambnte_vetor,
                v.st_mot_vis_convte_atvidd_cltva,
                v.st_mot_vis_orintacao_prevncao,
                v.st_mot_vis_outros,
                v.st_acomp_gestante,
                v.st_acomp_puerpera,
                v.st_acomp_recem_nascido,
                v.st_acomp_crianca,
                v.st_acomp_pessoa_hipertensao,
                v.st_acomp_pessoa_diabetes,
                v.st_acomp_pessoa_cancer,
                v.st_acomp_pessoa_idosa,
                v.st_acomp_saude_mental,
                v.st_acomp_tabagista,
                v.st_acomp_domiciliados_acamados,
                v.st_acomp_pessoa_tuberculose,
                v.st_acomp_pessoa_hanseniase,
                v.st_acomp_condi_bolsa_familia,
                CASE WHEN v.nu_latitude IS NOT NULL AND v.nu_longitude IS NOT NULL
                     THEN true ELSE false END    AS has_geo
            FROM tb_fat_visita_domiciliar v
            {$this->baseJoins()}
            WHERE v.co_seq_fat_visita_domiciliar = ?
        ";

        $sqlBase = "
            SELECT
                v.co_seq_fat_visita_domiciliar   AS id,
                p.no_profissional                AS agent_name,
                c.nu_cbo                         AS cbo,
                e.nu_ine                         AS team_ine,
                e.no_equipe                      AS team_name,
                t.dt_registro                    AS visited_date,
                {$this->instrumentExpr()}        AS instrument_label,
                d.co_seq_dim_desfecho_visita     AS outcome_code,
                d.ds_desfecho_visita             AS outcome_label,
                v.nu_latitude                    AS lat,
                v.nu_longitude                   AS lng,
                v.co_fat_cidadao_pec            AS citizen_pec,
                {$notesExpr}                     AS notes,
                {$citizenNameExpr}               AS citizen_name,
                {$logradouroExpr}                AS logradouro,
                {$numeroExpr}                    AS num_endereco,
                {$complementoExpr}               AS complemento,
                {$bairroExpr}                    AS bairro,
                {$cepExpr}                       AS cep,
                v.st_mot_vis_cad_att,
                v.st_mot_vis_visita_periodica,
                v.st_mot_vis_busca_ativa,
                v.st_mot_vis_acompanhamento,
                v.st_mot_vis_egresso_internacao,
                v.st_mot_vis_ctrl_ambnte_vetor,
                v.st_mot_vis_convte_atvidd_cltva,
                v.st_mot_vis_orintacao_prevncao,
                v.st_mot_vis_outros,
                v.st_acomp_gestante,
                v.st_acomp_puerpera,
                v.st_acomp_recem_nascido,
                v.st_acomp_crianca,
                v.st_acomp_pessoa_hipertensao,
                v.st_acomp_pessoa_diabetes,
                v.st_acomp_pessoa_cancer,
                v.st_acomp_pessoa_idosa,
                v.st_acomp_saude_mental,
                v.st_acomp_tabagista,
                v.st_acomp_domiciliados_acamados,
                v.st_acomp_pessoa_tuberculose,
                v.st_acomp_pessoa_hanseniase,
                v.st_acomp_condi_bolsa_familia,
                CASE WHEN v.nu_latitude IS NOT NULL AND v.nu_longitude IS NOT NULL
                     THEN true ELSE false END    AS has_geo
            FROM tb_fat_visita_domiciliar v
            {$this->baseJoins()}
            WHERE v.co_seq_fat_visita_domiciliar = ?
        ";

        $sql = $hasHora ? $sqlFull : $sqlBase;

        try {
            $row = $this->db()->selectOne($sql, [$id]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('VisitaAcs.show: ' . $e->getMessage());
            return response()->json(['error' => 'Não foi possível consultar o banco eSUS PEC.'], 503);
        }

        if (! $row) {
            return response()->json(['message' => 'Visita não encontrada.'], 404);
        }

        $result = $this->formatVisita($row, detail: true);
        $result['_debug'] = [
            'notes_expr'      => $notesExpr,
            'notes_probes'    => [
                'acs_table'      => $this->hasTable('tb_visita_domiciliar_acs'),
                'acs_fat_uuid'   => $this->firstExistingColumn('tb_fat_visita_domiciliar', ['nu_uuid_ficha', 'co_unico_ficha', 'nu_uuid']),
                'acs_uuid_col'   => $this->firstExistingColumn('tb_visita_domiciliar_acs', ['co_unico_visita_domiciliar', 'nu_uuid_ficha', 'nu_uuid', 'co_unico']),
                'acs_annot_col'  => $this->firstExistingColumn('tb_visita_domiciliar_acs', ['ds_anotacao', 'ds_observacao', 'ds_relato', 'ds_anotacao_visita']),
                'fat_direct_col' => $this->firstExistingColumn('tb_fat_visita_domiciliar', ['ds_anotacao', 'ds_observacao', 'ds_relato']),
                'fat_cds_fk'     => $this->firstExistingColumn('tb_fat_visita_domiciliar', ['co_cds_visita_domiciliar', 'co_seq_cds_visita_domiciliar']),
                'cds_annot_col'  => $this->firstExistingColumn('tb_cds_visita_domiciliar', ['ds_anotacao', 'ds_observacao', 'ds_relato']),
            ],
            'nu_hora_exists'  => $this->hasColumn('tb_dim_tempo', 'nu_hora'),
            'addr_ind_base'  => $indBase     ? 'OK' : 'null',
            'addr_ind_cols'  => compact('indLogCol', 'indNumCol', 'indCompCol', 'indBaiCol', 'indCepCol'),
            'addr_ind_all_cols' => $this->listColumns('tb_fat_cad_individual'),
            'addr_dw_base'   => $dwAddrBase  ? 'OK' : 'null',
            'addr_dw_all_cols' => $this->listColumns('tb_fat_cad_domiciliar'),
            'addr_cid_base'  => $cidAddrBase ? 'OK' : 'null',
            'addr_cid_cols'  => compact('cidLogCol', 'cidNumCol', 'cidCompCol', 'cidBaiCol', 'cidCepCol'),
        ];
        return response()->json($result);
    }

    /**
     * GET /visitas/mapa?ano=X&mes=Y[&ine=Z&agente=W]
     * Pins georreferenciados Ã¢â‚¬â€ aba Mapa do VisitasAcs.js.
     * Inclui equipe_ine para coloraÃƒÂ§ÃƒÂ£o por equipe no modo "Todos".
     */
    public function mapa(Request $request): JsonResponse
    {
        $request->validate([
            'ano' => 'required|integer|min:2020|max:2030',
            'mes' => 'required|integer|min:1|max:12',
            'ine' => 'nullable|string',
            'agente' => 'nullable|string',
            'busca' => 'nullable|string|max:200',
        ]);

        $ano = (int) $request->ano;
        $mes = (int) $request->mes;

        $ine = $request->query('ine') ?: null;
        $this->assertIneAllowed($request, $ine);
        $allowedInes = $this->resolveAllowedInes($request);

        [$where, $params] = $this->buildWhere($ano, $mes, $ine, $request->agente, null, null, $allowedInes);

        // Resolve column names for CPF/CNS/nome based on database schema.
        $visitCpfCol = $this->firstExistingColumn('tb_fat_visita_domiciliar', ['nu_cpf_cidadao', 'nu_cpf']);
        $visitCnsCol = $this->firstExistingColumn('tb_fat_visita_domiciliar', ['nu_cns', 'nu_cns_cidadao']);
        $cpfCol      = $this->firstExistingColumn('tb_fat_cad_individual', ['nu_cpf_cidadao', 'nu_cpf', 'co_cpf']);
        $cnsCol      = $this->firstExistingColumn('tb_fat_cad_individual', ['nu_cns', 'co_cns']);
        $nomeCol     = $this->firstExistingColumn('tb_fat_cad_individual', ['no_cidadao', 'no_nome', 'no_nome_social']);
        $pecCpfCol   = $this->firstExistingColumn('tb_fat_cidadao_pec', ['nu_cpf_cidadao', 'nu_cpf']);
        $pecCnsCol   = $this->firstExistingColumn('tb_fat_cidadao_pec', ['nu_cns']);
        $pecNomeCol  = $this->firstExistingColumn('tb_fat_cidadao_pec', ['no_cidadao', 'no_social_cidadao']);
        $cidCpfCol   = $this->firstExistingColumn('tb_cidadao', ['nu_cpf']);
        $cidCnsCol   = $this->firstExistingColumn('tb_cidadao', ['nu_cns']);
        $cidNomeCol  = $this->firstExistingColumn('tb_cidadao', ['no_cidadao', 'no_cidadao_filtro']);
        $pecCidCol   = $this->firstExistingColumn('tb_fat_cidadao_pec', ['co_cidadao']);
        $cidPkCol    = $this->firstExistingColumn('tb_cidadao', ['co_seq_cidadao']);

        if ($request->busca) {
            $busca  = trim($request->busca);
            $digits = preg_replace('/\D/', '', $busca);
            $likeBusca = '%' . $busca . '%';
            $likeDigits = '%' . $digits . '%';
            $searchWhere = [];
            $searchParams = [];

            if ($digits !== '') {
                foreach ([['v', $visitCpfCol], ['v', $visitCnsCol]] as [$alias, $col]) {
                    if ($col) {
                        $searchWhere[] = "REGEXP_REPLACE(COALESCE({$alias}.{$col}::text, ''), '\\D', '', 'g') LIKE ?";
                        $searchParams[] = $likeDigits;
                    }
                }
            }

            $cadWhere = [];
            $cadParams = [];
            if ($digits !== '') {
                foreach (array_filter([$cpfCol, $cnsCol]) as $col) {
                    $cadWhere[] = "REGEXP_REPLACE(COALESCE(ci.{$col}::text, ''), '\\D', '', 'g') LIKE ?";
                    $cadParams[] = $likeDigits;
                }
            }
            if ($nomeCol) {
                $cadWhere[] = "ci.{$nomeCol} ILIKE ?";
                $cadParams[] = $likeBusca;
            }
            if ($cadWhere) {
                $searchWhere[] = "EXISTS (
                    SELECT 1
                    FROM tb_fat_cad_individual ci
                    WHERE ci.co_fat_cidadao_pec = v.co_fat_cidadao_pec
                      AND (" . implode(' OR ', $cadWhere) . ")
                )";
                array_push($searchParams, ...$cadParams);
            }

            $pecWhere = [];
            $pecParams = [];
            $cidWhere = [];
            $cidParams = [];
            if ($digits !== '') {
                foreach (array_filter([$pecCpfCol, $pecCnsCol]) as $col) {
                    $pecWhere[] = "REGEXP_REPLACE(COALESCE(cp.{$col}::text, ''), '\\D', '', 'g') LIKE ?";
                    $pecParams[] = $likeDigits;
                }
                foreach (array_filter([$cidCpfCol, $cidCnsCol]) as $col) {
                    $cidWhere[] = "REGEXP_REPLACE(COALESCE(cid.{$col}::text, ''), '\\D', '', 'g') LIKE ?";
                    $cidParams[] = $likeDigits;
                }
            }
            if ($pecNomeCol) {
                $pecWhere[] = "cp.{$pecNomeCol} ILIKE ?";
                $pecParams[] = $likeBusca;
            }
            if ($cidNomeCol) {
                $cidWhere[] = "cid.{$cidNomeCol} ILIKE ?";
                $cidParams[] = $likeBusca;
            }
            if ($cidWhere && $pecCidCol && $cidPkCol) {
                $pecWhere = array_merge($pecWhere, $cidWhere);
                $pecParams = array_merge($pecParams, $cidParams);
                $searchWhere[] = "EXISTS (
                    SELECT 1
                    FROM tb_fat_cidadao_pec cp
                    LEFT JOIN tb_cidadao cid ON cid.{$cidPkCol} = cp.{$pecCidCol}
                    WHERE cp.co_seq_fat_cidadao_pec = v.co_fat_cidadao_pec
                      AND (" . implode(' OR ', $pecWhere) . ")
                )";
                array_push($searchParams, ...$pecParams);
            } elseif ($pecWhere) {
                $searchWhere[] = "EXISTS (
                    SELECT 1
                    FROM tb_fat_cidadao_pec cp
                    WHERE cp.co_seq_fat_cidadao_pec = v.co_fat_cidadao_pec
                      AND (" . implode(' OR ', $pecWhere) . ")
                )";
                array_push($searchParams, ...$pecParams);
            }

            if ($searchWhere) {
                $where .= ' AND (' . implode(' OR ', $searchWhere) . ')';
                array_push($params, ...$searchParams);
            }
        }
        $citizenExpr = $this->citizenNameExpr('v');
        $cadNomeSelect = $nomeCol
            ? "NULLIF(CASE WHEN {$nomeCol} ~* '^[0-9a-f]{32,}$'"
              . "             OR {$nomeCol} ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$'"
              . "          THEN NULL ELSE {$nomeCol} END, '')"
            : 'NULL::text';

        // Endereço do domicílio — pega o cadastro mais recente por cidadão
        $hasDomAddr = $this->hasDomicilioCadastro()
            && $this->hasColumn('tb_fat_cad_dom_familia', 'co_fat_cidadao_pec');
        $logCol  = $hasDomAddr ? $this->firstExistingColumn('tb_fat_cad_domiciliar', ['no_logradouro', 'ds_logradouro', 'logradouro']) : null;
        $numCol  = $hasDomAddr ? $this->firstExistingColumn('tb_fat_cad_domiciliar', ['nu_num_logradouro', 'nu_numero', 'nu_endereco']) : null;
        $compCol = $hasDomAddr ? $this->firstExistingColumn('tb_fat_cad_domiciliar', ['no_complemento', 'ds_complemento', 'complemento']) : null;
        $baiCol  = $hasDomAddr ? $this->firstExistingColumn('tb_fat_cad_domiciliar', ['no_bairro', 'ds_bairro', 'bairro']) : null;
        $famFilters = $this->domicilioFamiliaFilters('fdom');
        $addrCols = "\n                NULL::text AS logradouro, NULL::text AS num_endereco,\n                NULL::text AS complemento, NULL::text AS bairro";
        $addrLateral = '';
        if ($hasDomAddr && $logCol) {
            $hashGuard = fn(string $col) =>
                "NULLIF(CASE WHEN {$col} ~* '^[0-9a-f]{32,}$'"
                . " OR {$col} ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$'"
                . " THEN NULL ELSE {$col} END, '')";
            $lExpr = $hashGuard("d_addr.{$logCol}::text");
            $nExpr = $numCol  ? $hashGuard("d_addr.{$numCol}::text")  : 'NULL::text';
            $cExpr = $compCol ? $hashGuard("d_addr.{$compCol}::text") : 'NULL::text';
            $bExpr = $baiCol  ? $hashGuard("d_addr.{$baiCol}::text")  : 'NULL::text';
            $addrCols = "\n                addr_dom.logradouro, addr_dom.num_endereco,\n                addr_dom.complemento, addr_dom.bairro";
            $addrLateral = "
            LEFT JOIN LATERAL (
                SELECT {$lExpr} AS logradouro, {$nExpr} AS num_endereco,
                       {$cExpr} AS complemento, {$bExpr} AS bairro
                FROM tb_fat_cad_dom_familia fdom
                JOIN tb_fat_cad_domiciliar d_addr
                    ON d_addr.co_seq_fat_cad_domiciliar = fdom.co_fat_cad_domiciliar
                WHERE fdom.co_fat_cidadao_pec = v.co_fat_cidadao_pec
                  AND {$famFilters}
                ORDER BY fdom.co_fat_cad_domiciliar DESC
                LIMIT 1
            ) addr_dom ON true";
        }

        // Query completa: LATERAL direto no FROM + nu_hora + nome do cidadão
        $sqlFull = "
            SELECT
                v.co_seq_fat_visita_domiciliar   AS id,
                v.nu_latitude::float             AS lat,
                v.nu_longitude::float            AS lng,
                p.no_profissional                AS agente,
                c.nu_cbo                         AS cbo,
                e.nu_ine                         AS equipe_ine,
                e.no_equipe                      AS equipe_nome,
                t.dt_registro                    AS data,
                t.nu_hora                        AS hora,
                d.co_seq_dim_desfecho_visita     AS desfecho,
                v.nu_micro_area                  AS micro_area,
                {$citizenExpr}                   AS cidadao,
                {$addrCols}
            FROM tb_fat_visita_domiciliar v
            {$this->baseJoins()}
            {$addrLateral}
            WHERE {$where}
              AND v.nu_latitude  IS NOT NULL
              AND v.nu_longitude IS NOT NULL
            ORDER BY t.dt_registro DESC
        ";

        // Fallback seguro: sem LATERAL e sem nu_hora Ã¢â‚¬â€ tabelas base garantidas
        $sqlBase = "
            SELECT
                v.co_seq_fat_visita_domiciliar   AS id,
                v.nu_latitude::float             AS lat,
                v.nu_longitude::float            AS lng,
                p.no_profissional                AS agente,
                c.nu_cbo                         AS cbo,
                e.nu_ine                         AS equipe_ine,
                e.no_equipe                      AS equipe_nome,
                t.dt_registro                    AS data,
                d.co_seq_dim_desfecho_visita     AS desfecho,
                v.nu_micro_area                  AS micro_area,
                {$citizenExpr}                   AS cidadao,
                {$addrCols}
            FROM tb_fat_visita_domiciliar v
            {$this->baseJoins()}
            {$addrLateral}
            WHERE {$where}
              AND v.nu_latitude  IS NOT NULL
              AND v.nu_longitude IS NOT NULL
            ORDER BY t.dt_registro DESC
        ";

        $sql = $this->hasColumn('tb_dim_tempo', 'nu_hora') ? $sqlFull : $sqlBase;

        \Illuminate\Support\Facades\Log::info('VisitaAcs.mapa SQL: ' . preg_replace('/\s+/', ' ', $sql));

        try {
            $rows = $this->db()->select($sql, $params);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('VisitaAcs.mapa: ' . $e->getMessage());
            return response()->json(['error' => 'Não foi possível consultar o banco eSUS PEC.'], 503);
        }

        $pontos = array_map(fn ($r) => [
            'id'           => (int) $r->id,
            'lat'          => (float) $r->lat,
            'lng'          => (float) $r->lng,
            'agente'       => $r->agente,
            'cbo'          => self::CBO_LABELS[$r->cbo] ?? $r->cbo,
            'equipe_ine'   => $r->equipe_ine,
            'equipe'       => $r->equipe_nome,
            'cidadao'      => $r->cidadao ?? null,
            'logradouro'   => $r->logradouro   ?? null,
            'num_endereco' => $r->num_endereco  ?? null,
            'complemento'  => $r->complemento   ?? null,
            'bairro'       => $r->bairro         ?? null,
            'data'         => $r->data,
            'hora'         => isset($r->hora) ? (int) $r->hora : null,
            'desfecho'     => (int) $r->desfecho,
            'micro_area'   => $r->micro_area,
        ], $rows);

        return response()->json(['pontos' => $pontos]);
    }

    /**
     * GET /visitas/equipes
     */
    public function equipes(Request $request): JsonResponse
    {
        $allowedInes = $this->resolveAllowedInes($request);
        [$ineWhere, $ineParams] = $this->buildIneWhere(null, $allowedInes, 'nu_ine');

        $extraWhere = $ineWhere ? " AND {$ineWhere}" : '';

        try {
            $rows = $this->db()->select("
                SELECT nu_ine AS ine, no_equipe AS name
                FROM tb_dim_equipe
                WHERE st_registro_valido = 1 AND nu_ine != '-'{$extraWhere}
                ORDER BY no_equipe
            ", $ineParams);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('VisitaAcs.equipes: ' . $e->getMessage());
            return response()->json(['error' => 'Não foi possível consultar o banco eSUS PEC.'], 503);
        }

        return response()->json(['data' => $rows]);
    }

    /**
     * GET /visitas/agentes?ano=X&mes=Y[&ine=Z]
     * EstatÃƒÂ­sticas agregadas por agente Ã¢â‚¬â€ aba Por Agente.
     */
    public function agentes(Request $request): JsonResponse
    {
        $request->validate([
            'ano' => 'required|integer|min:2020|max:2030',
            'mes' => 'required|integer|min:1|max:12',
            'ine' => 'nullable|string',
            'agente' => 'nullable|string',
            'desfecho' => 'nullable|integer|in:1,2,3',
            'has_geo' => 'nullable|string|in:sim,nao',
        ]);

        $ano = (int) $request->ano;
        $mes = (int) $request->mes;

        $ine = $request->query('ine') ?: null;
        $this->assertIneAllowed($request, $ine);
        $allowedInes = $this->resolveAllowedInes($request);

        [$where, $params] = $this->buildWhere(
            $ano, $mes, $ine,
            $request->agente,
            $request->desfecho,
            $request->has_geo,
            $allowedInes,
        );

        $familyExpr = $this->familyIdExpr();
        $hasFamilies = $familyExpr !== null;

        $familyCols = $hasFamilies ? ",
            COUNT(DISTINCT CASE WHEN d.co_seq_dim_desfecho_visita = 1 THEN {$familyExpr} END) AS familias_acompanhadas"
            : '';

        $familyJoin = $hasFamilies
            ? 'LEFT JOIN tb_fat_cad_individual ci ON ci.co_fat_cidadao_pec = v.co_fat_cidadao_pec'
            : '';

        try {
            $rows = $this->db()->select("
                SELECT
                    p.no_profissional                                                     AS agente,
                    c.nu_cbo                                                              AS cbo,
                    e.no_equipe                                                           AS equipe_nome,
                    COUNT(DISTINCT v.co_seq_fat_visita_domiciliar) AS total,
                    COUNT(DISTINCT CASE WHEN d.co_seq_dim_desfecho_visita = 1 THEN v.co_seq_fat_visita_domiciliar END) AS realizadas,
                    COUNT(DISTINCT CASE WHEN d.co_seq_dim_desfecho_visita = 2 THEN v.co_seq_fat_visita_domiciliar END) AS recusadas,
                    COUNT(DISTINCT CASE WHEN d.co_seq_dim_desfecho_visita = 3 THEN v.co_seq_fat_visita_domiciliar END) AS ausentes,
                    COUNT(DISTINCT v.co_fat_cidadao_pec)                                 AS cidadaos
                    {$familyCols}
                FROM tb_fat_visita_domiciliar v
                {$this->baseJoins()}
                {$familyJoin}
                WHERE {$where}
                GROUP BY p.no_profissional, c.nu_cbo, e.no_equipe
                ORDER BY total DESC
            ", $params);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('VisitaAcs.agentes: ' . $e->getMessage());
            return response()->json(['error' => 'Não foi possível consultar o banco eSUS PEC.'], 503);
        }

        // Total de famílias por agente — baseado em vínculos cadastrais, não visitas
        $famTotalMap = [];
        if ($hasFamilies) {
            try {
                $totFamWhere  = 'ci.st_ficha_inativa = 0 AND de.st_registro_valido = 1';
                $totFamParams = [];

                if ($ine) {
                    $totFamWhere  .= ' AND de.nu_ine = ?';
                    $totFamParams[] = $ine;
                } elseif ($allowedInes !== null) {
                    if (empty($allowedInes)) {
                        $totFamWhere .= ' AND 1=0';
                    } else {
                        $ph = implode(',', array_fill(0, count($allowedInes), '?'));
                        $totFamWhere .= " AND de.nu_ine IN ({$ph})";
                        $totFamParams = array_merge($totFamParams, $allowedInes);
                    }
                }
                if ($request->agente && trim($request->agente) !== '') {
                    $totFamWhere  .= ' AND ' . $this->agentFilterClause('dp.no_profissional');
                    $totFamParams[] = $this->agentFilterValue($request->agente);
                }

                $totFamRows = $this->db()->select("
                    SELECT
                        dp.no_profissional AS agente,
                        COUNT(DISTINCT {$familyExpr}) AS familias_total
                    FROM tb_fat_cad_individual ci
                    JOIN tb_dim_equipe de
                        ON de.co_seq_dim_equipe = ci.co_dim_equipe
                    LEFT JOIN tb_dim_profissional dp
                        ON dp.co_seq_dim_profissional = ci.co_dim_profissional
                    WHERE {$totFamWhere}
                    GROUP BY dp.no_profissional
                ", $totFamParams);

                foreach ($totFamRows as $tfRow) {
                    $famTotalMap[$tfRow->agente ?? ''] = (int) ($tfRow->familias_total ?? 0);
                }
            } catch (\Throwable) {}
        }

        $domicilioMap      = $this->domicilioCadastroStatsPorAgente($ine, $request->agente, $allowedInes);
        $domicilioVisitMap = $this->domicilioVisitaStatsPorAgente($ano, $mes, $ine, $request->agente, $allowedInes);

        $agentes = array_map(function ($r) use ($hasFamilies, $famTotalMap, $domicilioMap, $domicilioVisitMap) {
            $famAcomp    = $hasFamilies ? (int) ($r->familias_acompanhadas ?? 0) : null;
            $famTotal    = $hasFamilies ? ($famTotalMap[$r->agente ?? ''] ?? 0) : null;
            $pctFamilias = ($hasFamilies && $famTotal > 0)
                ? (int) round($famAcomp / $famTotal * 100)
                : null;
            $domicilios = $domicilioMap[$r->agente ?? ''] ?? [
                'domicilios_total' => null,
                'domicilios_com_moradores' => null,
                'domicilios_casa_vazia' => null,
            ];
            $domVisita = $domicilioVisitMap[$r->agente ?? ''] ?? [
                'domicilios_visitados'    => null,
                'domicilios_acompanhados' => null,
                'domicilios_recusados'    => null,
                'domicilios_ausentes'     => null,
            ];
            $domComMoradores = (int) ($domicilios['domicilios_com_moradores'] ?? 0);
            $domAcompanhados = $domVisita['domicilios_acompanhados'] ?? null;
            $pctDomAcomp = ($domComMoradores > 0 && $domAcompanhados !== null)
                ? (int) round($domAcompanhados / $domComMoradores * 100)
                : null;

            return [
                'agente'                => $r->agente,
                'cbo'                   => $r->cbo,
                'cbo_nome'              => self::CBO_LABELS[$r->cbo] ?? $r->cbo,
                'equipe'                => ['nome' => $r->equipe_nome],
                'total'                 => (int) $r->total,
                'realizadas'            => (int) $r->realizadas,
                'recusadas'             => (int) $r->recusadas,
                'ausentes'              => (int) $r->ausentes,
                'pct_realizadas'        => $r->total > 0
                    ? (int) round($r->realizadas / $r->total * 100)
                    : 0,
                'cidadaos'              => (int) $r->cidadaos,
                'familias_total'        => $famTotal,
                'familias_acompanhadas' => $famAcomp,
                'pct_familias'          => $pctFamilias,
                'domicilios_visitados'        => $domVisita['domicilios_visitados'],
                'domicilios_acompanhados'     => $domAcompanhados,
                'domicilios_recusados_visita' => $domVisita['domicilios_recusados'],
                'domicilios_ausentes_visita'  => $domVisita['domicilios_ausentes'],
                'pct_dom_acompanhados'        => $pctDomAcomp,
                'domicilios_total'            => $domicilios['domicilios_total'],
                'domicilios_com_moradores'    => $domicilios['domicilios_com_moradores'],
                'domicilios_casa_vazia'       => $domicilios['domicilios_casa_vazia'],
            ];
        }, $rows);

        return response()->json(['agentes' => $agentes]);
    }

    /**
     * GET /visitas/evolucao/anos
     * Retorna os anos distintos que possuem visitas registradas no eSUS PEC,
     * respeitando os filtros de unidade/CNES do contexto autenticado.
     */
    public function anosDisponiveis(Request $request): JsonResponse
    {
        $allowedInes = $this->resolveAllowedInes($request);
        [$where, $params] = $this->buildWhereFilters(null, null, null, null, $allowedInes);

        $sql = "
            SELECT DISTINCT t.nu_ano AS ano
            FROM tb_fat_visita_domiciliar v
            {$this->baseJoins()}
            WHERE {$where}
            ORDER BY ano DESC
        ";

        try {
            $rows = $this->db()->select($sql, $params);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('VisitaAcs.anosDisponiveis: ' . $e->getMessage());
            return response()->json(['error' => 'Não foi possível consultar o banco eSUS PEC.'], 503);
        }

        return response()->json([
            'anos' => array_map(fn($r) => (int) $r->ano, $rows),
        ]);
    }

    /**
     * GET /visitas/evolucao?[ine=Z&agente=W&desfecho=N&has_geo=X&ano=YYYY]
     * Sem ?ano: retorna 3 séries (ano atual e 2 anteriores).
     * Com ?ano=YYYY: retorna 1 série para o ano informado.
     */
    public function evolucao(Request $request): JsonResponse
    {
        $request->validate([
            'ine' => 'nullable|string',
            'agente' => 'nullable|string',
            'desfecho' => 'nullable|integer|in:1,2,3',
            'has_geo' => 'nullable|string|in:sim,nao',
            'ano' => 'nullable|integer|min:2000|max:2099',
        ]);

        $anoAtual = (int) date('Y');
        $anos = $request->ano
            ? [(int) $request->ano]
            : [$anoAtual, $anoAtual - 1, $anoAtual - 2];

        $ine = $request->query('ine') ?: null;
        $this->assertIneAllowed($request, $ine);
        $allowedInes = $this->resolveAllowedInes($request);

        [$where, $params] = $this->buildWhereFilters(
            $ine,
            $request->agente,
            $request->desfecho,
            $request->has_geo,
            $allowedInes,
        );

        $placeholders = implode(',', array_fill(0, count($anos), '?'));
        $where .= " AND t.nu_ano IN ({$placeholders})";
        $params = array_merge($params, $anos);

        $hasDom = $this->hasDomicilioCadastro()
            && $this->hasColumn('tb_fat_cad_dom_familia', 'co_fat_cidadao_pec');

        $familiaFilters  = $this->domicilioFamiliaFilters('f');
        $regularDomicilio = $this->hasColumn('tb_fat_cad_domiciliar', 'nu_micro_area')
            ? "COALESCE(UPPER(TRIM(dom.nu_micro_area)), '') <> 'FA'"
            : 'TRUE';

        if ($hasDom) {
            $sql = "
                WITH citizen_domicilio AS (
                    SELECT DISTINCT ON (f.co_fat_cidadao_pec)
                        f.co_fat_cidadao_pec,
                        f.co_fat_cad_domiciliar
                    FROM tb_fat_cad_dom_familia f
                    WHERE {$familiaFilters}
                    ORDER BY f.co_fat_cidadao_pec, f.co_fat_cad_domiciliar DESC
                )
                SELECT
                    t.nu_ano  AS ano,
                    t.nu_mes  AS mes,
                    COUNT(DISTINCT dom.co_seq_fat_cad_domiciliar) AS total
                FROM tb_fat_visita_domiciliar v
                {$this->baseJoins()}
                LEFT JOIN citizen_domicilio cd
                    ON cd.co_fat_cidadao_pec = v.co_fat_cidadao_pec
                LEFT JOIN tb_fat_cad_domiciliar dom
                    ON dom.co_seq_fat_cad_domiciliar = cd.co_fat_cad_domiciliar
                WHERE {$where}
                  AND dom.co_seq_fat_cad_domiciliar IS NOT NULL
                  AND {$regularDomicilio}
                GROUP BY t.nu_ano, t.nu_mes
                ORDER BY t.nu_ano, t.nu_mes
            ";
        } else {
            $sql = "
                SELECT
                    t.nu_ano  AS ano,
                    t.nu_mes  AS mes,
                    COUNT(DISTINCT v.co_fat_cidadao_pec) AS total
                FROM tb_fat_visita_domiciliar v
                {$this->baseJoins()}
                WHERE {$where}
                GROUP BY t.nu_ano, t.nu_mes
                ORDER BY t.nu_ano, t.nu_mes
            ";
        }

        try {
            $rows = $this->db()->select($sql, $params);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('VisitaAcs.evolucao: ' . $e->getMessage());
            return response()->json(['error' => 'Não foi possível consultar o banco eSUS PEC.'], 503);
        }

        $index = [];
        foreach ($rows as $row) {
            $index[(int) $row->ano][(int) $row->mes] = (int) $row->total;
        }

        $series = array_map(function (int $ano) use ($index): array {
            $meses = [];
            for ($m = 1; $m <= 12; $m++) {
                $meses[] = $index[$ano][$m] ?? 0;
            }

            return ['ano' => $ano, 'meses' => $meses];
        }, $anos);

        return response()->json(['series' => $series]);
    }

    /**
     * GET /visitas/debug/{id}
     * Diagnóstico temporário — mostra quais colunas foram encontradas e qual SQL foi gerado.
     */
    public function showDebug(int $id): JsonResponse
    {
        // --- notas ---
        $notesDirectCol = $this->firstExistingColumn('tb_fat_visita_domiciliar', [
            'ds_anotacao', 'ds_observacao', 'ds_relato',
            'ds_anotacao_visita', 'ds_observacao_visita',
            'tx_anotacao', 'tx_observacao', 'tx_relato',
        ]);
        $fatCdsFk  = $this->firstExistingColumn('tb_fat_visita_domiciliar', ['co_cds_visita_domiciliar', 'co_seq_cds_visita_domiciliar']);
        $cdsAnnot  = $this->firstExistingColumn('tb_cds_visita_domiciliar', ['ds_anotacao', 'ds_observacao', 'ds_relato']);
        $uuidCol   = $this->firstExistingColumn('tb_fat_visita_domiciliar', ['nu_uuid_ficha', 'co_unico_ficha']);
        $fichaPk   = $this->firstExistingColumn('tb_cds_ficha_visita_domiciliar', ['co_seq_cds_ficha_visita_domiciliar']);
        $fichaUuid = $this->firstExistingColumn('tb_cds_ficha_visita_domiciliar', ['nu_uuid_ficha', 'nu_uuid']);
        $cdsFichaFk = $this->firstExistingColumn('tb_cds_visita_domiciliar', ['co_cds_ficha_visita_domiciliar']);
        $cdsCidCol  = $this->firstExistingColumn('tb_cds_visita_domiciliar', ['co_cidadao']);
        $pecCidCol  = $this->firstExistingColumn('tb_fat_cidadao_pec', ['co_cidadao']);

        // --- endereço ---
        $domPkCol         = $this->firstExistingColumn('tb_fat_cad_domiciliar', ['co_seq_fat_cad_domiciliar']);
        $familyDomFkCol   = $this->firstExistingColumn('tb_fat_cad_dom_familia', ['co_fat_cad_domiciliar']);
        $familyCitizenCol = $this->firstExistingColumn('tb_fat_cad_dom_familia', ['co_fat_cidadao_pec', 'co_seq_fat_cidadao_pec']);
        $logradouroCol    = $this->firstExistingColumn('tb_fat_cad_domiciliar', ['no_logradouro', 'ds_logradouro', 'logradouro']);
        $bairroCol        = $this->firstExistingColumn('tb_fat_cad_domiciliar', ['no_bairro', 'ds_bairro', 'bairro']);
        $cepCol           = $this->firstExistingColumn('tb_fat_cad_domiciliar', ['nu_cep', 'cep']);

        // --- resultado bruto da visita ---
        $row = null;
        try {
            $row = $this->db()->selectOne("
                SELECT v.co_seq_fat_visita_domiciliar AS id,
                       v.co_fat_cidadao_pec,
                       {$this->buildNotesExpr('v')} AS notes_result,
                       (SELECT d.no_logradouro::text
                        FROM tb_fat_cad_dom_familia f
                        JOIN tb_fat_cad_domiciliar d ON d.co_seq_fat_cad_domiciliar = f.co_fat_cad_domiciliar
                        WHERE f.co_fat_cidadao_pec = v.co_fat_cidadao_pec LIMIT 1) AS addr_test
                FROM tb_fat_visita_domiciliar v
                WHERE v.co_seq_fat_visita_domiciliar = ?
            ", [$id]);
        } catch (\Throwable $e) {
            $row = ['error' => $e->getMessage()];
        }

        return response()->json([
            'notes' => [
                'fat_direct_col'   => $notesDirectCol,
                'fat_cds_fk'       => $fatCdsFk,
                'cds_annot_col'    => $cdsAnnot,
                'fat_uuid_col'     => $uuidCol,
                'ficha_pk'         => $fichaPk,
                'ficha_uuid_col'   => $fichaUuid,
                'cds_ficha_fk'     => $cdsFichaFk,
                'cds_cidadao_col'  => $cdsCidCol,
                'pec_cidadao_col'  => $pecCidCol,
                'expr_used'        => $this->buildNotesExpr('v'),
            ],
            'address' => [
                'dom_pk'           => $domPkCol,
                'family_dom_fk'    => $familyDomFkCol,
                'family_citizen'   => $familyCitizenCol,
                'logradouro_col'   => $logradouroCol,
                'bairro_col'       => $bairroCol,
                'cep_col'          => $cepCol,
                'address_join_ok'  => ($domPkCol && $familyDomFkCol && $familyCitizenCol),
            ],
            'raw' => $row,
        ]);
    }

    /**
     * GET /monitor-aps/visitas/responsabilidade?ine=X
     * Conta cidadãos cadastrados por ACS via tb_fat_cad_individual.co_dim_profissional.
     */
    public function responsabilidade(Request $request): JsonResponse
    {
        $ine = $request->query('ine') ?: null;
        $this->assertIneAllowed($request, $ine);
        $allowedInes = $this->resolveAllowedInes($request);

        try {
            $db = $this->db();
        } catch (\Throwable) {
            return response()->json(['error' => 'Não foi possível conectar ao e-SUS.'], 503);
        }

        try {
            $sql = "
                SELECT
                    dp.no_profissional                         AS agente,
                    dp.nu_cns                                  AS cns,
                    de.nu_ine,
                    de.no_equipe,
                    COUNT(DISTINCT fci.co_fat_cidadao_pec)     AS cadastrados
                FROM tb_fat_cad_individual fci
                JOIN tb_dim_equipe de
                    ON de.co_seq_dim_equipe = fci.co_dim_equipe
                LEFT JOIN tb_dim_profissional dp
                    ON dp.co_seq_dim_profissional = fci.co_dim_profissional
                WHERE fci.st_ficha_inativa = 0
                  AND de.st_registro_valido = 1
            ";
            $params = [];

            if ($ine) {
                $sql    .= ' AND de.nu_ine = ?';
                $params[] = $ine;
            } elseif ($allowedInes !== null) {
                if (empty($allowedInes)) {
                    $sql .= ' AND 1=0';
                } else {
                    $ph = implode(',', array_fill(0, count($allowedInes), '?'));
                    $sql .= " AND de.nu_ine IN ({$ph})";
                    $params = array_merge($params, $allowedInes);
                }
            }

            $sql .= '
                GROUP BY dp.no_profissional, dp.nu_cns, de.nu_ine, de.no_equipe
                ORDER BY cadastrados DESC
            ';

            $rows = $db->select($sql, $params);
            return response()->json(['responsabilidade' => $rows]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('VisitaAcs.responsabilidade: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao consultar responsabilidade.'], 500);
        }
    }
}
