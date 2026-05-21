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

    // Desfechos confirmados no banco de produção:
    // 1=Visita realizada, 2=Visita recusada, 3=Ausente, 4=Não informado
    private const OUTCOME_COLORS = [
        1 => 'success',
        2 => 'error',
        3 => 'warning',
        4 => 'default',
    ];

    private function quadMeses(int $quad): array
    {
        return match ($quad) {
            1       => [1, 2, 3, 4],
            2       => [5, 6, 7, 8],
            3       => [9, 10, 11, 12],
            default => [1, 2, 3, 4],
        };
    }

    /** WHERE clause + bindings para filtro por mês exato (usado em index/show/mapa granular) */
    private function baseWhere(int $ano, int $mes, ?string $ine, ?string $agentName): array
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

    /** WHERE clause + bindings para filtro por quadrimestre (usado em resumo/lista/agentes/mapa) */
    private function quadWhere(int $ano, int $quad, ?string $ine): array
    {
        $cbos   = implode("','", self::ACS_CBOS);
        $meses  = $this->quadMeses($quad);
        $phs    = implode(',', array_fill(0, count($meses), '?'));
        $where  = "c.nu_cbo IN ('{$cbos}') AND t.nu_ano = ? AND t.nu_mes IN ({$phs})";
        $params = array_merge([$ano], $meses);

        if ($ine) {
            $where   .= ' AND e.nu_ine = ?';
            $params[] = $ine;
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
            'instrument_label' => $row->instrument_label,
            'outcome_code'     => $outcomeCode,
            'outcome_label'    => $row->outcome_label,
            'outcome_color'    => self::OUTCOME_COLORS[$outcomeCode] ?? 'default',
            'has_geolocation'  => (bool) $row->has_geo,
            'motives'          => $this->formatMotives($row),
        ];

        if ($detail) {
            $result['notes']          = $row->notes ?? null;
            $result['lat']            = isset($row->lat) && $row->lat !== null ? (float) $row->lat : null;
            $result['lng']            = isset($row->lng) && $row->lng !== null ? (float) $row->lng : null;
            $result['accompaniments'] = $this->formatAccompaniments($row);
        }

        return $result;
    }

    // ─── Endpoints públicos ───────────────────────────────────────────────────

    /**
     * GET /visitas?ano=X&mes=Y[&ine=Z&agente=W&page=N&per_page=N]
     * Lista paginada por mês (granularidade mensal — mais precisa).
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

        [$where, $params] = $this->baseWhere($ano, $mes, $request->ine, $request->agente);

        $countRow = $this->db()->selectOne(
            "SELECT COUNT(*) AS total FROM tb_fat_visita_domiciliar v {$this->baseJoins()} WHERE {$where}",
            $params
        );

        $total = (int) ($countRow->total ?? 0);

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
                     THEN true ELSE false END    AS has_geo
            FROM tb_fat_visita_domiciliar v
            {$this->baseJoins()}
            WHERE {$where}
            ORDER BY t.dt_registro DESC, v.co_seq_fat_visita_domiciliar DESC
            LIMIT ? OFFSET ?
        ", array_merge($params, [$perPage, $offset]));

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
     * GET /visitas/resumo?ano=X&quadrimestre=Y[&ine=Z]
     * Totais e breakdown mensal para o dashboard (VisitasAcs.js).
     */
    public function resumo(Request $request): JsonResponse
    {
        $request->validate([
            'ano'          => 'required|integer|min:2020|max:2030',
            'quadrimestre' => 'required|integer|min:1|max:3',
            'ine'          => 'nullable|string',
        ]);

        $ano  = (int) $request->ano;
        $quad = (int) $request->quadrimestre;

        [$where, $params] = $this->quadWhere($ano, $quad, $request->ine);

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

        $porMes = $this->db()->select("
            SELECT
                t.nu_mes                                                              AS mes,
                SUM(CASE WHEN d.co_seq_dim_desfecho_visita = 1 THEN 1 ELSE 0 END)   AS realizadas,
                SUM(CASE WHEN d.co_seq_dim_desfecho_visita = 2 THEN 1 ELSE 0 END)   AS recusadas,
                SUM(CASE WHEN d.co_seq_dim_desfecho_visita = 3 THEN 1 ELSE 0 END)   AS ausentes
            FROM tb_fat_visita_domiciliar v
            {$this->baseJoins()}
            WHERE {$where}
            GROUP BY t.nu_mes
            ORDER BY t.nu_mes
        ", $params);

        return response()->json([
            'totais' => [
                'total'      => (int) ($totRow->total ?? 0),
                'realizadas' => (int) ($totRow->realizadas ?? 0),
                'recusadas'  => (int) ($totRow->recusadas ?? 0),
                'ausentes'   => (int) ($totRow->ausentes ?? 0),
                'cidadaos'   => (int) ($totRow->cidadaos ?? 0),
            ],
            'por_mes' => array_map(fn($r) => [
                'mes'        => (int) $r->mes,
                'realizadas' => (int) $r->realizadas,
                'recusadas'  => (int) $r->recusadas,
                'ausentes'   => (int) $r->ausentes,
            ], $porMes),
        ]);
    }

    /**
     * GET /visitas/lista?ano=X&quadrimestre=Y[&ine=Z&agente=W&desfecho=N&page=N&per_page=N]
     * Lista paginada por quadrimestre (VisitasAcs.js — aba Tabela).
     */
    public function lista(Request $request): JsonResponse
    {
        $request->validate([
            'ano'          => 'required|integer|min:2020|max:2030',
            'quadrimestre' => 'required|integer|min:1|max:3',
            'ine'          => 'nullable|string',
            'agente'       => 'nullable|string',
            'desfecho'     => 'nullable|integer',
            'page'         => 'nullable|integer|min:1',
            'per_page'     => 'nullable|integer|min:1|max:100',
        ]);

        $ano      = (int) $request->ano;
        $quad     = (int) $request->quadrimestre;
        $perPage  = (int) ($request->per_page ?? 50);
        $page     = (int) ($request->page ?? 1);
        $offset   = ($page - 1) * $perPage;
        $desfecho = $request->agente ? null : ($request->desfecho ? (int) $request->desfecho : null);

        [$where, $params] = $this->quadWhere($ano, $quad, $request->ine);

        if ($request->agente) {
            $where   .= ' AND p.no_profissional ILIKE ?';
            $params[] = '%' . $request->agente . '%';
        }

        if ($desfecho) {
            $where   .= ' AND d.co_seq_dim_desfecho_visita = ?';
            $params[] = $desfecho;
        }

        $countRow = $this->db()->selectOne(
            "SELECT COUNT(*) AS total FROM tb_fat_visita_domiciliar v {$this->baseJoins()} WHERE {$where}",
            $params
        );

        $total = (int) ($countRow->total ?? 0);

        $rows = $this->db()->select("
            SELECT
                v.co_seq_fat_visita_domiciliar   AS id,
                t.dt_registro                    AS data,
                p.no_profissional                AS agente,
                c.nu_cbo                         AS cbo,
                e.no_equipe                      AS equipe_nome,
                v.nu_micro_area                  AS micro_area,
                d.co_seq_dim_desfecho_visita     AS desfecho_id,
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
                v.st_acomp_condi_bolsa_familia
            FROM tb_fat_visita_domiciliar v
            {$this->baseJoins()}
            WHERE {$where}
            ORDER BY t.dt_registro DESC, v.co_seq_fat_visita_domiciliar DESC
            LIMIT ? OFFSET ?
        ", array_merge($params, [$perPage, $offset]));

        $visitas = array_map(fn($r) => [
            'id'             => (int) $r->id,
            'data'           => $r->data,
            'agente'         => $r->agente,
            'cbo'            => self::CBO_LABELS[$r->cbo] ?? $r->cbo,
            'equipe'         => ['nome' => $r->equipe_nome],
            'micro_area'     => $r->micro_area,
            'desfecho_id'    => (int) $r->desfecho_id,
            'acompanhamentos' => $this->formatAccompaniments($r),
        ], $rows);

        return response()->json(['visitas' => $visitas, 'total' => $total]);
    }

    /**
     * GET /visitas/{id}
     * Detalhe completo de uma visita (reutilizado pelo modal).
     */
    public function show(int $id): JsonResponse
    {
        $row = $this->db()->selectOne("
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
                a.ds_anotacao                    AS notes,
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
            LEFT JOIN tb_visita_domiciliar_acs a ON a.co_unico_visita_domiciliar = v.nu_uuid_ficha
            WHERE v.co_seq_fat_visita_domiciliar = ?
        ", [$id]);

        if (! $row) {
            return response()->json(['message' => 'Visita não encontrada.'], 404);
        }

        return response()->json($this->formatVisita($row, detail: true));
    }

    /**
     * GET /visitas/mapa?ano=X&quadrimestre=Y[&ine=Z&agente=W]
     * Pins georreferenciados para o mapa Leaflet (VisitasAcs.js — aba Mapa).
     */
    public function mapa(Request $request): JsonResponse
    {
        $request->validate([
            'ano'          => 'required|integer|min:2020|max:2030',
            'quadrimestre' => 'required|integer|min:1|max:3',
            'ine'          => 'nullable|string',
            'agente'       => 'nullable|string',
        ]);

        $ano  = (int) $request->ano;
        $quad = (int) $request->quadrimestre;

        [$where, $params] = $this->quadWhere($ano, $quad, $request->ine);

        if ($request->agente) {
            $where   .= ' AND p.no_profissional = ?';
            $params[] = $request->agente;
        }

        $rows = $this->db()->select("
            SELECT
                v.co_seq_fat_visita_domiciliar   AS id,
                v.nu_latitude::float             AS lat,
                v.nu_longitude::float            AS lng,
                p.no_profissional                AS agente,
                c.nu_cbo                         AS cbo,
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
        ", $params);

        // Campos nomeados conforme MapaVisitas.js espera: desfecho, agente, data, micro_area
        $pontos = array_map(fn($r) => [
            'id'         => (int) $r->id,
            'lat'        => (float) $r->lat,
            'lng'        => (float) $r->lng,
            'agente'     => $r->agente,
            'cbo'        => self::CBO_LABELS[$r->cbo] ?? $r->cbo,
            'equipe'     => $r->equipe_nome,
            'data'       => $r->data,
            'desfecho'   => (int) $r->desfecho,
            'micro_area' => $r->micro_area,
        ], $rows);

        return response()->json(['pontos' => $pontos]);
    }

    /**
     * GET /visitas/equipes
     * Equipes que possuem ACS/TACS registrados.
     */
    public function equipes(): JsonResponse
    {
        $cbos = implode("','", self::ACS_CBOS);

        $rows = $this->db()->select("
            SELECT DISTINCT e.nu_ine AS ine, e.no_equipe AS name
            FROM tb_fat_visita_domiciliar v
            JOIN tb_dim_cbo    c ON c.co_seq_dim_cbo    = v.co_dim_cbo
            JOIN tb_dim_equipe e ON e.co_seq_dim_equipe = v.co_dim_equipe
            WHERE c.nu_cbo IN ('{$cbos}')
            ORDER BY e.no_equipe
        ");

        return response()->json(['data' => $rows]);
    }

    /**
     * GET /visitas/agentes?ano=X&quadrimestre=Y[&ine=Z]
     * Estatísticas agregadas por agente no quadrimestre (VisitasAcs.js — aba Por Agente).
     */
    public function agentes(Request $request): JsonResponse
    {
        $request->validate([
            'ano'          => 'required|integer|min:2020|max:2030',
            'quadrimestre' => 'required|integer|min:1|max:3',
            'ine'          => 'nullable|string',
        ]);

        $ano  = (int) $request->ano;
        $quad = (int) $request->quadrimestre;

        [$where, $params] = $this->quadWhere($ano, $quad, $request->ine);

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
