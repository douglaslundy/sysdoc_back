<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VisitaAcsController extends MonitorApsBaseController
{
    private const ACS_CBOS = ['515105', '322255'];

    // Desfechos confirmados no banco de produção:
    // 1=Visita realizada, 2=Visita recusada, 3=Ausente, 4=Não informado
    private const OUTCOME_COLORS = [
        1 => 'success',
        2 => 'error',
        3 => 'warning',
        4 => 'default',
    ];

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
            'cbo_label'        => $row->cbo === '515105' ? 'ACS' : 'TACS',
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

    public function mapa(Request $request): JsonResponse
    {
        $request->validate([
            'ano'    => 'required|integer|min:2020|max:2030',
            'mes'    => 'required|integer|min:1|max:12',
            'ine'    => 'nullable|string',
            'agente' => 'nullable|string',
        ]);

        $ano = (int) $request->ano;
        $mes = (int) $request->mes;

        [$where, $params] = $this->baseWhere($ano, $mes, $request->ine, $request->agente);

        $rows = $this->db()->select("
            SELECT
                v.co_seq_fat_visita_domiciliar   AS id,
                v.nu_latitude::float             AS lat,
                v.nu_longitude::float            AS lng,
                p.no_profissional                AS agent_name,
                c.nu_cbo                         AS cbo,
                e.nu_ine                         AS team_ine,
                e.no_equipe                      AS team_name,
                t.dt_registro                    AS visited_date,
                d.co_seq_dim_desfecho_visita     AS outcome_code,
                d.ds_desfecho_visita             AS outcome_label
            FROM tb_fat_visita_domiciliar v
            {$this->baseJoins()}
            WHERE {$where}
              AND v.nu_latitude  IS NOT NULL
              AND v.nu_longitude IS NOT NULL
            ORDER BY t.dt_registro DESC
        ", $params);

        return response()->json([
            'data' => array_map(fn($r) => [
                'id'           => (int) $r->id,
                'lat'          => (float) $r->lat,
                'lng'          => (float) $r->lng,
                'agent_name'   => $r->agent_name,
                'cbo'          => $r->cbo,
                'cbo_label'    => $r->cbo === '515105' ? 'ACS' : 'TACS',
                'team_ine'     => $r->team_ine,
                'team_name'    => $r->team_name,
                'visited_date' => $r->visited_date,
                'outcome_code' => (int) $r->outcome_code,
                'outcome_label'=> $r->outcome_label,
            ], $rows),
        ]);
    }

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

    public function agentes(Request $request): JsonResponse
    {
        $request->validate(['ine' => 'required|string']);

        $cbos = implode("','", self::ACS_CBOS);

        $rows = $this->db()->select("
            SELECT DISTINCT p.no_profissional AS name, c.nu_cbo AS cbo
            FROM tb_fat_visita_domiciliar v
            JOIN tb_dim_profissional p ON p.co_seq_dim_profissional = v.co_dim_profissional
            JOIN tb_dim_cbo         c ON c.co_seq_dim_cbo           = v.co_dim_cbo
            JOIN tb_dim_equipe      e ON e.co_seq_dim_equipe        = v.co_dim_equipe
            WHERE e.nu_ine = ? AND c.nu_cbo IN ('{$cbos}')
            ORDER BY p.no_profissional
        ", [$request->ine]);

        return response()->json(['data' => $rows]);
    }
}
