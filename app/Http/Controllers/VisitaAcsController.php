<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
    private function buildWhere(int $ano, int $mes, ?string $ine, ?string $agentName = null): array
    {
        $cbos   = implode("','", self::ACS_CBOS);
        $where  = "c.nu_cbo IN ('{$cbos}') AND t.nu_ano = ? AND t.nu_mes = ?";
        $params = [$ano, $mes];

        if ($ine) {
            $where   .= ' AND e.nu_ine = ?';
            $params[] = $ine;
        }

        if ($agentName) {
            $where   .= ' AND p.no_profissional = ?';
            $params[] = $agentName;
        }

        return [$where, $params];
    }

    private function baseJoins(): string
    {
        return "
            JOIN tb_dim_profissional    p  ON p.co_seq_dim_profissional    = v.co_dim_profissional
            JOIN tb_dim_cbo             c  ON c.co_seq_dim_cbo             = v.co_dim_cbo
            JOIN tb_dim_equipe          e  ON e.co_seq_dim_equipe          = v.co_dim_equipe
            JOIN tb_dim_tempo           t  ON t.co_seq_dim_tempo           = v.co_dim_tempo
            JOIN tb_dim_desfecho_visita d  ON d.co_seq_dim_desfecho_visita = v.co_dim_desfecho_visita
            JOIN tb_dim_tipo_ficha      tf ON tf.co_seq_dim_tipo_ficha     = v.co_dim_tipo_ficha
        ";
    }

    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if ($this->hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function hasTable(string $table): bool
    {
        try {
            $row = $this->db()->selectOne("
                SELECT 1
                FROM information_schema.tables
                WHERE table_schema = 'public'
                  AND table_name = ?
                LIMIT 1
            ", [$table]);
            return $row !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    private function formatMotives(object $row): array
    {
        $labels = [
            'st_mot_vis_cad_att'            => 'Cadastramento/atualização',
            'st_mot_vis_visita_periodica'    => 'Visita periódica',
            'st_mot_vis_busca_ativa'         => 'Busca ativa',
            'st_mot_vis_acompanhamento'      => 'Acompanhamento',
            'st_mot_vis_egresso_internacao'  => 'Egresso de internação',
            'st_mot_vis_ctrl_ambnte_vetor'   => 'Controle ambiental/vetorial',
            'st_mot_vis_convte_atvidd_cltva' => 'Convite para atividade coletiva',
            'st_mot_vis_orintacao_prevncao'  => 'Orientação/prevenção',
            'st_mot_vis_outros'              => 'Outros',
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
            'st_acomp_gestante'              => 'Gestante',
            'st_acomp_puerpera'              => 'Puérpera',
            'st_acomp_recem_nascido'         => 'Recém-nascido',
            'st_acomp_crianca'               => 'Criança',
            'st_acomp_pessoa_hipertensao'    => 'Hipertensão',
            'st_acomp_pessoa_diabetes'       => 'Diabetes',
            'st_acomp_pessoa_cancer'         => 'Câncer',
            'st_acomp_pessoa_idosa'          => 'Pessoa idosa',
            'st_acomp_saude_mental'          => 'Saúde mental',
            'st_acomp_tabagista'             => 'Tabagismo',
            'st_acomp_domiciliados_acamados' => 'Domiciliado/acamado',
            'st_acomp_pessoa_tuberculose'    => 'Tuberculose',
            'st_acomp_pessoa_hanseniase'     => 'Hanseníase',
            'st_acomp_condi_bolsa_familia'   => 'Bolsa Família',
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
            'id'               => (int) $row->id,
            'agent_name'       => $row->agent_name,
            'cbo'              => $row->cbo,
            'cbo_label'        => self::CBO_LABELS[$row->cbo] ?? $row->cbo,
            'team_ine'         => $row->team_ine,
            'team_name'        => $row->team_name,
            'visited_date'     => $row->visited_date,
            'hora'             => isset($row->hora) ? (int) $row->hora : null,
            'instrument_label' => $row->instrument_label,
            'outcome_code'     => $outcomeCode,
            'outcome_label'    => $row->outcome_label,
            'outcome_color'    => self::OUTCOME_COLORS[$outcomeCode] ?? 'default',
            'has_geolocation'  => (bool) $row->has_geo,
            'motives'          => $this->formatMotives($row),
        ];

        if ($detail) {
            $result['citizen_name']   = $row->citizen_name   ?? null;
            $result['notes']          = $row->notes          ?? null;
            $result['lat']            = isset($row->lat) && $row->lat !== null ? (float) $row->lat : null;
            $result['lng']            = isset($row->lng) && $row->lng !== null ? (float) $row->lng : null;
            $result['accompaniments'] = $this->formatAccompaniments($row);
            $result['address']        = [
                'logradouro'  => $row->logradouro  ?? null,
                'numero'      => $row->num_endereco ?? null,
                'complemento' => $row->complemento ?? null,
                'bairro'      => $row->bairro       ?? null,
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
            'ano'      => 'required|integer|min:2020|max:2030',
            'mes'      => 'required|integer|min:1|max:12',
            'ine'      => 'nullable|string',
            'agente'   => 'nullable|string',
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $ano     = (int) $request->ano;
        $mes     = (int) $request->mes;
        $perPage = (int) ($request->per_page ?? 20);
        $page    = (int) ($request->page ?? 1);
        $offset  = ($page - 1) * $perPage;

        [$where, $params] = $this->buildWhere($ano, $mes, $request->ine, $request->agente);

        $rows = $this->db()->select("
            SELECT
                v.co_seq_fat_visita_domiciliar   AS id,
                p.no_profissional                AS agent_name,
                c.nu_cbo                         AS cbo,
                e.nu_ine                         AS team_ine,
                e.no_equipe                      AS team_name,
                t.dt_registro                    AS visited_date,
                tf.ds_tipo_ficha                 AS instrument_label,
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

        $total = (int) ($rows[0]->total_count ?? 0);

        return response()->json([
            'data' => array_map(fn($r) => $this->formatVisita($r), $rows),
            'meta' => [
                'total'    => $total,
                'page'     => $page,
                'per_page' => $perPage,
                'pages'    => (int) ceil($total / max($perPage, 1)),
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
        ]);

        $ano = (int) $request->ano;
        $mes = (int) $request->mes;

        [$where, $params] = $this->buildWhere($ano, $mes, $request->ine);

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

        return response()->json([
            'totais' => [
                'total'      => (int) ($totRow->total ?? 0),
                'realizadas' => (int) ($totRow->realizadas ?? 0),
                'recusadas'  => (int) ($totRow->recusadas ?? 0),
                'ausentes'   => (int) ($totRow->ausentes ?? 0),
                'cidadaos'   => (int) ($totRow->cidadaos ?? 0),
            ],
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
            'ano'      => 'required|integer|min:2020|max:2030',
            'mes'      => 'required|integer|min:1|max:12',
            'ine'      => 'nullable|string',
            'agente'   => 'nullable|string',
            'desfecho' => 'nullable|integer',
            'has_geo'  => 'nullable|in:sim,nao',
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $ano     = (int) $request->ano;
        $mes     = (int) $request->mes;
        $perPage = (int) ($request->per_page ?? 50);
        $page    = (int) ($request->page ?? 1);
        $offset  = ($page - 1) * $perPage;

        [$where, $params] = $this->buildWhere($ano, $mes, $request->ine);

        if ($request->agente) {
            $where   .= ' AND p.no_profissional ILIKE ?';
            $params[] = '%' . $request->agente . '%';
        }

        if ($request->desfecho) {
            $where   .= ' AND d.co_seq_dim_desfecho_visita = ?';
            $params[] = (int) $request->desfecho;
        }

        if ($request->has_geo === 'sim') {
            $where .= ' AND v.nu_latitude IS NOT NULL AND v.nu_longitude IS NOT NULL';
        } elseif ($request->has_geo === 'nao') {
            $where .= ' AND (v.nu_latitude IS NULL OR v.nu_longitude IS NULL)';
        }

        $queryParams = array_merge($params, [$perPage, $offset]);

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
                tf.ds_tipo_ficha                 AS instrumento,
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
                p.no_profissional                AS agente,
                c.nu_cbo                         AS cbo,
                e.nu_ine                         AS equipe_ine,
                e.no_equipe                      AS equipe_nome,
                v.nu_micro_area                  AS micro_area,
                tf.ds_tipo_ficha                 AS instrumento,
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

        $sql  = $this->hasColumn('tb_dim_tempo', 'nu_hora') ? $sqlFull : $sqlBase;
        $rows = $this->db()->select($sql, $queryParams);
        $total = (int) ($rows[0]->total_count ?? 0);

        $visitas = array_map(fn($r) => [
            'id'             => (int) $r->id,
            'data'           => $r->data,
            'hora'           => isset($r->hora) ? (int) $r->hora : null,
            'cidadao'        => $r->cidadao ?? null,
            'agente'         => $r->agente,
            'cbo'            => self::CBO_LABELS[$r->cbo] ?? $r->cbo,
            'equipe'         => ['ine' => $r->equipe_ine, 'nome' => $r->equipe_nome],
            'micro_area'     => $r->micro_area,
            'instrumento'    => $r->instrumento,
            'desfecho_id'    => (int) $r->desfecho_id,
            'desfecho_label' => $r->desfecho_label,
            'has_geo'        => (bool) $r->has_geo,
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
        $notesCol = $this->firstExistingColumn('tb_fat_visita_domiciliar', ['ds_anotacao', 'ds_observacao', 'ds_relato']);
        $citizenNameCol = $this->firstExistingColumn('tb_fat_cad_individual', ['no_cidadao']);
        $logradouroCol = $this->firstExistingColumn('tb_fat_cad_individual', ['ds_logradouro', 'no_logradouro']);
        $numeroCol = $this->firstExistingColumn('tb_fat_cad_individual', ['nu_numero', 'nu_endereco']);
        $complCol = $this->firstExistingColumn('tb_fat_cad_individual', ['ds_complemento', 'no_complemento']);
        $bairroCol = $this->firstExistingColumn('tb_fat_cad_individual', ['ds_bairro', 'no_bairro']);

        $notesExpr = $notesCol ? "v.{$notesCol}" : 'NULL::text';
        $citizenNameExpr = $citizenNameCol ? "(SELECT fci.{$citizenNameCol} FROM tb_fat_cad_individual fci WHERE fci.co_fat_cidadao_pec = v.co_fat_cidadao_pec LIMIT 1)" : 'NULL::text';
        $logradouroExpr = $logradouroCol ? "(SELECT fci.{$logradouroCol} FROM tb_fat_cad_individual fci WHERE fci.co_fat_cidadao_pec = v.co_fat_cidadao_pec LIMIT 1)" : 'NULL::text';
        $numeroExpr = $numeroCol ? "(SELECT fci.{$numeroCol}::text FROM tb_fat_cad_individual fci WHERE fci.co_fat_cidadao_pec = v.co_fat_cidadao_pec LIMIT 1)" : 'NULL::text';
        $complementoExpr = $complCol ? "(SELECT fci.{$complCol} FROM tb_fat_cad_individual fci WHERE fci.co_fat_cidadao_pec = v.co_fat_cidadao_pec LIMIT 1)" : 'NULL::text';
        $bairroExpr = $bairroCol ? "(SELECT fci.{$bairroCol} FROM tb_fat_cad_individual fci WHERE fci.co_fat_cidadao_pec = v.co_fat_cidadao_pec LIMIT 1)" : 'NULL::text';

        $sqlFull = "
            SELECT
                v.co_seq_fat_visita_domiciliar   AS id,
                p.no_profissional                AS agent_name,
                c.nu_cbo                         AS cbo,
                e.nu_ine                         AS team_ine,
                e.no_equipe                      AS team_name,
                t.dt_registro                    AS visited_date,
                t.nu_hora                        AS hora,
                tf.ds_tipo_ficha                 AS instrument_label,
                d.co_seq_dim_desfecho_visita     AS outcome_code,
                d.ds_desfecho_visita             AS outcome_label,
                v.nu_latitude                    AS lat,
                v.nu_longitude                   AS lng,
                {$notesExpr}                     AS notes,
                {$citizenNameExpr}               AS citizen_name,
                {$logradouroExpr}                AS logradouro,
                {$numeroExpr}                    AS num_endereco,
                {$complementoExpr}               AS complemento,
                {$bairroExpr}                    AS bairro,
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
                tf.ds_tipo_ficha                 AS instrument_label,
                d.co_seq_dim_desfecho_visita     AS outcome_code,
                d.ds_desfecho_visita             AS outcome_label,
                v.nu_latitude                    AS lat,
                v.nu_longitude                   AS lng,
                {$notesExpr}                     AS notes,
                {$citizenNameExpr}               AS citizen_name,
                {$logradouroExpr}                AS logradouro,
                {$numeroExpr}                    AS num_endereco,
                {$complementoExpr}               AS complemento,
                {$bairroExpr}                    AS bairro,
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
        $row = $this->db()->selectOne($sql, [$id]);

        if (! $row) {
            return response()->json(['message' => 'Visita não encontrada.'], 404);
        }

        $isMissingCoreDetail = empty($row->notes)
            && empty($row->citizen_name)
            && empty($row->logradouro)
            && empty($row->bairro);

        if ($isMissingCoreDetail && $this->hasTable('tb_fat_consolidado_cidadao_fvd')) {
            $consNotesCol = $this->firstExistingColumn('tb_fat_consolidado_cidadao_fvd', ['ds_anotacao', 'ds_observacao', 'ds_relato']);
            $consCitizenCol = $this->firstExistingColumn('tb_fat_consolidado_cidadao_fvd', ['no_cidadao']);
            $consLogradouroCol = $this->firstExistingColumn('tb_fat_consolidado_cidadao_fvd', ['ds_logradouro', 'no_logradouro']);
            $consNumeroCol = $this->firstExistingColumn('tb_fat_consolidado_cidadao_fvd', ['nu_numero', 'nu_endereco']);
            $consComplementoCol = $this->firstExistingColumn('tb_fat_consolidado_cidadao_fvd', ['ds_complemento', 'no_complemento']);
            $consBairroCol = $this->firstExistingColumn('tb_fat_consolidado_cidadao_fvd', ['ds_bairro', 'no_bairro']);

            if ($consNotesCol || $consCitizenCol || $consLogradouroCol || $consNumeroCol || $consComplementoCol || $consBairroCol) {
                $consSql = "
                    SELECT
                        " . ($consNotesCol ? "c.{$consNotesCol}" : "NULL::text") . " AS notes,
                        " . ($consCitizenCol ? "c.{$consCitizenCol}" : "NULL::text") . " AS citizen_name,
                        " . ($consLogradouroCol ? "c.{$consLogradouroCol}" : "NULL::text") . " AS logradouro,
                        " . ($consNumeroCol ? "c.{$consNumeroCol}::text" : "NULL::text") . " AS num_endereco,
                        " . ($consComplementoCol ? "c.{$consComplementoCol}" : "NULL::text") . " AS complemento,
                        " . ($consBairroCol ? "c.{$consBairroCol}" : "NULL::text") . " AS bairro
                    FROM tb_fat_consolidado_cidadao_fvd c
                    WHERE c.co_seq_fat_visita_domiciliar = ?
                    LIMIT 1
                ";

                $fallback = $this->db()->selectOne($consSql, [$id]);
                if ($fallback) {
                    $row->notes = $row->notes ?: ($fallback->notes ?? null);
                    $row->citizen_name = $row->citizen_name ?: ($fallback->citizen_name ?? null);
                    $row->logradouro = $row->logradouro ?: ($fallback->logradouro ?? null);
                    $row->num_endereco = $row->num_endereco ?: ($fallback->num_endereco ?? null);
                    $row->complemento = $row->complemento ?: ($fallback->complemento ?? null);
                    $row->bairro = $row->bairro ?: ($fallback->bairro ?? null);
                }
            }
        }

        return response()->json($this->formatVisita($row, detail: true));
    }
/**
     * GET /visitas/mapa?ano=X&mes=Y[&ine=Z&agente=W]
     * Pins georreferenciados Ã¢â‚¬â€ aba Mapa do VisitasAcs.js.
     * Inclui equipe_ine para coloraÃƒÂ§ÃƒÂ£o por equipe no modo "Todos".
     */
    public function mapa(Request $request): JsonResponse
    {
        $request->validate([
            'ano'    => 'required|integer|min:2020|max:2030',
            'mes'    => 'required|integer|min:1|max:12',
            'ine'    => 'nullable|string',
            'agente' => 'nullable|string',
            'busca'  => 'nullable|string|max:200',
        ]);

        $ano = (int) $request->ano;
        $mes = (int) $request->mes;

        [$where, $params] = $this->buildWhere($ano, $mes, $request->ine);

        if ($request->agente) {
            $where   .= ' AND p.no_profissional = ?';
            $params[] = $request->agente;
        }

        if ($request->busca) {
            $busca  = trim($request->busca);
            $digits = preg_replace('/\D/', '', $busca);

            if (strlen($digits) === 11) {
                // CPF
                $where   .= " AND v.co_fat_cidadao_pec IN (
                    SELECT co_fat_cidadao_pec FROM tb_fat_cad_individual
                    WHERE nu_cpf = ? AND st_ficha_inativa = 0)";
                $params[] = $digits;
            } elseif (strlen($digits) === 15) {
                // CNS
                $where   .= " AND v.co_fat_cidadao_pec IN (
                    SELECT co_fat_cidadao_pec FROM tb_fat_cad_individual
                    WHERE nu_cns = ? AND st_ficha_inativa = 0)";
                $params[] = $digits;
            } else {
                // Nome parcial (mÃƒÂ­nimo 3 chars validado no frontend)
                $where   .= " AND v.co_fat_cidadao_pec IN (
                    SELECT co_fat_cidadao_pec FROM tb_fat_cad_individual
                    WHERE no_cidadao ILIKE ? AND st_ficha_inativa = 0)";
                $params[] = '%' . $busca . '%';
            }
        }

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
            LIMIT 2000
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
                v.nu_micro_area                  AS micro_area
            FROM tb_fat_visita_domiciliar v
            {$this->baseJoins()}
            WHERE {$where}
              AND v.nu_latitude  IS NOT NULL
              AND v.nu_longitude IS NOT NULL
            ORDER BY t.dt_registro DESC
            LIMIT 2000
        ";

        $sql  = $this->hasColumn('tb_dim_tempo', 'nu_hora') ? $sqlFull : $sqlBase;
        $rows = $this->db()->select($sql, $params);

        $pontos = array_map(fn($r) => [
            'id'         => (int) $r->id,
            'lat'        => (float) $r->lat,
            'lng'        => (float) $r->lng,
            'agente'     => $r->agente,
            'cbo'        => self::CBO_LABELS[$r->cbo] ?? $r->cbo,
            'equipe_ine' => $r->equipe_ine,
            'equipe'     => $r->equipe_nome,
            'cidadao'    => $r->cidadao ?? null,
            'data'       => $r->data,
            'hora'       => isset($r->hora) ? (int) $r->hora : null,
            'desfecho'   => (int) $r->desfecho,
            'micro_area' => $r->micro_area,
        ], $rows);

        return response()->json(['pontos' => $pontos]);
    }

    /**
     * GET /visitas/equipes
     */
    public function equipes(): JsonResponse
    {
        $rows = $this->db()->select("
            SELECT nu_ine AS ine, no_equipe AS name
            FROM tb_dim_equipe
            WHERE st_registro_valido = 1 AND nu_ine != '-'
            ORDER BY no_equipe
        ");

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
        ]);

        $ano = (int) $request->ano;
        $mes = (int) $request->mes;

        [$where, $params] = $this->buildWhere($ano, $mes, $request->ine);

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
            FROM tb_fat_visita_domiciliar v
            {$this->baseJoins()}
            WHERE {$where}
            GROUP BY p.no_profissional, c.nu_cbo, e.no_equipe
            ORDER BY total DESC
        ", $params);

        $agentes = array_map(fn($r) => [
            'agente'         => $r->agente,
            'cbo'            => $r->cbo,
            'cbo_nome'       => self::CBO_LABELS[$r->cbo] ?? $r->cbo,
            'equipe'         => ['nome' => $r->equipe_nome],
            'total'          => (int) $r->total,
            'realizadas'     => (int) $r->realizadas,
            'recusadas'      => (int) $r->recusadas,
            'ausentes'       => (int) $r->ausentes,
            'pct_realizadas' => $r->total > 0
                ? (int) round($r->realizadas / $r->total * 100)
                : 0,
            'cidadaos'       => (int) $r->cidadaos,
        ], $rows);

        return response()->json(['agentes' => $agentes]);
    }
}

