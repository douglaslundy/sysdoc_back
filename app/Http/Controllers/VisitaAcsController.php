<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VisitaAcsController extends MonitorApsBaseController
{
    private const CBO_ACS = ['515105', '322255'];

    // ---------------------------------------------------------------
    // Endpoints
    // ---------------------------------------------------------------

    /** Resumo agregado por equipe e mês para o quadrimestre. */
    public function resumo(Request $request)
    {
        ['ano' => $ano, 'quadrimestre' => $quad] = $this->params($request);
        $ine = $request->query('ine');
        $cacheKey = "aps_visitas_resumo_{$ano}_{$quad}_" . ($ine ?? 'all');

        try {
            $data = Cache::remember($cacheKey, 600, function () use ($ano, $quad, $ine) {
                $cbos = "'" . implode("','", self::CBO_ACS) . "'";

                $where = 'de.st_registro_valido = 1 AND de.nu_ine != \'-\'
                  AND dc.nu_cbo IN (' . $cbos . ')
                  AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?';
                $bindings = [$ano, $quad];

                if ($ine) {
                    $where    .= ' AND de.nu_ine = ?';
                    $bindings[] = $ine;
                }

                $rows = $this->db()->select("
                    SELECT
                      de.nu_ine, de.no_equipe,
                      dt.nu_ano, dt.nu_mes,
                      COUNT(*)                                                              AS total,
                      SUM(CASE WHEN fvd.co_dim_desfecho_visita = 1 THEN 1 ELSE 0 END)     AS realizadas,
                      SUM(CASE WHEN fvd.co_dim_desfecho_visita = 2 THEN 1 ELSE 0 END)     AS recusadas,
                      SUM(CASE WHEN fvd.co_dim_desfecho_visita = 3 THEN 1 ELSE 0 END)     AS ausentes,
                      COUNT(DISTINCT fvd.co_fat_cidadao_pec)                              AS cidadaos
                    FROM tb_fat_visita_domiciliar fvd
                    JOIN tb_dim_equipe de ON fvd.co_dim_equipe = de.co_seq_dim_equipe
                    JOIN tb_dim_cbo    dc ON fvd.co_dim_cbo    = dc.co_seq_dim_cbo
                    JOIN tb_dim_tempo  dt ON fvd.co_dim_tempo   = dt.co_seq_dim_tempo
                    WHERE {$where}
                    GROUP BY de.nu_ine, de.no_equipe, dt.nu_ano, dt.nu_mes
                    ORDER BY de.no_equipe, dt.nu_mes
                ", $bindings);

                $porEquipe = [];
                $totais    = ['total' => 0, 'realizadas' => 0, 'recusadas' => 0, 'ausentes' => 0, 'cidadaos' => 0];
                $porMes    = [];

                foreach ($rows as $r) {
                    $key = $r->nu_ine;
                    if (!isset($porEquipe[$key])) {
                        $porEquipe[$key] = [
                            'ine' => $r->nu_ine, 'nome' => $r->no_equipe,
                            'total' => 0, 'realizadas' => 0, 'recusadas' => 0, 'ausentes' => 0, 'cidadaos' => 0,
                            'meses' => [],
                        ];
                    }
                    $porEquipe[$key]['total']      += (int) $r->total;
                    $porEquipe[$key]['realizadas']  += (int) $r->realizadas;
                    $porEquipe[$key]['recusadas']   += (int) $r->recusadas;
                    $porEquipe[$key]['ausentes']    += (int) $r->ausentes;
                    $porEquipe[$key]['cidadaos']    += (int) $r->cidadaos;
                    $porEquipe[$key]['meses'][]      = [
                        'mes' => (int) $r->nu_mes, 'total' => (int) $r->total,
                        'realizadas' => (int) $r->realizadas,
                    ];

                    $mes = (int) $r->nu_mes;
                    if (!isset($porMes[$mes])) {
                        $porMes[$mes] = ['mes' => $mes, 'total' => 0, 'realizadas' => 0, 'recusadas' => 0, 'ausentes' => 0];
                    }
                    $porMes[$mes]['total']      += (int) $r->total;
                    $porMes[$mes]['realizadas']  += (int) $r->realizadas;
                    $porMes[$mes]['recusadas']   += (int) $r->recusadas;
                    $porMes[$mes]['ausentes']    += (int) $r->ausentes;

                    $totais['total']      += (int) $r->total;
                    $totais['realizadas']  += (int) $r->realizadas;
                    $totais['recusadas']   += (int) $r->recusadas;
                    $totais['ausentes']    += (int) $r->ausentes;
                    $totais['cidadaos']    += (int) $r->cidadaos;
                }

                ksort($porMes);

                return [
                    'periodo'    => ['ano' => $ano, 'quadrimestre' => $quad],
                    'totais'     => $totais,
                    'por_equipe' => array_values($porEquipe),
                    'por_mes'    => array_values($porMes),
                ];
            });

            return response()->json($data);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /** Lista paginada de visitas com filtros. */
    public function lista(Request $request)
    {
        ['ano' => $ano, 'quadrimestre' => $quad] = $this->params($request);
        $ine        = $request->query('ine');
        $agente     = $request->query('agente');
        $microArea  = $request->query('micro_area');
        $desfecho   = $request->query('desfecho');
        $page       = max(1, (int) $request->query('page', 1));
        $perPage    = min(100, max(10, (int) $request->query('per_page', 50)));
        $offset     = ($page - 1) * $perPage;

        $cbos = "'" . implode("','", self::CBO_ACS) . "'";

        $where    = ['de.st_registro_valido = 1', "de.nu_ine != '-'",
                     "dc.nu_cbo IN ({$cbos})", 'dt.nu_ano = ?', 'CEIL(dt.nu_mes::numeric / 4) = ?'];
        $bindings = [$ano, $quad];

        if ($ine)       { $where[] = 'de.nu_ine = ?';                  $bindings[] = $ine; }
        if ($agente)    { $where[] = 'dp.no_profissional ILIKE ?';      $bindings[] = "%{$agente}%"; }
        if ($microArea) { $where[] = 'fvd.nu_micro_area = ?';           $bindings[] = $microArea; }
        if ($desfecho)  { $where[] = 'fvd.co_dim_desfecho_visita = ?';  $bindings[] = (int) $desfecho; }

        $whereStr = implode(' AND ', $where);

        try {
            [$countRow] = $this->db()->select("
                SELECT COUNT(*) AS total
                FROM tb_fat_visita_domiciliar fvd
                JOIN tb_dim_equipe     de  ON fvd.co_dim_equipe      = de.co_seq_dim_equipe
                JOIN tb_dim_cbo        dc  ON fvd.co_dim_cbo          = dc.co_seq_dim_cbo
                JOIN tb_dim_profissional dp ON fvd.co_dim_profissional = dp.co_seq_dim_profissional
                JOIN tb_dim_tempo       dt  ON fvd.co_dim_tempo        = dt.co_seq_dim_tempo
                WHERE {$whereStr}
            ", $bindings);

            $rows = $this->db()->select("
                SELECT
                  fvd.co_seq_fat_visita_domiciliar AS id,
                  de.nu_ine, de.no_equipe,
                  dp.no_profissional AS agente,
                  dc.nu_cbo, dc.no_cbo AS cbo_nome,
                  dt.dt_registro, dt.nu_mes, dt.nu_ano,
                  fvd.nu_micro_area,
                  fvd.co_dim_desfecho_visita AS desfecho_id,
                  dd.ds_desfecho_visita      AS desfecho,
                  fvd.nu_latitude, fvd.nu_longitude,
                  fvd.co_fat_cidadao_pec,
                  fvd.st_mot_vis_visita_periodica,
                  fvd.st_mot_vis_busca_ativa,
                  fvd.st_mot_vis_acompanhamento,
                  fvd.st_mot_vis_cad_att,
                  fvd.st_acomp_gestante,
                  fvd.st_acomp_crianca,
                  fvd.st_acomp_pessoa_hipertensao,
                  fvd.st_acomp_pessoa_diabetes,
                  fvd.st_acomp_pessoa_idosa,
                  fvd.st_acomp_saude_mental
                FROM tb_fat_visita_domiciliar fvd
                JOIN tb_dim_equipe       de  ON fvd.co_dim_equipe      = de.co_seq_dim_equipe
                JOIN tb_dim_cbo          dc  ON fvd.co_dim_cbo          = dc.co_seq_dim_cbo
                JOIN tb_dim_profissional  dp  ON fvd.co_dim_profissional = dp.co_seq_dim_profissional
                JOIN tb_dim_tempo         dt  ON fvd.co_dim_tempo        = dt.co_seq_dim_tempo
                JOIN tb_dim_desfecho_visita dd ON fvd.co_dim_desfecho_visita = dd.co_seq_dim_desfecho_visita
                WHERE {$whereStr}
                ORDER BY dt.dt_registro DESC, fvd.co_seq_fat_visita_domiciliar DESC
                LIMIT {$perPage} OFFSET {$offset}
            ", $bindings);

            return response()->json([
                'periodo'  => ['ano' => $ano, 'quadrimestre' => $quad],
                'total'    => (int) $countRow->total,
                'page'     => $page,
                'per_page' => $perPage,
                'visitas'  => array_map(fn($r) => [
                    'id'          => $r->id,
                    'equipe'      => ['ine' => $r->nu_ine, 'nome' => $r->no_equipe],
                    'agente'      => $r->agente,
                    'cbo'         => $r->nu_cbo,
                    'data'        => $r->dt_registro,
                    'mes'         => (int) $r->nu_mes,
                    'micro_area'  => $r->nu_micro_area,
                    'desfecho'    => $r->desfecho,
                    'desfecho_id' => (int) $r->desfecho_id,
                    'lat'         => $r->nu_latitude  ? (float) $r->nu_latitude  : null,
                    'lng'         => $r->nu_longitude ? (float) $r->nu_longitude : null,
                    'cidadao'     => $r->co_fat_cidadao_pec,
                    'motivacoes'  => array_filter([
                        $r->st_mot_vis_visita_periodica ? 'Visita periódica'    : null,
                        $r->st_mot_vis_busca_ativa      ? 'Busca ativa'         : null,
                        $r->st_mot_vis_acompanhamento   ? 'Acompanhamento'      : null,
                        $r->st_mot_vis_cad_att          ? 'Cadastro/atualiz.'   : null,
                    ]),
                    'acompanhamentos' => array_filter([
                        $r->st_acomp_gestante             ? 'Gestante'      : null,
                        $r->st_acomp_crianca              ? 'Criança'       : null,
                        $r->st_acomp_pessoa_hipertensao   ? 'Hipertensão'   : null,
                        $r->st_acomp_pessoa_diabetes      ? 'Diabetes'      : null,
                        $r->st_acomp_pessoa_idosa         ? 'Idoso'         : null,
                        $r->st_acomp_saude_mental         ? 'Saúde Mental'  : null,
                    ]),
                ], $rows),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /** Lista de agentes ACS/TACS com totais do período. */
    public function agentes(Request $request)
    {
        ['ano' => $ano, 'quadrimestre' => $quad] = $this->params($request);
        $ine = $request->query('ine');
        $cacheKey = "aps_visitas_agentes_{$ano}_{$quad}_" . ($ine ?? 'all');

        try {
            $data = Cache::remember($cacheKey, 600, function () use ($ano, $quad, $ine) {
                $cbos = "'" . implode("','", self::CBO_ACS) . "'";

                $where    = ['de.st_registro_valido = 1', "de.nu_ine != '-'",
                             "dc.nu_cbo IN ({$cbos})", 'dt.nu_ano = ?', 'CEIL(dt.nu_mes::numeric / 4) = ?'];
                $bindings = [$ano, $quad];
                if ($ine) { $where[] = 'de.nu_ine = ?'; $bindings[] = $ine; }
                $whereStr = implode(' AND ', $where);

                $rows = $this->db()->select("
                    SELECT
                      dp.no_profissional AS agente,
                      dc.nu_cbo, dc.no_cbo,
                      de.nu_ine, de.no_equipe,
                      COUNT(*)                                                               AS total,
                      SUM(CASE WHEN fvd.co_dim_desfecho_visita = 1 THEN 1 ELSE 0 END)      AS realizadas,
                      SUM(CASE WHEN fvd.co_dim_desfecho_visita = 2 THEN 1 ELSE 0 END)      AS recusadas,
                      SUM(CASE WHEN fvd.co_dim_desfecho_visita = 3 THEN 1 ELSE 0 END)      AS ausentes,
                      COUNT(DISTINCT fvd.co_fat_cidadao_pec)                               AS cidadaos,
                      COUNT(DISTINCT fvd.nu_micro_area)                                    AS micro_areas
                    FROM tb_fat_visita_domiciliar fvd
                    JOIN tb_dim_equipe       de  ON fvd.co_dim_equipe      = de.co_seq_dim_equipe
                    JOIN tb_dim_cbo          dc  ON fvd.co_dim_cbo          = dc.co_seq_dim_cbo
                    JOIN tb_dim_profissional  dp  ON fvd.co_dim_profissional = dp.co_seq_dim_profissional
                    JOIN tb_dim_tempo         dt  ON fvd.co_dim_tempo        = dt.co_seq_dim_tempo
                    WHERE {$whereStr}
                    GROUP BY dp.no_profissional, dc.nu_cbo, dc.no_cbo, de.nu_ine, de.no_equipe
                    ORDER BY total DESC
                ", $bindings);

                return array_map(fn($r) => [
                    'agente'      => $r->agente,
                    'cbo'         => $r->nu_cbo,
                    'cbo_nome'    => $r->no_cbo,
                    'equipe'      => ['ine' => $r->nu_ine, 'nome' => $r->no_equipe],
                    'total'       => (int) $r->total,
                    'realizadas'  => (int) $r->realizadas,
                    'recusadas'   => (int) $r->recusadas,
                    'ausentes'    => (int) $r->ausentes,
                    'cidadaos'    => (int) $r->cidadaos,
                    'micro_areas' => (int) $r->micro_areas,
                    'pct_realizadas' => $r->total > 0 ? round($r->realizadas / $r->total * 100, 1) : 0,
                ], $rows);
            });

            return response()->json(['periodo' => ['ano' => $ano, 'quadrimestre' => $quad], 'agentes' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /** Visitas com geolocalização para o mapa. */
    public function mapa(Request $request)
    {
        ['ano' => $ano, 'quadrimestre' => $quad] = $this->params($request);
        $ine = $request->query('ine');
        $cacheKey = "aps_visitas_mapa_{$ano}_{$quad}_" . ($ine ?? 'all');

        try {
            $data = Cache::remember($cacheKey, 600, function () use ($ano, $quad, $ine) {
                $cbos = "'" . implode("','", self::CBO_ACS) . "'";

                $where    = ['de.st_registro_valido = 1', "de.nu_ine != '-'",
                             "dc.nu_cbo IN ({$cbos})", 'dt.nu_ano = ?', 'CEIL(dt.nu_mes::numeric / 4) = ?',
                             'fvd.nu_latitude IS NOT NULL', 'fvd.nu_longitude IS NOT NULL',
                             'fvd.nu_latitude <> 0', 'fvd.nu_longitude <> 0'];
                $bindings = [$ano, $quad];
                if ($ine) { $where[] = 'de.nu_ine = ?'; $bindings[] = $ine; }
                $whereStr = implode(' AND ', $where);

                $rows = $this->db()->select("
                    SELECT
                      fvd.nu_latitude  AS lat,
                      fvd.nu_longitude AS lng,
                      fvd.co_dim_desfecho_visita AS desfecho_id,
                      de.nu_ine,
                      dp.no_profissional AS agente,
                      dt.dt_registro,
                      fvd.nu_micro_area
                    FROM tb_fat_visita_domiciliar fvd
                    JOIN tb_dim_equipe       de  ON fvd.co_dim_equipe      = de.co_seq_dim_equipe
                    JOIN tb_dim_cbo          dc  ON fvd.co_dim_cbo          = dc.co_seq_dim_cbo
                    JOIN tb_dim_profissional  dp  ON fvd.co_dim_profissional = dp.co_seq_dim_profissional
                    JOIN tb_dim_tempo         dt  ON fvd.co_dim_tempo        = dt.co_seq_dim_tempo
                    WHERE {$whereStr}
                    LIMIT 2000
                ", $bindings);

                return array_map(fn($r) => [
                    'lat'        => (float) $r->lat,
                    'lng'        => (float) $r->lng,
                    'desfecho'   => (int) $r->desfecho_id,
                    'ine'        => $r->nu_ine,
                    'agente'     => $r->agente,
                    'data'       => $r->dt_registro,
                    'micro_area' => $r->nu_micro_area,
                ], $rows);
            });

            return response()->json(['periodo' => ['ano' => $ano, 'quadrimestre' => $quad], 'pontos' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function params(Request $request): array
    {
        return [
            'ano'          => (int) $request->query('ano', (int) date('Y')),
            'quadrimestre' => (int) $request->query('quadrimestre', (int) ceil((int) date('n') / 4)),
        ];
    }
}
