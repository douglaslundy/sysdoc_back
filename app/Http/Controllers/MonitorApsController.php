<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitorApsController extends MonitorApsBaseController
{
    private const THRESHOLDS = [
        'ind1_acesso_aps'         => ['suficiente' => 20, 'bom' => 40, 'otimo' => 60],
        'ind2_crianca'            => ['suficiente' => 30, 'bom' => 60, 'otimo' => 80],
        'ind3_gestante'           => ['suficiente' => 40, 'bom' => 65, 'otimo' => 85],
        'ind4_hipertensao'        => ['suficiente' => 35, 'bom' => 60, 'otimo' => 80],
        'ind5_diabetes'           => ['suficiente' => 35, 'bom' => 60, 'otimo' => 80],
        'ind6_idoso'              => ['suficiente' => 30, 'bom' => 55, 'otimo' => 75],
        'ind7_saude_mental'       => ['suficiente' => 15, 'bom' => 30, 'otimo' => 50],
        'ind8_visita_acs'         => ['suficiente' => 50, 'bom' => 70, 'otimo' => 85],
        'ind9_vacinacao'          => ['suficiente' => 70, 'bom' => 85, 'otimo' => 95],
        'ind10_interprofissional' => ['suficiente' => 20, 'bom' => 40, 'otimo' => 60],
        'ind13_acesso_bucal'      => ['suficiente' => 20, 'bom' => 40, 'otimo' => 60],
        'ind14_conclusao'         => ['suficiente' => 30, 'bom' => 50, 'otimo' => 70],
        'ind15_coletivas'         => ['suficiente' => 10, 'bom' => 25, 'otimo' => 40],
        'vinculo'                 => ['suficiente' => 40, 'bom' => 65, 'otimo' => 85],
    ];

    private const VACINAS = [
        '0301060029', '0301060100', '0301060037',
        '0301060118', '0301060196', '0301060160',
    ];

    private const REPASSE_FIXO_IED = [1 => 18000, 2 => 16000, 3 => 14000, 4 => 12000];
    private const REPASSE_CLASS    = ['regular' => 2000, 'suficiente' => 4000, 'bom' => 6000, 'otimo' => 8000];

    // ---------------------------------------------------------------
    // Endpoints públicos (auth:sanctum)
    // ---------------------------------------------------------------

    public function resumo(Request $request)
    {
        ['ano' => $ano, 'quadrimestre' => $quad] = $this->params($request);
        try {
            $equipes  = $this->db()->select('SELECT nu_ine, no_equipe, tp_equipe FROM dim_equipe WHERE st_ativo = true ORDER BY tp_equipe, no_equipe');
            $vinculos = $this->calcularVinculo($ano, $quad);
            $estrato  = (int) env('MONITOR_APS_ESTRATO_IED', 4);

            $equipesComClass = array_map(fn($v) => [
                'ine'  => $v['ine'], 'nome' => $v['nome'], 'tipo' => $v['tipo'],
                'classificacao_vinculo'   => $v['classificacao'],
                'classificacao_qualidade' => 'regular',
            ], $vinculos);

            return response()->json([
                'municipio'     => env('MONITOR_APS_MUNICIPIO_NOME', ''),
                'ibge'          => env('MONITOR_APS_MUNICIPIO_IBGE', ''),
                'periodo'       => ['ano' => $ano, 'quadrimestre' => $quad],
                'total_equipes' => count($equipes),
                'vinculos'      => $vinculos,
                'repasse'       => $this->calcularRepasseEstimado($equipesComClass, $estrato),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function vinculo(Request $request)
    {
        ['ano' => $ano, 'quadrimestre' => $quad, 'ine' => $ine] = $this->params($request);
        try {
            $data = $this->calcularVinculo($ano, $quad, $ine);
            return response()->json(['periodo' => ['ano' => $ano, 'quadrimestre' => $quad], 'equipes' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function qualidade(Request $request)
    {
        ['ano' => $ano, 'quadrimestre' => $quad, 'ine' => $ine, 'bloco' => $bloco] = $this->params($request);
        try {
            $sql = 'SELECT nu_ine, tp_equipe FROM dim_equipe WHERE st_ativo = true';
            $bindings = [];
            if ($ine) { $sql .= ' AND nu_ine = ?'; $bindings[] = $ine; }
            $sql .= ' ORDER BY tp_equipe';

            $equipes     = $this->db()->select($sql, $bindings);
            $indicadores = [];

            foreach ($equipes as $e) {
                $isESF = in_array($e->tp_equipe, [70, 71]);
                $isESB = $e->tp_equipe === 72;
                if ($bloco === 'esb' && !$isESB) continue;
                if ($bloco === 'esf' && !$isESF) continue;
                if ($isESF) $indicadores = array_merge($indicadores, $this->calcularESF($e->nu_ine, $ano, $quad));
                if ($isESB) $indicadores = array_merge($indicadores, $this->calcularESB($e->nu_ine, $ano, $quad));
            }

            return response()->json(['periodo' => ['ano' => $ano, 'quadrimestre' => $quad], 'indicadores' => $indicadores]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function qualidadeIndicador(Request $request, int $id)
    {
        ['ano' => $ano, 'quadrimestre' => $quad, 'ine' => $ine] = $this->params($request);
        if (!$ine) return response()->json(['error' => 'Parâmetro ine é obrigatório'], 400);

        $mapa = [
            1 => 'calcularInd1',  2 => 'calcularInd2',  3 => 'calcularInd3',
            4 => 'calcularInd4',  5 => 'calcularInd5',  6 => 'calcularInd6',
            7 => 'calcularInd7',  8 => 'calcularInd8',  9 => 'calcularInd9',
            10 => 'calcularInd10', 13 => 'calcularInd13',
            14 => 'calcularInd14', 15 => 'calcularInd15',
        ];
        $method = $mapa[$id] ?? null;
        if (!$method) return response()->json(['error' => "Indicador {$id} não encontrado"], 404);

        try {
            return response()->json($this->$method($ine, $ano, $quad));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function repasse(Request $request)
    {
        ['ano' => $ano, 'quadrimestre' => $quad] = $this->params($request);
        $estrato = (int) env('MONITOR_APS_ESTRATO_IED', 4);
        try {
            $vinculos = $this->calcularVinculo($ano, $quad);
            $equipesComClass = array_map(fn($v) => [
                'ine'  => $v['ine'], 'nome' => $v['nome'], 'tipo' => $v['tipo'],
                'classificacao_vinculo'   => $v['classificacao'],
                'classificacao_qualidade' => 'regular',
            ], $vinculos);
            $repasse = $this->calcularRepasseEstimado($equipesComClass, $estrato);
            return response()->json([
                'periodo'         => ['ano' => $ano, 'quadrimestre' => $quad],
                'estrato_ied'     => $estrato,
                'repasse'         => $repasse,
                'total_municipal' => array_sum(array_column($repasse, 'total_estimado')),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function historico(Request $request)
    {
        ['ine' => $ine] = $this->params($request);
        $indicadorId = (int) $request->query('indicador_id');
        if (!$ine) return response()->json(['error' => 'ine é obrigatório'], 400);

        $mapa = [
            1 => 'calcularInd1',   2 => 'calcularInd2',  3 => 'calcularInd3',
            4 => 'calcularInd4',   5 => 'calcularInd5',  6 => 'calcularInd6',
            7 => 'calcularInd7',   8 => 'calcularInd8',  9 => 'calcularInd9',
            10 => 'calcularInd10', 13 => 'calcularInd13',
            14 => 'calcularInd14', 15 => 'calcularInd15',
        ];
        $method = $mapa[$indicadorId] ?? null;
        if (!$method) return response()->json(['error' => "Indicador {$indicadorId} não encontrado"], 404);

        $anos      = array_map('intval', explode(',', $request->query('anos', '2025')));
        $historico = [];

        try {
            foreach ($anos as $ano) {
                foreach ([1, 2, 3] as $quad) {
                    try {
                        $d = $this->$method($ine, $ano, $quad);
                        if ($d) $historico[] = ['ano' => $ano, 'quadrimestre' => $quad, ...($d['indicador']['resultado'] ?? [])];
                    } catch (\Throwable) {}
                }
            }
            return response()->json(['ine' => $ine, 'indicador_id' => $indicadorId, 'historico' => $historico]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------
    // Helpers privados
    // ---------------------------------------------------------------

    private function params(Request $request): array
    {
        return [
            'ano'          => (int) $request->query('ano', 2025),
            'quadrimestre' => (int) $request->query('quadrimestre', 2),
            'ine'          => $request->query('ine'),
            'bloco'        => $request->query('bloco'),
        ];
    }

    private function classificar(float $percentual, array $thresholds): string
    {
        if ($percentual >= $thresholds['otimo'])      return 'otimo';
        if ($percentual >= $thresholds['bom'])        return 'bom';
        if ($percentual >= $thresholds['suficiente']) return 'suficiente';
        return 'regular';
    }

    private function resultado(int $id, string $nome, string $bloco, string $ine, string $nomeEquipe, int $ano, int $quad, $numerador, $denominador, float $percentual, string $thresholdKey, array $subindicadores): array
    {
        $t = self::THRESHOLDS[$thresholdKey];
        return [
            'indicador' => [
                'id' => $id, 'nome' => $nome, 'bloco' => $bloco,
                'equipe'  => ['ine' => $ine, 'nome' => $nomeEquipe],
                'periodo' => ['ano' => $ano, 'quadrimestre' => $quad],
                'resultado' => [
                    'numerador'       => $numerador,
                    'denominador'     => $denominador,
                    'percentual'      => $percentual,
                    'classificacao'   => $this->classificar($percentual, $t),
                    'meta_suficiente' => $t['suficiente'],
                    'meta_bom'        => $t['bom'],
                    'meta_otimo'      => $t['otimo'],
                ],
                'subindicadores' => $subindicadores,
            ],
        ];
    }

    private function calcularVinculo(int $ano, int $quad, ?string $ine = null): array
    {
        $sql = "
            SELECT
              de.nu_ine, de.no_equipe, de.nu_cnes,
              CASE de.tp_equipe
                WHEN 70 THEN 'eSF' WHEN 71 THEN 'eAP'
                WHEN 72 THEN 'eSB' WHEN 80 THEN 'eMulti'
                ELSE de.tp_equipe::text
              END AS tipo,
              COUNT(DISTINCT fci.co_cidadao)                                            AS total_ind,
              COUNT(DISTINCT fcd.co_cidadao_responsavel)                                AS total_dom,
              COUNT(DISTINCT CASE WHEN fci.st_bolsa_familia THEN fci.co_cidadao END)    AS bolsa_familia,
              COUNT(DISTINCT CASE WHEN fci.st_bpc           THEN fci.co_cidadao END)    AS bpc,
              COUNT(DISTINCT CASE WHEN EXTRACT(YEAR FROM AGE(CURRENT_DATE, fci.dt_nascimento)) < 5
                THEN fci.co_cidadao END)                                                AS criancas_0_5,
              COUNT(DISTINCT CASE WHEN EXTRACT(YEAR FROM AGE(CURRENT_DATE, fci.dt_nascimento)) >= 60
                THEN fci.co_cidadao END)                                                AS idosos_60_mais,
              COUNT(DISTINCT CASE WHEN dt.nu_ano = ? AND dt.nu_quadrimestre = ?
                THEN fci.co_cidadao END)                                                AS atualizados_quad
            FROM fat_cad_individual fci
            JOIN dim_equipe de  ON fci.co_dim_equipe = de.co_seq_dim_equipe
            JOIN dim_tempo  dt  ON fci.co_dim_tempo  = dt.co_seq_dim_tempo
            LEFT JOIN fat_cad_domiciliar fcd ON fci.co_cidadao = fcd.co_cidadao_responsavel
            WHERE de.st_ativo = true AND fci.st_ativo = true
        ";
        $bindings = [$ano, $quad];
        if ($ine) { $sql .= ' AND de.nu_ine = ?'; $bindings[] = $ine; }
        $sql .= ' GROUP BY de.nu_ine, de.no_equipe, de.nu_cnes, de.tp_equipe ORDER BY de.tp_equipe, de.no_equipe';

        $t = self::THRESHOLDS['vinculo'];
        return array_map(function ($r) use ($t) {
            $ind  = (int) $r->total_ind;
            $dom  = (int) $r->total_dom;
            $pctC = $ind > 0 ? round($dom / $ind * 100, 1) : 0.0;
            $pctA = $ind > 0 ? round((int)$r->atualizados_quad / $ind * 100, 1) : 0.0;
            return [
                'ine'  => $r->nu_ine, 'nome' => $r->no_equipe, 'cnes' => $r->nu_cnes, 'tipo' => $r->tipo,
                'cadastros' => [
                    'individuais'     => $ind,
                    'domiciliares'    => $dom,
                    'pct_completude'  => $pctC,
                    'pct_atualizados' => $pctA,
                    'pontuacao'       => round($dom * 1.5 + ($ind - $dom) * 0.75, 2),
                ],
                'grupos_prioritarios' => [
                    'criancas_0_5'   => (int) $r->criancas_0_5,
                    'idosos_60_mais' => (int) $r->idosos_60_mais,
                    'bolsa_familia'  => (int) $r->bolsa_familia,
                    'bpc'            => (int) $r->bpc,
                ],
                'classificacao' => $this->classificar($pctC, $t),
            ];
        }, $this->db()->select($sql, $bindings));
    }

    private function calcularRepasseEstimado(array $equipes, int $estrato): array
    {
        $fixo = self::REPASSE_FIXO_IED[$estrato] ?? 12000;
        return array_map(function ($eq) use ($fixo) {
            $v = self::REPASSE_CLASS[$eq['classificacao_vinculo']]   ?? 0;
            $q = self::REPASSE_CLASS[$eq['classificacao_qualidade']] ?? 0;
            return [
                'ine' => $eq['ine'], 'nome' => $eq['nome'], 'tipo' => $eq['tipo'],
                'componente_fixo'      => $fixo,
                'componente_vinculo'   => $v,
                'componente_qualidade' => $q,
                'total_estimado'       => $fixo + $v + $q,
            ];
        }, $equipes);
    }

    private function calcularESF(string $ine, int $ano, int $quad): array
    {
        $results = [];
        foreach ([1,2,3,4,5,6,7,8,9,10] as $id) {
            try {
                $r = $this->{"calcularInd{$id}"}($ine, $ano, $quad);
                if ($r !== null) $results[] = $r;
            } catch (\Throwable) {}
        }
        return $results;
    }

    private function calcularESB(string $ine, int $ano, int $quad): array
    {
        $results = [];
        foreach ([13,14,15] as $id) {
            try {
                $r = $this->{"calcularInd{$id}"}($ine, $ano, $quad);
                if ($r !== null) $results[] = $r;
            } catch (\Throwable) {}
        }
        return $results;
    }

    // ---------------------------------------------------------------
    // Indicadores ESF (1-10)
    // ---------------------------------------------------------------

    private function calcularInd1(string $ine, int $ano, int $quad): ?array
    {
        $rows = $this->db()->select("
            SELECT de.nu_ine, de.no_equipe,
              COUNT(CASE WHEN fai.co_dim_tipo_atendimento = 1 THEN 1 END) AS programados,
              COUNT(CASE WHEN fai.co_dim_tipo_atendimento = 2 THEN 1 END) AS espontaneos,
              COUNT(CASE WHEN fai.co_dim_tipo_atendimento = 3 THEN 1 END) AS escuta_inicial,
              COUNT(CASE WHEN fai.co_dim_tipo_atendimento = 4 THEN 1 END) AS consulta_dia,
              COUNT(CASE WHEN fai.co_dim_tipo_atendimento = 5 THEN 1 END) AS urgencia,
              COUNT(*) AS total
            FROM fat_atendimento_individual fai
            JOIN dim_equipe de ON fai.co_dim_equipe = de.co_seq_dim_equipe
            JOIN dim_tempo  dt ON fai.co_dim_tempo  = dt.co_seq_dim_tempo
            WHERE dt.nu_ano = ? AND dt.nu_quadrimestre = ? AND de.nu_ine = ? AND de.st_ativo = true
            GROUP BY de.nu_ine, de.no_equipe
        ", [$ano, $quad, $ine]);

        if (!$rows) return null;
        $r     = $rows[0];
        $total = (int)$r->total ?: 1;
        $tipos = [
            ['nome' => 'Demanda programada',    'valor' => (int)$r->programados,    'pct' => round((int)$r->programados    / $total * 100, 1)],
            ['nome' => 'Demanda espontânea',    'valor' => (int)$r->espontaneos,    'pct' => round((int)$r->espontaneos    / $total * 100, 1)],
            ['nome' => 'Escuta inicial',        'valor' => (int)$r->escuta_inicial, 'pct' => round((int)$r->escuta_inicial / $total * 100, 1)],
            ['nome' => 'Consulta do dia',       'valor' => (int)$r->consulta_dia,  'pct' => round((int)$r->consulta_dia   / $total * 100, 1)],
            ['nome' => 'Urgência / emergência', 'valor' => (int)$r->urgencia,      'pct' => round((int)$r->urgencia       / $total * 100, 1)],
        ];
        $acima10    = count(array_filter($tipos, fn($t) => $t['pct'] >= 10));
        $percentual = round($acima10 / 5 * 100, 1);
        return $this->resultado(1, 'Mais Acesso à Atenção Primária', 'eSF_eAP',
            $r->nu_ine, $r->no_equipe, $ano, $quad, $acima10, 5, $percentual, 'ind1_acesso_aps',
            array_map(fn($t) => ['nome' => $t['nome'], 'valor' => $t['valor'], 'total' => (int)$r->total, 'pct' => $t['pct']], $tipos)
        );
    }

    private function calcularInd2(string $ine, int $ano, int $quad): ?array
    {
        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_cidadao) AS total
            FROM fat_cad_individual fci
            JOIN dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ativo = true
              AND fci.dt_nascimento > CURRENT_DATE - INTERVAL '24 months'
        ", [$ine]) ?: [null];
        $denominador = (int)($den?->total ?? 0);
        if (!$denominador) return null;

        $vacinas = '{' . implode(',', self::VACINAS) . '}';

        [$sub1] = $this->db()->select("
            SELECT COUNT(*) AS v FROM (
              SELECT fai.co_cidadao
              FROM fat_atendimento_individual fai
              JOIN dim_equipe de ON fai.co_dim_equipe = de.co_seq_dim_equipe
              JOIN dim_tempo  dt ON fai.co_dim_tempo  = dt.co_seq_dim_tempo
              JOIN dim_cbo    dc ON fai.co_dim_cbo    = dc.co_seq_dim_cbo
              JOIN fat_cad_individual fci ON fai.co_cidadao = fci.co_cidadao
                AND fci.dt_nascimento > CURRENT_DATE - INTERVAL '24 months'
              WHERE de.nu_ine = ? AND dt.nu_ano = ? AND dt.nu_quadrimestre = ?
                AND dc.nu_cbo IN ('225142','225125','223505')
              GROUP BY fai.co_cidadao HAVING COUNT(*) >= 9
            ) sq
        ", [$ine, $ano, $quad]) ?: [null];

        [$sub2] = $this->db()->select("
            SELECT COUNT(*) AS v FROM (
              SELECT fai.co_cidadao
              FROM fat_atendimento_individual fai
              JOIN dim_equipe de ON fai.co_dim_equipe = de.co_seq_dim_equipe
              JOIN dim_tempo  dt ON fai.co_dim_tempo  = dt.co_seq_dim_tempo
              JOIN fat_cad_individual fci ON fai.co_cidadao = fci.co_cidadao
                AND fci.dt_nascimento > CURRENT_DATE - INTERVAL '24 months'
              WHERE de.nu_ine = ? AND dt.nu_ano = ? AND dt.nu_quadrimestre = ?
                AND fai.nu_peso IS NOT NULL
              GROUP BY fai.co_cidadao HAVING COUNT(*) >= 9
            ) sq
        ", [$ine, $ano, $quad]) ?: [null];

        [$sub3] = $this->db()->select("
            SELECT COUNT(*) AS v FROM (
              SELECT fvd.co_cidadao
              FROM fat_visita_domiciliar fvd
              JOIN dim_equipe de ON fvd.co_dim_equipe = de.co_seq_dim_equipe
              JOIN dim_tempo  dt ON fvd.co_dim_tempo  = dt.co_seq_dim_tempo
              JOIN dim_cbo    dc ON fvd.co_dim_cbo    = dc.co_seq_dim_cbo
              JOIN fat_cad_individual fci ON fvd.co_cidadao = fci.co_cidadao
                AND fci.dt_nascimento > CURRENT_DATE - INTERVAL '24 months'
              WHERE de.nu_ine = ? AND dt.nu_ano = ? AND dt.nu_quadrimestre = ?
                AND dc.nu_cbo = '516220' AND fvd.st_visita_realizada = true
              GROUP BY fvd.co_cidadao HAVING COUNT(*) >= 2
            ) sq
        ", [$ine, $ano, $quad]) ?: [null];

        [$sub4] = $this->db()->select("
            SELECT COUNT(*) AS v FROM (
              SELECT fv.co_cidadao
              FROM fat_vacinacao fv
              JOIN dim_equipe de ON fv.co_dim_equipe = de.co_seq_dim_equipe
              JOIN dim_tempo  dt ON fv.co_dim_tempo  = dt.co_seq_dim_tempo
              JOIN fat_cad_individual fci ON fv.co_cidadao = fci.co_cidadao
                AND fci.dt_nascimento > CURRENT_DATE - INTERVAL '24 months'
              WHERE de.nu_ine = ? AND dt.nu_ano = ? AND dt.nu_quadrimestre = ?
                AND fv.nu_sigtap_imuno = ANY(?::varchar[])
              GROUP BY fv.co_cidadao HAVING COUNT(DISTINCT fv.nu_sigtap_imuno) >= 6
            ) sq
        ", [$ine, $ano, $quad, $vacinas]) ?: [null];

        $vals = [(int)($sub1?->v ?? 0), (int)($sub2?->v ?? 0), (int)($sub3?->v ?? 0), (int)($sub4?->v ?? 0)];
        $numerador  = min(...$vals);
        $percentual = round($numerador / $denominador * 100, 1);
        return $this->resultado(2, 'Cuidado Longitudinal da Criança', 'eSF_eAP',
            $ine, '', $ano, $quad, $numerador, $denominador, $percentual, 'ind2_crianca', [
                ['nome' => '≥9 consultas médico/enfermeiro', 'valor' => $vals[0], 'total' => $denominador],
                ['nome' => '≥9 registros peso/altura',       'valor' => $vals[1], 'total' => $denominador],
                ['nome' => '≥2 visitas ACS',                 'valor' => $vals[2], 'total' => $denominador],
                ['nome' => 'Vacinação completa',             'valor' => $vals[3], 'total' => $denominador],
            ]);
    }

    private function calcularInd3(string $ine, int $ano, int $quad): ?array
    {
        $rows = $this->db()->select("
            SELECT nu_ine, no_equipe,
              COUNT(*) AS denominador,
              SUM(CASE WHEN st_pn_adequado THEN 1 ELSE 0 END) AS numerador
            FROM vw_acompanhamento_pre_natal vpn
            JOIN dim_equipe de ON de.nu_ine = vpn.nu_ine
            JOIN dim_tempo  dt ON dt.nu_ano = ? AND dt.nu_quadrimestre = ?
            WHERE vpn.nu_ine = ?
            GROUP BY nu_ine, no_equipe
        ", [$ano, $quad, $ine]);
        if (!$rows) return null;
        $r = $rows[0]; $num = (int)$r->numerador; $den = (int)$r->denominador;
        $pct = $den > 0 ? round($num / $den * 100, 1) : 0.0;
        return $this->resultado(3, 'Cuidado da Gestante e Puérpera', 'eSF_eAP',
            $r->nu_ine, $r->no_equipe, $ano, $quad, $num, $den, $pct, 'ind3_gestante',
            [['nome' => 'Pré-natal adequado (≥6 consultas)', 'valor' => $num, 'total' => $den]]);
    }

    private function calcularInd4(string $ine, int $ano, int $quad): ?array
    {
        $rows = $this->db()->select("
            SELECT nu_ine, no_equipe,
              COUNT(*) AS denominador,
              SUM(CASE WHEN st_acompanhado THEN 1 ELSE 0 END) AS numerador
            FROM vw_acompanhamento_hipertensao WHERE nu_ine = ?
            GROUP BY nu_ine, no_equipe
        ", [$ine]);
        if (!$rows) return null;
        $r = $rows[0]; $num = (int)$r->numerador; $den = (int)$r->denominador;
        $pct = $den > 0 ? round($num / $den * 100, 1) : 0.0;
        return $this->resultado(4, 'Cuidado da Pessoa com Hipertensão', 'eSF_eAP',
            $r->nu_ine, $r->no_equipe, $ano, $quad, $num, $den, $pct, 'ind4_hipertensao',
            [['nome' => 'Hipertensos com ≥2 atendimentos', 'valor' => $num, 'total' => $den]]);
    }

    private function calcularInd5(string $ine, int $ano, int $quad): ?array
    {
        $rows = $this->db()->select("
            SELECT nu_ine, no_equipe,
              COUNT(*) AS denominador,
              SUM(CASE WHEN st_acompanhado THEN 1 ELSE 0 END) AS numerador
            FROM vw_acompanhamento_diabetes WHERE nu_ine = ?
            GROUP BY nu_ine, no_equipe
        ", [$ine]);
        if (!$rows) return null;
        $r = $rows[0]; $num = (int)$r->numerador; $den = (int)$r->denominador;
        $pct = $den > 0 ? round($num / $den * 100, 1) : 0.0;
        return $this->resultado(5, 'Cuidado da Pessoa com Diabetes', 'eSF_eAP',
            $r->nu_ine, $r->no_equipe, $ano, $quad, $num, $den, $pct, 'ind5_diabetes',
            [['nome' => 'Diabéticos com ≥2 atendimentos', 'valor' => $num, 'total' => $den]]);
    }

    private function calcularInd6(string $ine, int $ano, int $quad): ?array
    {
        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_cidadao) AS total
            FROM fat_cad_individual fci
            JOIN dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ativo = true
              AND fci.dt_nascimento < CURRENT_DATE - INTERVAL '60 years'
        ", [$ine]) ?: [null];
        $denominador = (int)($den?->total ?? 0);
        if (!$denominador) return null;

        [$num] = $this->db()->select("
            SELECT COUNT(DISTINCT fai.co_cidadao) AS total
            FROM fat_atendimento_individual fai
            JOIN dim_equipe de ON fai.co_dim_equipe = de.co_seq_dim_equipe
            JOIN dim_tempo  dt ON fai.co_dim_tempo  = dt.co_seq_dim_tempo
            JOIN fat_cad_individual fci ON fai.co_cidadao = fci.co_cidadao
              AND fci.dt_nascimento < CURRENT_DATE - INTERVAL '60 years'
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND dt.nu_quadrimestre = ?
        ", [$ine, $ano, $quad]) ?: [null];
        $numerador  = (int)($num?->total ?? 0);
        $percentual = round($numerador / $denominador * 100, 1);
        return $this->resultado(6, 'Cuidado da Pessoa Idosa', 'eSF_eAP',
            $ine, '', $ano, $quad, $numerador, $denominador, $percentual, 'ind6_idoso',
            [['nome' => 'Idosos atendidos no quadrimestre', 'valor' => $numerador, 'total' => $denominador]]);
    }

    private function calcularInd7(string $ine, int $ano, int $quad): ?array
    {
        [$total] = $this->db()->select("
            SELECT COUNT(*) AS total
            FROM fat_atendimento_individual fai
            JOIN dim_equipe de ON fai.co_dim_equipe = de.co_seq_dim_equipe
            JOIN dim_tempo  dt ON fai.co_dim_tempo  = dt.co_seq_dim_tempo
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND dt.nu_quadrimestre = ?
        ", [$ine, $ano, $quad]) ?: [null];

        [$sm] = $this->db()->select("
            SELECT COUNT(*) AS total
            FROM fat_atendimento_individual fai
            JOIN dim_equipe de ON fai.co_dim_equipe = de.co_seq_dim_equipe
            JOIN dim_tempo  dt ON fai.co_dim_tempo  = dt.co_seq_dim_tempo
            LEFT JOIN dim_ciap2 dc ON fai.co_dim_ciap2_avaliado = dc.co_seq_dim_ciap2
            LEFT JOIN dim_cid10 di ON fai.co_dim_cid10_avaliado = di.co_seq_dim_cid10
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND dt.nu_quadrimestre = ?
              AND (dc.nu_ciap2 IN ('P76','P77','P78','P79','P80','P81','P82','P85','P86','P98','P99')
                   OR di.nu_cid10 LIKE 'F%')
        ", [$ine, $ano, $quad]) ?: [null];

        $numerador   = (int)($sm?->total ?? 0);
        $denominador = (int)($total?->total ?? 0) ?: 1;
        $percentual  = round($numerador / $denominador * 100, 1);
        return $this->resultado(7, 'Saúde Mental na APS', 'eSF_eAP',
            $ine, '', $ano, $quad, $numerador, $denominador, $percentual, 'ind7_saude_mental',
            [['nome' => 'Atendimentos de saúde mental', 'valor' => $numerador, 'total' => $denominador]]);
    }

    private function calcularInd8(string $ine, int $ano, int $quad): ?array
    {
        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_cidadao) AS total
            FROM fat_cad_individual fci
            JOIN dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ativo = true
        ", [$ine]) ?: [null];
        $denominador = (int)($den?->total ?? 0);
        if (!$denominador) return null;

        [$num] = $this->db()->select("
            SELECT COUNT(DISTINCT fvd.co_cidadao) AS total
            FROM fat_visita_domiciliar fvd
            JOIN dim_equipe de ON fvd.co_dim_equipe = de.co_seq_dim_equipe
            JOIN dim_tempo  dt ON fvd.co_dim_tempo  = dt.co_seq_dim_tempo
            JOIN dim_cbo    dc ON fvd.co_dim_cbo    = dc.co_seq_dim_cbo
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND dt.nu_quadrimestre = ?
              AND dc.nu_cbo = '516220' AND fvd.st_visita_realizada = true
        ", [$ine, $ano, $quad]) ?: [null];
        $numerador  = (int)($num?->total ?? 0);
        $percentual = round($numerador / $denominador * 100, 1);
        return $this->resultado(8, 'Visita Domiciliar por ACS/TACS', 'eSF_eAP',
            $ine, '', $ano, $quad, $numerador, $denominador, $percentual, 'ind8_visita_acs',
            [['nome' => 'Pessoas com ≥1 visita ACS no quadrimestre', 'valor' => $numerador, 'total' => $denominador]]);
    }

    private function calcularInd9(string $ine, int $ano, int $quad): ?array
    {
        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_cidadao) AS total
            FROM fat_cad_individual fci
            JOIN dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ativo = true
              AND fci.dt_nascimento > CURRENT_DATE - INTERVAL '2 years'
        ", [$ine]) ?: [null];
        $denominador = (int)($den?->total ?? 0);
        if (!$denominador) return null;

        $vacinas = '{' . implode(',', self::VACINAS) . '}';
        [$num] = $this->db()->select("
            SELECT COUNT(*) AS total FROM (
              SELECT fv.co_cidadao
              FROM fat_vacinacao fv
              JOIN dim_equipe de ON fv.co_dim_equipe = de.co_seq_dim_equipe
              JOIN dim_tempo  dt ON fv.co_dim_tempo  = dt.co_seq_dim_tempo
              WHERE de.nu_ine = ? AND dt.nu_ano = ? AND dt.nu_quadrimestre = ?
                AND fv.nu_sigtap_imuno = ANY(?::varchar[]) AND fv.st_realizado = true
              GROUP BY fv.co_cidadao HAVING COUNT(DISTINCT fv.nu_sigtap_imuno) >= 4
            ) sq
        ", [$ine, $ano, $quad, $vacinas]) ?: [null];
        $numerador  = (int)($num?->total ?? 0);
        $percentual = round($numerador / $denominador * 100, 1);
        return $this->resultado(9, 'Vacinação na APS', 'eSF_eAP',
            $ine, '', $ano, $quad, $numerador, $denominador, $percentual, 'ind9_vacinacao',
            [['nome' => 'Crianças <2 anos com ≥4 vacinas do calendário', 'valor' => $numerador, 'total' => $denominador]]);
    }

    private function calcularInd10(string $ine, int $ano, int $quad): ?array
    {
        [$r] = $this->db()->select("
            SELECT COUNT(*) AS total_atividades, COALESCE(SUM(nu_participantes), 0) AS total_participantes
            FROM fat_ativ_coletiva fac
            JOIN dim_equipe de ON fac.co_dim_equipe = de.co_seq_dim_equipe
            JOIN dim_tempo  dt ON fac.co_dim_tempo  = dt.co_seq_dim_tempo
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND dt.nu_quadrimestre = ?
        ", [$ine, $ano, $quad]) ?: [null];

        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_cidadao) AS total
            FROM fat_cad_individual fci
            JOIN dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ativo = true
        ", [$ine]) ?: [null];

        $participantes = (int)($r?->total_participantes ?? 0);
        $denominador   = (int)($den?->total ?? 0) ?: 1;
        $percentual    = round($participantes / $denominador * 100, 1);
        return $this->resultado(10, 'Ações Interprofissionais', 'eSF_eAP',
            $ine, '', $ano, $quad, $participantes, $denominador, $percentual, 'ind10_interprofissional', [
                ['nome' => 'Total de atividades coletivas', 'valor' => (int)($r?->total_atividades ?? 0), 'total' => '-'],
                ['nome' => 'Total de participantes',        'valor' => $participantes, 'total' => $denominador],
            ]);
    }

    // ---------------------------------------------------------------
    // Indicadores eSB (13-15)
    // ---------------------------------------------------------------

    private function calcularInd13(string $ine, int $ano, int $quad): ?array
    {
        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_cidadao) AS total
            FROM fat_cad_individual fci
            JOIN dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ativo = true
        ", [$ine]) ?: [null];
        $denominador = (int)($den?->total ?? 0);
        if (!$denominador) return null;

        [$num] = $this->db()->select("
            SELECT COUNT(*) AS total
            FROM fat_atendimento_odontologico fao
            JOIN dim_equipe de ON fao.co_dim_equipe = de.co_seq_dim_equipe
            JOIN dim_tempo  dt ON fao.co_dim_tempo  = dt.co_seq_dim_tempo
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND dt.nu_quadrimestre = ?
              AND fao.st_primeira_consulta = true
        ", [$ine, $ano, $quad]) ?: [null];
        $numerador  = (int)($num?->total ?? 0);
        $percentual = round($numerador / $denominador * 100, 1);
        return $this->resultado(13, 'Acesso à Saúde Bucal', 'eSB',
            $ine, '', $ano, $quad, $numerador, $denominador, $percentual, 'ind13_acesso_bucal',
            [['nome' => 'Primeiras consultas odontológicas', 'valor' => $numerador, 'total' => $denominador]]);
    }

    private function calcularInd14(string $ine, int $ano, int $quad): ?array
    {
        [$total] = $this->db()->select("
            SELECT COUNT(*) AS total
            FROM fat_atendimento_odontologico fao
            JOIN dim_equipe de ON fao.co_dim_equipe = de.co_seq_dim_equipe
            JOIN dim_tempo  dt ON fao.co_dim_tempo  = dt.co_seq_dim_tempo
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND dt.nu_quadrimestre = ?
        ", [$ine, $ano, $quad]) ?: [null];
        $denominador = (int)($total?->total ?? 0);
        if (!$denominador) return null;

        [$concl] = $this->db()->select("
            SELECT COUNT(*) AS total
            FROM fat_atendimento_odontologico fao
            JOIN dim_equipe de ON fao.co_dim_equipe = de.co_seq_dim_equipe
            JOIN dim_tempo  dt ON fao.co_dim_tempo  = dt.co_seq_dim_tempo
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND dt.nu_quadrimestre = ?
              AND fao.st_conclusao_tratamento = true
        ", [$ine, $ano, $quad]) ?: [null];
        $numerador  = (int)($concl?->total ?? 0);
        $percentual = round($numerador / $denominador * 100, 1);
        return $this->resultado(14, 'Conclusão de Tratamento Odontológico', 'eSB',
            $ine, '', $ano, $quad, $numerador, $denominador, $percentual, 'ind14_conclusao',
            [['nome' => 'Tratamentos concluídos', 'valor' => $numerador, 'total' => $denominador]]);
    }

    private function calcularInd15(string $ine, int $ano, int $quad): ?array
    {
        [$r] = $this->db()->select("
            SELECT COUNT(*) AS atividades, COALESCE(SUM(nu_participantes), 0) AS participantes
            FROM fat_ativ_coletiva fac
            JOIN dim_equipe de ON fac.co_dim_equipe = de.co_seq_dim_equipe
            JOIN dim_tempo  dt ON fac.co_dim_tempo  = dt.co_seq_dim_tempo
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND dt.nu_quadrimestre = ?
        ", [$ine, $ano, $quad]) ?: [null];

        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_cidadao) AS total
            FROM fat_cad_individual fci
            JOIN dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ativo = true
        ", [$ine]) ?: [null];

        $participantes = (int)($r?->participantes ?? 0);
        $denominador   = (int)($den?->total ?? 0) ?: 1;
        $percentual    = round($participantes / $denominador * 100, 1);
        return $this->resultado(15, 'Ações Coletivas em Saúde Bucal', 'eSB',
            $ine, '', $ano, $quad, $participantes, $denominador, $percentual, 'ind15_coletivas', [
                ['nome' => 'Atividades coletivas realizadas', 'valor' => (int)($r?->atividades ?? 0), 'total' => '-'],
                ['nome' => 'Total de participantes',          'valor' => $participantes, 'total' => $denominador],
            ]);
    }
}
