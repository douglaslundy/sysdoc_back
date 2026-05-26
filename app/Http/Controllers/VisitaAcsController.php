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
        ?string $hasGeo = null
    ): array {
        $cbos = implode("','", self::ACS_CBOS);
        $where = "c.nu_cbo IN ('{$cbos}') AND t.nu_ano = ? AND t.nu_mes = ?";
        $params = [$ano, $mes];

        if ($ine) {
            $where .= ' AND e.nu_ine = ?';
            $params[] = $ine;
        }

        if ($agentName) {
            $where .= ' AND p.no_profissional = ?';
            $params[] = $agentName;
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
        ?string $hasGeo = null
    ): array {
        $cbos = implode("','", self::ACS_CBOS);
        $where = "c.nu_cbo IN ('{$cbos}')";
        $params = [];

        if ($ine) {
            $where .= ' AND e.nu_ine = ?';
            $params[] = $ine;
        }

        if ($agentName) {
            $where .= ' AND p.no_profissional = ?';
            $params[] = $agentName;
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

    private function baseJoins(): string
    {
        return '
            JOIN tb_dim_profissional    p  ON p.co_seq_dim_profissional    = v.co_dim_profissional
            JOIN tb_dim_cbo             c  ON c.co_seq_dim_cbo             = v.co_dim_cbo
            JOIN tb_dim_equipe          e  ON e.co_seq_dim_equipe          = v.co_dim_equipe
            JOIN tb_dim_tempo           t  ON t.co_seq_dim_tempo           = v.co_dim_tempo
            JOIN tb_dim_desfecho_visita d  ON d.co_seq_dim_desfecho_visita = v.co_dim_desfecho_visita
            JOIN tb_dim_tipo_ficha      tf ON tf.co_seq_dim_tipo_ficha     = v.co_dim_tipo_ficha
        ';
    }

    /**
     * Expressão SQL para o instrumento de registro.
     *
     * tb_dim_tipo_ficha.ds_tipo_ficha armazena o NOME DA FICHA (template), que é sempre
     * "CDS Ficha de Visita Domiciliar" independente do canal de entrada. O campo correto
     * para distinguir tablet/CDS é st_tipo_instrumento_registro:
     *   1 = CDS (offline/papel)  |  3 = PEC (tablet)  |  4 = App e-SUS APS
     */
    private function instrumentExpr(string $alias = 'v'): string
    {
        if ($this->hasColumn('tb_fat_visita_domiciliar', 'st_tipo_instrumento_registro')) {
            return "CASE {$alias}.st_tipo_instrumento_registro
                        WHEN 1 THEN 'CDS'
                        WHEN 3 THEN 'PEC (Tablet)'
                        WHEN 4 THEN 'App e-SUS APS'
                        ELSE COALESCE(tf.ds_tipo_ficha, 'Desconhecido')
                    END";
        }

        return 'tf.ds_tipo_ficha';
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

    private function textColumnExpr(string $tableAlias, ?string $column): string
    {
        return $column ? "{$tableAlias}.{$column}::text" : 'NULL::text';
    }

    private function citizenNameExpr(string $visitAlias = 'v'): string
    {
        if (
            $this->hasTable('tb_fat_cad_individual')
            && $this->hasColumn('tb_fat_cad_individual', 'no_cidadao')
            && $this->hasColumn('tb_fat_cad_individual', 'co_fat_cidadao_pec')
        ) {
            return "(SELECT ci.no_cidadao FROM tb_fat_cad_individual ci WHERE ci.co_fat_cidadao_pec = {$visitAlias}.co_fat_cidadao_pec LIMIT 1)";
        }

        if (
            $this->hasTable('tb_fat_cidadao_pec')
            && $this->hasColumn('tb_fat_cidadao_pec', 'no_cidadao')
            && $this->hasColumn('tb_fat_cidadao_pec', 'co_seq_fat_cidadao_pec')
        ) {
            return "(SELECT cp.no_cidadao FROM tb_fat_cidadao_pec cp WHERE cp.co_seq_fat_cidadao_pec = {$visitAlias}.co_fat_cidadao_pec LIMIT 1)";
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

        [$where, $params] = $this->buildWhere($ano, $mes, $request->ine, $request->agente);

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

        [$where, $params] = $this->buildWhere(
            $ano, $mes, $request->ine,
            $request->agente,
            $request->desfecho,
            $request->has_geo,
        );

        try {
            $totRow = $this->db()->selectOne("
                SELECT
                    COUNT(*)                                                              AS total,
                    SUM(CASE WHEN d.co_seq_dim_desfecho_visita = 1 THEN 1 ELSE 0 END)   AS realizadas,
                    SUM(CASE WHEN d.co_seq_dim_desfecho_visita = 2 THEN 1 ELSE 0 END)   AS recusadas,
                    SUM(CASE WHEN d.co_seq_dim_desfecho_visita = 3 THEN 1 ELSE 0 END)   AS ausentes,
                    COUNT(DISTINCT v.co_fat_cidadao_pec)                                 AS cidadaos
                FROM tb_fat_visita_domiciliar v
                {$this->baseJoins()}
                WHERE {$where}
            ", $params);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('VisitaAcs.resumo: ' . $e->getMessage());
            return response()->json(['error' => 'Não foi possível consultar o banco eSUS PEC.'], 503);
        }

        $familyData = null;
        $familyExpr = $this->familyIdExpr();

        if ($familyExpr !== null) {
            // Family counts are always month-wide (desfecho/geo filters excluded)
            // so the breakdown reflects the full picture, not just the filtered slice.
            [$familyWhere, $familyParams] = $this->buildWhere($ano, $mes, $request->ine, $request->agente);

            try {
                $famRow = $this->db()->selectOne("
                    SELECT
                        COUNT(DISTINCT {$familyExpr})                        AS familias,
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
                        'familias'              => (int) ($famRow->familias ?? 0),
                        'familias_acompanhadas' => (int) ($famRow->familias_acompanhadas ?? 0),
                        'familias_recusadas'    => (int) ($famRow->familias_recusadas ?? 0),
                        'familias_ausentes'     => (int) ($famRow->familias_ausentes ?? 0),
                    ];
                }
            } catch (\Throwable) {
                // Falha silenciosa — front trata null como "não disponível"
            }
        }

        $nullFamily = [
            'familias'              => null,
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
                $familyData ?? $nullFamily
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

        [$where, $params] = $this->buildWhere($ano, $mes, $request->ine);

        if ($request->agente) {
            $where .= ' AND p.no_profissional ILIKE ?';
            $params[] = '%'.$request->agente.'%';
        }

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

        // Query completa: citizen via LATERAL + nu_hora
        $sqlFull = "
            SELECT
                v.co_seq_fat_visita_domiciliar   AS id,
                t.dt_registro                    AS data,
                t.nu_hora                        AS hora,
                ci.no_cidadao                    AS cidadao,
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
            LEFT JOIN LATERAL (
                SELECT no_cidadao
                FROM   tb_fat_cad_individual
                WHERE  co_fat_cidadao_pec = v.co_fat_cidadao_pec
                LIMIT  1
            ) ci ON true
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

        [$where, $params] = $this->buildWhere($ano, $mes, $request->ine);

        // Resolve column names for CPF/CNS/nome based on database schema
        $cpfCol  = $this->firstExistingColumn('tb_fat_cad_individual', ['nu_cpf', 'co_cpf'])     ?? 'nu_cpf';
        $cnsCol  = $this->firstExistingColumn('tb_fat_cad_individual', ['nu_cns', 'co_cns'])     ?? 'nu_cns';
        $nomeCol = $this->firstExistingColumn('tb_fat_cad_individual', ['no_cidadao', 'no_nome']) ?? 'no_cidadao';

        if ($request->agente) {
            $where .= ' AND p.no_profissional = ?';
            $params[] = $request->agente;
        }

        if ($request->busca) {
            $busca  = trim($request->busca);
            $digits = preg_replace('/\D/', '', $busca);

            if (strlen($digits) === 11) {
                $where   .= " AND v.co_fat_cidadao_pec IN (
                    SELECT co_fat_cidadao_pec FROM tb_fat_cad_individual
                    WHERE {$cpfCol} = ?)";
                $params[] = $digits;
            } elseif (strlen($digits) === 15) {
                $where   .= " AND v.co_fat_cidadao_pec IN (
                    SELECT co_fat_cidadao_pec FROM tb_fat_cad_individual
                    WHERE {$cnsCol} = ?)";
                $params[] = $digits;
            } else {
                $where   .= " AND v.co_fat_cidadao_pec IN (
                    SELECT co_fat_cidadao_pec FROM tb_fat_cad_individual
                    WHERE {$nomeCol} ILIKE ?)";
                $params[] = '%' . $busca . '%';
            }
        }
        $citizenExpr = $this->citizenNameExpr('v');

        // Query completa: LATERAL direto no FROM + nu_hora + nome do cidadÃƒÂ£o
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
                ci.no_cidadao                    AS cidadao
            FROM tb_fat_visita_domiciliar v
            {$this->baseJoins()}
            LEFT JOIN LATERAL (
                SELECT no_cidadao
                FROM   tb_fat_cad_individual
                WHERE  co_fat_cidadao_pec = v.co_fat_cidadao_pec
                LIMIT  1
            ) ci ON true
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
                {$citizenExpr}                   AS cidadao
            FROM tb_fat_visita_domiciliar v
            {$this->baseJoins()}
            WHERE {$where}
              AND v.nu_latitude  IS NOT NULL
              AND v.nu_longitude IS NOT NULL
            ORDER BY t.dt_registro DESC
        ";

        $sql = $this->hasColumn('tb_dim_tempo', 'nu_hora') ? $sqlFull : $sqlBase;

        try {
            $rows = $this->db()->select($sql, $params);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('VisitaAcs.mapa: ' . $e->getMessage());
            return response()->json(['error' => 'Não foi possível consultar o banco eSUS PEC.'], 503);
        }

        $pontos = array_map(fn ($r) => [
            'id' => (int) $r->id,
            'lat' => (float) $r->lat,
            'lng' => (float) $r->lng,
            'agente' => $r->agente,
            'cbo' => self::CBO_LABELS[$r->cbo] ?? $r->cbo,
            'equipe_ine' => $r->equipe_ine,
            'equipe' => $r->equipe_nome,
            'cidadao' => $r->cidadao ?? null,
            'data' => $r->data,
            'hora' => isset($r->hora) ? (int) $r->hora : null,
            'desfecho' => (int) $r->desfecho,
            'micro_area' => $r->micro_area,
        ], $rows);

        return response()->json(['pontos' => $pontos]);
    }

    /**
     * GET /visitas/equipes
     */
    public function equipes(): JsonResponse
    {
        try {
            $rows = $this->db()->select("
                SELECT nu_ine AS ine, no_equipe AS name
                FROM tb_dim_equipe
                WHERE st_registro_valido = 1 AND nu_ine != '-'
                ORDER BY no_equipe
            ");
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
            'has_geo' => 'nullable|string|in:sim,nao',
        ]);

        $ano = (int) $request->ano;
        $mes = (int) $request->mes;

        [$where, $params] = $this->buildWhere(
            $ano, $mes, $request->ine,
            $request->agente,
            null,
            $request->has_geo,
        );

        $familyExpr = $this->familyIdExpr();
        $hasFamilies = $familyExpr !== null;

        $familyCols = $hasFamilies ? ",
            COUNT(DISTINCT {$familyExpr})                                                        AS familias,
            COUNT(DISTINCT CASE WHEN d.co_seq_dim_desfecho_visita = 1 THEN {$familyExpr} END)   AS familias_acompanhadas"
            : '';

        $familyJoin = $hasFamilies
            ? 'LEFT JOIN tb_fat_cad_individual ci ON ci.co_fat_cidadao_pec = v.co_fat_cidadao_pec'
            : '';

        try {
            $rows = $this->db()->select("
                SELECT
                    p.no_profissional                                                      AS agente,
                    c.nu_cbo                                                               AS cbo,
                    e.no_equipe                                                            AS equipe_nome,
                    COUNT(*)                                                               AS total,
                    SUM(CASE WHEN d.co_seq_dim_desfecho_visita = 1 THEN 1 ELSE 0 END)    AS realizadas,
                    SUM(CASE WHEN d.co_seq_dim_desfecho_visita = 2 THEN 1 ELSE 0 END)    AS recusadas,
                    SUM(CASE WHEN d.co_seq_dim_desfecho_visita = 3 THEN 1 ELSE 0 END)    AS ausentes,
                    COUNT(DISTINCT v.co_fat_cidadao_pec)                                  AS cidadaos
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

        $agentes = array_map(function ($r) use ($hasFamilies) {
            $familias    = $hasFamilies ? (int) ($r->familias ?? 0) : null;
            $famAcomp    = $hasFamilies ? (int) ($r->familias_acompanhadas ?? 0) : null;
            $pctFamilias = ($hasFamilies && $familias > 0)
                ? (int) round($famAcomp / $familias * 100)
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
                'familias'              => $familias,
                'familias_acompanhadas' => $famAcomp,
                'pct_familias'          => $pctFamilias,
            ];
        }, $rows);

        return response()->json(['agentes' => $agentes]);
    }

    /**
     * GET /visitas/evolucao?[ine=Z&agente=W&desfecho=N&has_geo=X]
     * Contagem mensal de visitas para o ano atual e os 2 anos anteriores.
     * Retorna 3 séries, cada uma com 12 valores (índice 0 = Janeiro).
     */
    public function evolucao(Request $request): JsonResponse
    {
        $request->validate([
            'ine' => 'nullable|string',
            'agente' => 'nullable|string',
            'desfecho' => 'nullable|integer|in:1,2,3',
            'has_geo' => 'nullable|string|in:sim,nao',
        ]);

        $anoAtual = (int) date('Y');
        $anos = [$anoAtual, $anoAtual - 1, $anoAtual - 2];

        [$where, $params] = $this->buildWhereFilters(
            $request->ine,
            $request->agente,
            $request->desfecho,
            $request->has_geo,
        );

        $placeholders = implode(',', array_fill(0, count($anos), '?'));
        $where .= " AND t.nu_ano IN ({$placeholders})";
        $params = array_merge($params, $anos);

        try {
            $rows = $this->db()->select("
                SELECT
                    t.nu_ano  AS ano,
                    t.nu_mes  AS mes,
                    COUNT(*)  AS total
                FROM tb_fat_visita_domiciliar v
                {$this->baseJoins()}
                WHERE {$where}
                GROUP BY t.nu_ano, t.nu_mes
                ORDER BY t.nu_ano, t.nu_mes
            ", $params);
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
}
