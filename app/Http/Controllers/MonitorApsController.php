<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MonitorApsController extends MonitorApsBaseController
{
    private const THRESHOLDS = [
        // C1: range-based — Ótimo 50–70 %, Bom 30–50 %, Suficiente 10–30 %, Regular ≤10 % ou >70 %
        // classificarInd1() implementa a lógica de faixa; estes valores são usados apenas como meta display
        'ind1_acesso_aps'         => ['suficiente' => 10, 'bom' => 30, 'otimo' => 50],
        // C2–C7: Nota Metodológica oficial (Portaria 6.907/2025) — Regular <25, Suficiente 25–<50, Bom 50–<75, Ótimo ≥75
        'ind2_crianca'            => ['suficiente' => 25, 'bom' => 50, 'otimo' => 75],
        'ind3_gestante'           => ['suficiente' => 25, 'bom' => 50, 'otimo' => 75],
        'ind4_hipertensao'        => ['suficiente' => 25, 'bom' => 50, 'otimo' => 75],
        'ind5_diabetes'           => ['suficiente' => 25, 'bom' => 50, 'otimo' => 75],
        'ind6_idoso'              => ['suficiente' => 25, 'bom' => 50, 'otimo' => 75],
        // ind7–ind10: indicadores complementares — NÃO constam no conjunto oficial C1–C7 da Portaria 6.907/2025; thresholds estimados internamente
        'ind7_saude_mental'       => ['suficiente' => 15, 'bom' => 30, 'otimo' => 50],
        'ind8_visita_acs'         => ['suficiente' => 50, 'bom' => 70, 'otimo' => 85],
        'ind9_vacinacao'          => ['suficiente' => 70, 'bom' => 85, 'otimo' => 95],
        'ind10_interprofissional' => ['suficiente' => 20, 'bom' => 40, 'otimo' => 60],
        'ind11_mulher_cancer'     => ['suficiente' => 25, 'bom' => 50, 'otimo' => 75],
        // B1 (ind13): Nota Metodológica B1/2025 — Regular ≤1 %, Suficiente >1–3 %, Bom >3–5 %, Ótimo >5 %
        'ind13_acesso_bucal'      => ['suficiente' => 1, 'bom' => 3, 'otimo' => 5],
        // B2 (ind14): Nota Metodológica B2/2025 — Regular ≤25 %, Suficiente >25–50 %, Bom >50–75 %, Ótimo >75 %
        'ind14_conclusao'         => ['suficiente' => 25, 'bom' => 50, 'otimo' => 75],
        // B5 (ind15): Nota Metodológica B5/2025 — Regular ≤0,25 %, Suficiente >0,25–0,5 %, Bom >0,5–1 %, Ótimo >1 %
        'ind15_coletivas'         => ['suficiente' => 0.25, 'bom' => 0.5, 'otimo' => 1.0],
        'vinculo'                 => ['suficiente' => 40, 'bom' => 65, 'otimo' => 85],
    ];

    // co_seq_dim_imunobiologico das vacinas do calendário básico infantil
    // PENTA=15, VPC10=14, ROTA=17, MenC=6, VIP=16, VOPb=21
    private const VACINAS_IDS = [15, 14, 17, 6, 16, 21];

    private const REPASSE_FIXO_IED = [1 => 18000, 2 => 16000, 3 => 14000, 4 => 12000];
    private const REPASSE_CLASS    = ['regular' => 2000, 'suficiente' => 4000, 'bom' => 6000, 'otimo' => 8000];

    // ---------------------------------------------------------------
    // Endpoints públicos (auth:sanctum)
    // ---------------------------------------------------------------

    public function resumo(Request $request)
    {
        set_time_limit(120);
        ['ano' => $ano, 'quadrimestre' => $quad] = $this->params($request);
        try {
            $data = Cache::remember("aps_resumo_{$ano}_{$quad}", 600, function () use ($ano, $quad) {
                $cfg     = $this->apsConfig();
                $equipes = $this->db()->select(
                    'SELECT nu_ine, no_equipe FROM tb_dim_equipe WHERE st_registro_valido = 1 AND nu_ine != \'-\' ORDER BY no_equipe'
                );
                $vinculos = $this->calcularVinculo($ano, $quad);

                $indESF = [];
                foreach ($equipes as $e) {
                    $indESF = array_merge($indESF, $this->calcularESF($e->nu_ine, $ano, $quad));
                }
                $classQualidade = $this->mediaClassificacaoPorEquipe($indESF);

                $vincMap = array_column($vinculos, 'classificacao', 'ine');
                $equipesComClass = array_map(fn($e) => [
                    'ine'  => $e->nu_ine, 'nome' => $e->no_equipe, 'tipo' => 'eSF',
                    'classificacao_vinculo'   => $vincMap[$e->nu_ine]          ?? 'regular',
                    'classificacao_qualidade' => $classQualidade[$e->nu_ine]   ?? 'regular',
                ], $equipes);

                return [
                    'municipio'     => $cfg->municipio_nome,
                    'ibge'          => $cfg->municipio_ibge,
                    'periodo'       => ['ano' => $ano, 'quadrimestre' => $quad],
                    'total_equipes' => count($equipes),
                    'vinculos'      => $vinculos,
                    'qualidade'     => ['esf' => $indESF, 'esb' => []],
                    'repasse'       => $this->calcularRepasseEstimado($equipesComClass, $cfg->estrato_ied),
                ];
            });
            return response()->json($data);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function vinculo(Request $request)
    {
        ['ano' => $ano, 'quadrimestre' => $quad, 'ine' => $ine] = $this->params($request);
        try {
            $cacheKey = 'aps_vinculo_' . $ano . '_' . $quad . '_' . ($ine ?? 'all');
            $data = Cache::remember($cacheKey, 600, fn() => $this->calcularVinculo($ano, $quad, $ine));
            return response()->json(['periodo' => ['ano' => $ano, 'quadrimestre' => $quad], 'equipes' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function qualidade(Request $request)
    {
        set_time_limit(120);
        ['ano' => $ano, 'quadrimestre' => $quad, 'ine' => $ine, 'bloco' => $bloco] = $this->params($request);
        try {
            $cacheKey = 'aps_qualidade_' . $ano . '_' . $quad . '_' . ($ine ?? 'all') . '_' . ($bloco ?? 'esf');
            $indicadores = Cache::remember($cacheKey, 600, function () use ($ano, $quad, $ine, $bloco) {
                $sql = 'SELECT nu_ine FROM tb_dim_equipe WHERE st_registro_valido = 1 AND nu_ine != \'-\'';
                $bindings = [];
                if ($ine) { $sql .= ' AND nu_ine = ?'; $bindings[] = $ine; }
                $sql .= ' ORDER BY no_equipe';

                $equipes     = $this->db()->select($sql, $bindings);
                $indicadores = [];
                foreach ($equipes as $e) {
                    if ($bloco === 'esb') {
                        $indicadores = array_merge($indicadores, $this->calcularESB($e->nu_ine, $ano, $quad));
                    } else {
                        $indicadores = array_merge($indicadores, $this->calcularESF($e->nu_ine, $ano, $quad));
                    }
                }
                return $indicadores;
            });
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
            10 => 'calcularInd10', 11 => 'calcularInd11', 13 => 'calcularInd13',
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
        set_time_limit(120);
        ['ano' => $ano, 'quadrimestre' => $quad] = $this->params($request);
        try {
            $data = Cache::remember("aps_repasse_{$ano}_{$quad}", 600, function () use ($ano, $quad) {
                $estrato  = $this->apsConfig()->estrato_ied;
                $equipes  = $this->db()->select(
                    'SELECT nu_ine, no_equipe FROM tb_dim_equipe WHERE st_registro_valido = 1 AND nu_ine != \'-\' ORDER BY no_equipe'
                );
                $vinculos = $this->calcularVinculo($ano, $quad);

                $indESF = [];
                foreach ($equipes as $e) {
                    $indESF = array_merge($indESF, $this->calcularESF($e->nu_ine, $ano, $quad));
                }
                $classQualidade = $this->mediaClassificacaoPorEquipe($indESF);

                $vincMap = array_column($vinculos, 'classificacao', 'ine');
                $equipesComClass = array_map(fn($e) => [
                    'ine'  => $e->nu_ine, 'nome' => $e->no_equipe, 'tipo' => 'eSF',
                    'classificacao_vinculo'   => $vincMap[$e->nu_ine]        ?? 'regular',
                    'classificacao_qualidade' => $classQualidade[$e->nu_ine] ?? 'regular',
                ], $equipes);

                $repasse = $this->calcularRepasseEstimado($equipesComClass, $estrato);
                return [
                    'periodo'         => ['ano' => $ano, 'quadrimestre' => $quad],
                    'estrato_ied'     => $estrato,
                    'repasse'         => $repasse,
                    'total_municipal' => array_sum(array_column($repasse, 'total_estimado')),
                ];
            });
            return response()->json($data);
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
            10 => 'calcularInd10', 11 => 'calcularInd11', 13 => 'calcularInd13',
            14 => 'calcularInd14', 15 => 'calcularInd15',
        ];
        $method = $mapa[$indicadorId] ?? null;
        if (!$method) return response()->json(['error' => "Indicador {$indicadorId} não encontrado"], 404);

        $anos      = array_map('intval', explode(',', $request->query('anos', (string) date('Y'))));
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
            'ano'          => (int) $request->query('ano', (int) date('Y')),
            'quadrimestre' => (int) $request->query('quadrimestre', (int) ceil((int) date('n') / 4)),
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

    /** C1 é range-based: Ótimo 50–70 %, Bom 30–50 %, Suficiente 10–30 %, Regular ≤10 % ou >70 % */
    private function classificarInd1(float $pct): string
    {
        if ($pct > 70.0)  return 'regular';
        if ($pct >= 50.0) return 'otimo';
        if ($pct >= 30.0) return 'bom';
        if ($pct >= 10.0) return 'suficiente';
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

    /** Retorna [ine => classificacao] com a média dos ranks dos indicadores de cada equipe. */
    private function mediaClassificacaoPorEquipe(array $indicadores): array
    {
        $ordem  = ['regular' => 0, 'suficiente' => 1, 'bom' => 2, 'otimo' => 3];
        $chaves = array_keys($ordem);
        $ranks  = [];
        foreach ($indicadores as $ind) {
            if ($ind['indicador']['complementar'] ?? false) continue;
            $ine   = $ind['indicador']['equipe']['ine']             ?? null;
            $class = $ind['indicador']['resultado']['classificacao'] ?? 'regular';
            if ($ine) $ranks[$ine][] = $ordem[$class] ?? 0;
        }
        $resultado = [];
        foreach ($ranks as $ine => $r) {
            $resultado[$ine] = $chaves[(int) round(array_sum($r) / count($r))] ?? 'regular';
        }
        return $resultado;
    }

    private array $equipeNomeCache = [];

    private function nomeEquipe(string $ine): string
    {
        if (!array_key_exists($ine, $this->equipeNomeCache)) {
            $r = $this->db()->selectOne(
                'SELECT no_equipe FROM tb_dim_equipe WHERE nu_ine = ? AND st_registro_valido = 1 LIMIT 1',
                [$ine]
            );
            $this->equipeNomeCache[$ine] = $r?->no_equipe ?? '';
        }
        return $this->equipeNomeCache[$ine];
    }

    private function calcularVinculo(int $ano, int $quad, ?string $ine = null): array
    {
        // tb_dim_equipe não tem tp_equipe nem nu_cnes — apenas nu_ine e no_equipe
        // st_ativo → st_registro_valido = 1 AND nu_ine != '-'
        // tb_fat_cad_individual: co_cidadao → co_fat_cidadao_pec, st_ativo → st_ficha_inativa = 0
        // cadastro domiciliar: proxy via co_fat_cidadao_pec_responsvl NOT NULL
        $sql = "
            SELECT
              de.nu_ine, de.no_equipe,
              COUNT(DISTINCT fci.co_fat_cidadao_pec)                                   AS total_ind,
              COUNT(DISTINCT CASE WHEN fci.co_fat_cidadao_pec_responsvl IS NOT NULL
                THEN fci.co_fat_cidadao_pec END)                                       AS total_dom,
              COUNT(DISTINCT CASE WHEN EXTRACT(YEAR FROM AGE(CURRENT_DATE, fci.dt_nascimento)) < 5
                THEN fci.co_fat_cidadao_pec END)                                       AS criancas_0_5,
              COUNT(DISTINCT CASE WHEN EXTRACT(YEAR FROM AGE(CURRENT_DATE, fci.dt_nascimento)) >= 60
                THEN fci.co_fat_cidadao_pec END)                                       AS idosos_60_mais,
              COUNT(DISTINCT CASE WHEN dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
                THEN fci.co_fat_cidadao_pec END)                                       AS atualizados_quad
            FROM tb_fat_cad_individual fci
            JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            JOIN tb_dim_tempo  dt ON fci.co_dim_tempo  = dt.co_seq_dim_tempo
            WHERE de.st_registro_valido = 1 AND de.nu_ine != '-' AND fci.st_ficha_inativa = 0
        ";
        $bindings = [$ano, $quad];
        if ($ine) { $sql .= ' AND de.nu_ine = ?'; $bindings[] = $ine; }
        $sql .= ' GROUP BY de.nu_ine, de.no_equipe ORDER BY de.no_equipe';

        $t = self::THRESHOLDS['vinculo'];
        return array_map(function ($r) use ($t) {
            $ind  = (int) $r->total_ind;
            $dom  = (int) $r->total_dom;
            $pctC = $ind > 0 ? round($dom / $ind * 100, 1) : 0.0;
            $pctA = $ind > 0 ? round((int)$r->atualizados_quad / $ind * 100, 1) : 0.0;
            return [
                'ine'  => $r->nu_ine, 'nome' => $r->no_equipe, 'cnes' => null, 'tipo' => 'eSF',
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
                    'bolsa_familia'  => 0,
                    'bpc'            => 0,
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
        // C1–C7 (ind1–ind6, ind11): indicadores oficiais — entram na média de qualidade e no repasse
        foreach ([1,2,3,4,5,6,11] as $id) {
            try {
                $r = $this->{"calcularInd{$id}"}($ine, $ano, $quad);
                if ($r !== null) $results[] = $r;
            } catch (\Throwable) {}
        }
        // ind7–ind10: complementares — exibidos no painel mas NÃO entram na média de qualidade
        foreach ([7,8,9,10] as $id) {
            try {
                $r = $this->{"calcularInd{$id}"}($ine, $ano, $quad);
                if ($r !== null) {
                    $r['indicador']['complementar'] = true;
                    $results[] = $r;
                }
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
        // C1: % de atendimentos programados = programados / total × 100
        // Classificação range-based: Ótimo 50–70 %, Bom 30–50 %, Suficiente 10–30 %, Regular ≤10 % ou >70 %
        $rows = $this->db()->select("
            SELECT de.nu_ine, de.no_equipe,
              COUNT(CASE WHEN fai.co_dim_tipo_atendimento = 1 THEN 1 END) AS programados,
              COUNT(CASE WHEN fai.co_dim_tipo_atendimento = 2 THEN 1 END) AS espontaneos,
              COUNT(CASE WHEN fai.co_dim_tipo_atendimento = 3 THEN 1 END) AS escuta_inicial,
              COUNT(CASE WHEN fai.co_dim_tipo_atendimento = 4 THEN 1 END) AS consulta_dia,
              COUNT(CASE WHEN fai.co_dim_tipo_atendimento = 5 THEN 1 END) AS urgencia,
              COUNT(*) AS total
            FROM tb_fat_atendimento_individual fai
            JOIN tb_dim_equipe de ON fai.co_dim_equipe_1 = de.co_seq_dim_equipe
            JOIN tb_dim_tempo  dt ON fai.co_dim_tempo    = dt.co_seq_dim_tempo
            WHERE dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ? AND de.nu_ine = ?
              AND de.st_registro_valido = 1
            GROUP BY de.nu_ine, de.no_equipe
        ", [$ano, $quad, $ine]);

        if (!$rows) return null;
        $r          = $rows[0];
        $total      = (int)$r->total ?: 1;
        $prog       = (int)$r->programados;
        $percentual = round($prog / $total * 100, 1);
        $tipos = [
            ['nome' => 'Demanda programada',    'valor' => $prog,                   'total' => $total, 'pct' => round($prog                   / $total * 100, 1)],
            ['nome' => 'Demanda espontânea',    'valor' => (int)$r->espontaneos,    'total' => $total, 'pct' => round((int)$r->espontaneos    / $total * 100, 1)],
            ['nome' => 'Escuta inicial',        'valor' => (int)$r->escuta_inicial, 'total' => $total, 'pct' => round((int)$r->escuta_inicial / $total * 100, 1)],
            ['nome' => 'Consulta do dia',       'valor' => (int)$r->consulta_dia,   'total' => $total, 'pct' => round((int)$r->consulta_dia   / $total * 100, 1)],
            ['nome' => 'Urgência / emergência', 'valor' => (int)$r->urgencia,       'total' => $total, 'pct' => round((int)$r->urgencia       / $total * 100, 1)],
        ];
        return [
            'indicador' => [
                'id' => 1, 'nome' => 'Mais Acesso à Atenção Primária', 'bloco' => 'eSF_eAP',
                'equipe'  => ['ine' => $r->nu_ine, 'nome' => $r->no_equipe],
                'periodo' => ['ano' => $ano, 'quadrimestre' => $quad],
                'resultado' => [
                    'numerador'       => $prog,
                    'denominador'     => $total,
                    'percentual'      => $percentual,
                    'classificacao'   => $this->classificarInd1($percentual),
                    'meta_suficiente' => 10,
                    'meta_bom'        => 30,
                    'meta_otimo'      => 50,
                    'meta_otimo_max'  => 70,
                ],
                'subindicadores' => $tipos,
            ],
        ];
    }

    private function calcularInd2(string $ine, int $ano, int $quad): ?array
    {
        // Denominador: crianças < 24 meses cadastradas ativas
        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_fat_cidadao_pec) AS total
            FROM tb_fat_cad_individual fci
            JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ficha_inativa = 0
              AND fci.dt_nascimento > CURRENT_DATE - INTERVAL '24 months'
        ", [$ine]) ?: [null];
        $denominador = (int)($den?->total ?? 0);
        if (!$denominador) return null;

        // Sub1: ≥9 consultas médico/enfermeiro no período
        [$sub1] = $this->db()->select("
            SELECT COUNT(*) AS v FROM (
              SELECT fai.co_fat_cidadao_pec
              FROM tb_fat_atendimento_individual fai
              JOIN tb_dim_equipe de ON fai.co_dim_equipe_1 = de.co_seq_dim_equipe
              JOIN tb_dim_tempo  dt ON fai.co_dim_tempo    = dt.co_seq_dim_tempo
              JOIN tb_dim_cbo    dc ON fai.co_dim_cbo_1    = dc.co_seq_dim_cbo
              JOIN tb_fat_cad_individual fci ON fai.co_fat_cidadao_pec = fci.co_fat_cidadao_pec
                AND fci.dt_nascimento > CURRENT_DATE - INTERVAL '24 months'
                AND fci.st_ficha_inativa = 0
              WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
                AND dc.nu_cbo IN ('225142','225125','223505')
              GROUP BY fai.co_fat_cidadao_pec HAVING COUNT(*) >= 9
            ) sq
        ", [$ine, $ano, $quad]) ?: [null];

        // Sub2: ≥9 registros de peso no período
        [$sub2] = $this->db()->select("
            SELECT COUNT(*) AS v FROM (
              SELECT fai.co_fat_cidadao_pec
              FROM tb_fat_atendimento_individual fai
              JOIN tb_dim_equipe de ON fai.co_dim_equipe_1 = de.co_seq_dim_equipe
              JOIN tb_dim_tempo  dt ON fai.co_dim_tempo    = dt.co_seq_dim_tempo
              JOIN tb_fat_cad_individual fci ON fai.co_fat_cidadao_pec = fci.co_fat_cidadao_pec
                AND fci.dt_nascimento > CURRENT_DATE - INTERVAL '24 months'
                AND fci.st_ficha_inativa = 0
              WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
                AND fai.nu_peso IS NOT NULL
              GROUP BY fai.co_fat_cidadao_pec HAVING COUNT(*) >= 9
            ) sq
        ", [$ine, $ano, $quad]) ?: [null];

        // Sub3: ≥2 visitas ACS/TACS (CBO 515105/322255, desfecho=1)
        [$sub3] = $this->db()->select("
            SELECT COUNT(*) AS v FROM (
              SELECT fvd.co_fat_cidadao_pec
              FROM tb_fat_visita_domiciliar fvd
              JOIN tb_dim_equipe de ON fvd.co_dim_equipe = de.co_seq_dim_equipe
              JOIN tb_dim_tempo  dt ON fvd.co_dim_tempo  = dt.co_seq_dim_tempo
              JOIN tb_dim_cbo    dc ON fvd.co_dim_cbo    = dc.co_seq_dim_cbo
              JOIN tb_fat_cad_individual fci ON fvd.co_fat_cidadao_pec = fci.co_fat_cidadao_pec
                AND fci.dt_nascimento > CURRENT_DATE - INTERVAL '24 months'
                AND fci.st_ficha_inativa = 0
              WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
                AND dc.nu_cbo IN ('515105', '322255') AND fvd.co_dim_desfecho_visita = 1
              GROUP BY fvd.co_fat_cidadao_pec HAVING COUNT(*) >= 2
            ) sq
        ", [$ine, $ano, $quad]) ?: [null];

        // Sub4: vacinação — criança com todos os 6 imunobiológicos do calendário
        // ds_filtro_imunobiologico = '|id1|id2|...' com co_seq_dim_imunobiologico
        [$sub4] = $this->db()->select("
            SELECT COUNT(*) AS v FROM (
              SELECT fv.co_fat_cidadao_pec
              FROM tb_fat_vacinacao fv
              JOIN tb_dim_equipe de ON fv.co_dim_equipe = de.co_seq_dim_equipe
              JOIN tb_dim_tempo  dt ON fv.co_dim_tempo  = dt.co_seq_dim_tempo
              JOIN tb_fat_cad_individual fci ON fv.co_fat_cidadao_pec = fci.co_fat_cidadao_pec
                AND fci.dt_nascimento > CURRENT_DATE - INTERVAL '24 months'
                AND fci.st_ficha_inativa = 0
              WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
              GROUP BY fv.co_fat_cidadao_pec
              HAVING
                BOOL_OR(fv.ds_filtro_imunobiologico LIKE '%|15|%') AND
                BOOL_OR(fv.ds_filtro_imunobiologico LIKE '%|14|%') AND
                BOOL_OR(fv.ds_filtro_imunobiologico LIKE '%|17|%') AND
                BOOL_OR(fv.ds_filtro_imunobiologico LIKE '%|6|%')  AND
                BOOL_OR(fv.ds_filtro_imunobiologico LIKE '%|16|%') AND
                BOOL_OR(fv.ds_filtro_imunobiologico LIKE '%|21|%')
            ) sq
        ", [$ine, $ano, $quad]) ?: [null];

        $vals = [(int)($sub1?->v ?? 0), (int)($sub2?->v ?? 0), (int)($sub3?->v ?? 0), (int)($sub4?->v ?? 0)];
        $numerador  = min(...$vals);
        $percentual = round($numerador / $denominador * 100, 1);
        return $this->resultado(2, 'Cuidado Longitudinal da Criança', 'eSF_eAP',
            $ine, $this->nomeEquipe($ine), $ano, $quad, $numerador, $denominador, $percentual, 'ind2_crianca', [
                ['nome' => '≥9 consultas médico/enfermeiro', 'valor' => $vals[0], 'total' => $denominador],
                ['nome' => '≥9 registros peso/altura',       'valor' => $vals[1], 'total' => $denominador],
                ['nome' => '≥2 visitas ACS',                 'valor' => $vals[2], 'total' => $denominador],
                ['nome' => 'Vacinação completa',             'valor' => $vals[3], 'total' => $denominador],
            ]);
    }

    private function calcularInd3(string $ine, int $ano, int $quad): ?array
    {
        // vw_acompanhamento_pre_natal não existe no DW — cálculo direto
        // Denominador: gestantes ativas (st_gestante = 1)
        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_fat_cidadao_pec) AS total
            FROM tb_fat_cad_individual fci
            JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_gestante = 1 AND fci.st_ficha_inativa = 0
        ", [$ine]) ?: [null];
        $denominador = (int)($den?->total ?? 0);
        if (!$denominador) return null;

        // Numerador: gestantes com ≥6 atendimentos médico/enfermeiro no período
        [$num] = $this->db()->select("
            SELECT COUNT(*) AS total FROM (
              SELECT fai.co_fat_cidadao_pec
              FROM tb_fat_atendimento_individual fai
              JOIN tb_dim_equipe de ON fai.co_dim_equipe_1 = de.co_seq_dim_equipe
              JOIN tb_dim_tempo  dt ON fai.co_dim_tempo    = dt.co_seq_dim_tempo
              JOIN tb_dim_cbo    dc ON fai.co_dim_cbo_1    = dc.co_seq_dim_cbo
              JOIN tb_fat_cad_individual fci ON fai.co_fat_cidadao_pec = fci.co_fat_cidadao_pec
                AND fci.st_gestante = 1 AND fci.st_ficha_inativa = 0
              WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
                AND dc.nu_cbo IN ('225142','225125','223505')
              GROUP BY fai.co_fat_cidadao_pec HAVING COUNT(*) >= 6
            ) sq
        ", [$ine, $ano, $quad]) ?: [null];

        $num2 = (int)($num?->total ?? 0);
        $pct  = $denominador > 0 ? round($num2 / $denominador * 100, 1) : 0.0;
        return $this->resultado(3, 'Cuidado da Gestante e Puérpera', 'eSF_eAP',
            $ine, $this->nomeEquipe($ine), $ano, $quad, $num2, $denominador, $pct, 'ind3_gestante',
            [['nome' => 'Gestantes com ≥6 consultas médico/enfermeiro', 'valor' => $num2, 'total' => $denominador]]);
    }

    private function calcularInd4(string $ine, int $ano, int $quad): ?array
    {
        // vw_acompanhamento_hipertensao não existe — cálculo direto
        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_fat_cidadao_pec) AS total
            FROM tb_fat_cad_individual fci
            JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_hipertensao_arterial = 1 AND fci.st_ficha_inativa = 0
        ", [$ine]) ?: [null];
        $denominador = (int)($den?->total ?? 0);
        if (!$denominador) return null;

        [$num] = $this->db()->select("
            SELECT COUNT(*) AS total FROM (
              SELECT fai.co_fat_cidadao_pec
              FROM tb_fat_atendimento_individual fai
              JOIN tb_dim_equipe de ON fai.co_dim_equipe_1 = de.co_seq_dim_equipe
              JOIN tb_dim_tempo  dt ON fai.co_dim_tempo    = dt.co_seq_dim_tempo
              JOIN tb_fat_cad_individual fci ON fai.co_fat_cidadao_pec = fci.co_fat_cidadao_pec
                AND fci.st_hipertensao_arterial = 1 AND fci.st_ficha_inativa = 0
              WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
              GROUP BY fai.co_fat_cidadao_pec HAVING COUNT(*) >= 2
            ) sq
        ", [$ine, $ano, $quad]) ?: [null];

        $num2 = (int)($num?->total ?? 0);
        $pct  = $denominador > 0 ? round($num2 / $denominador * 100, 1) : 0.0;
        return $this->resultado(4, 'Cuidado da Pessoa com Hipertensão', 'eSF_eAP',
            $ine, $this->nomeEquipe($ine), $ano, $quad, $num2, $denominador, $pct, 'ind4_hipertensao',
            [['nome' => 'Hipertensos com ≥2 atendimentos', 'valor' => $num2, 'total' => $denominador]]);
    }

    private function calcularInd5(string $ine, int $ano, int $quad): ?array
    {
        // vw_acompanhamento_diabetes não existe — cálculo direto
        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_fat_cidadao_pec) AS total
            FROM tb_fat_cad_individual fci
            JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_diabete = 1 AND fci.st_ficha_inativa = 0
        ", [$ine]) ?: [null];
        $denominador = (int)($den?->total ?? 0);
        if (!$denominador) return null;

        [$num] = $this->db()->select("
            SELECT COUNT(*) AS total FROM (
              SELECT fai.co_fat_cidadao_pec
              FROM tb_fat_atendimento_individual fai
              JOIN tb_dim_equipe de ON fai.co_dim_equipe_1 = de.co_seq_dim_equipe
              JOIN tb_dim_tempo  dt ON fai.co_dim_tempo    = dt.co_seq_dim_tempo
              JOIN tb_fat_cad_individual fci ON fai.co_fat_cidadao_pec = fci.co_fat_cidadao_pec
                AND fci.st_diabete = 1 AND fci.st_ficha_inativa = 0
              WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
              GROUP BY fai.co_fat_cidadao_pec HAVING COUNT(*) >= 2
            ) sq
        ", [$ine, $ano, $quad]) ?: [null];

        $num2 = (int)($num?->total ?? 0);
        $pct  = $denominador > 0 ? round($num2 / $denominador * 100, 1) : 0.0;
        return $this->resultado(5, 'Cuidado da Pessoa com Diabetes', 'eSF_eAP',
            $ine, $this->nomeEquipe($ine), $ano, $quad, $num2, $denominador, $pct, 'ind5_diabetes',
            [['nome' => 'Diabéticos com ≥2 atendimentos', 'valor' => $num2, 'total' => $denominador]]);
    }

    private function calcularInd6(string $ine, int $ano, int $quad): ?array
    {
        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_fat_cidadao_pec) AS total
            FROM tb_fat_cad_individual fci
            JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ficha_inativa = 0
              AND fci.dt_nascimento < CURRENT_DATE - INTERVAL '60 years'
        ", [$ine]) ?: [null];
        $denominador = (int)($den?->total ?? 0);
        if (!$denominador) return null;

        [$num] = $this->db()->select("
            SELECT COUNT(DISTINCT fai.co_fat_cidadao_pec) AS total
            FROM tb_fat_atendimento_individual fai
            JOIN tb_dim_equipe de ON fai.co_dim_equipe_1 = de.co_seq_dim_equipe
            JOIN tb_dim_tempo  dt ON fai.co_dim_tempo    = dt.co_seq_dim_tempo
            JOIN tb_fat_cad_individual fci ON fai.co_fat_cidadao_pec = fci.co_fat_cidadao_pec
              AND fci.dt_nascimento < CURRENT_DATE - INTERVAL '60 years'
              AND fci.st_ficha_inativa = 0
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
        ", [$ine, $ano, $quad]) ?: [null];
        $numerador  = (int)($num?->total ?? 0);
        $percentual = round($numerador / $denominador * 100, 1);
        return $this->resultado(6, 'Cuidado da Pessoa Idosa', 'eSF_eAP',
            $ine, $this->nomeEquipe($ine), $ano, $quad, $numerador, $denominador, $percentual, 'ind6_idoso',
            [['nome' => 'Idosos atendidos no quadrimestre', 'valor' => $numerador, 'total' => $denominador]]);
    }

    private function calcularInd7(string $ine, int $ano, int $quad): ?array
    {
        // dim_ciap2/dim_cid10 não existem — usa ds_filtro_ciaps e ds_filtro_cids (texto pipe-separated)
        [$total] = $this->db()->select("
            SELECT COUNT(*) AS total
            FROM tb_fat_atendimento_individual fai
            JOIN tb_dim_equipe de ON fai.co_dim_equipe_1 = de.co_seq_dim_equipe
            JOIN tb_dim_tempo  dt ON fai.co_dim_tempo    = dt.co_seq_dim_tempo
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
        ", [$ine, $ano, $quad]) ?: [null];

        [$sm] = $this->db()->select("
            SELECT COUNT(*) AS total
            FROM tb_fat_atendimento_individual fai
            JOIN tb_dim_equipe de ON fai.co_dim_equipe_1 = de.co_seq_dim_equipe
            JOIN tb_dim_tempo  dt ON fai.co_dim_tempo    = dt.co_seq_dim_tempo
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
              AND (
                fai.ds_filtro_ciaps ~ '\\|(P76|P77|P78|P79|P80|P81|P82|P85|P86|P98|P99)\\|'
                OR fai.ds_filtro_cids ~ '\\|F[0-9]'
              )
        ", [$ine, $ano, $quad]) ?: [null];

        $numerador   = (int)($sm?->total ?? 0);
        $denominador = (int)($total?->total ?? 0) ?: 1;
        $percentual  = round($numerador / $denominador * 100, 1);
        return $this->resultado(7, 'Saúde Mental na APS', 'eSF_eAP',
            $ine, $this->nomeEquipe($ine), $ano, $quad, $numerador, $denominador, $percentual, 'ind7_saude_mental',
            [['nome' => 'Atendimentos de saúde mental', 'valor' => $numerador, 'total' => $denominador]]);
    }

    private function calcularInd8(string $ine, int $ano, int $quad): ?array
    {
        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_fat_cidadao_pec) AS total
            FROM tb_fat_cad_individual fci
            JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ficha_inativa = 0
        ", [$ine]) ?: [null];
        $denominador = (int)($den?->total ?? 0);
        if (!$denominador) return null;

        [$num] = $this->db()->select("
            SELECT COUNT(DISTINCT fvd.co_fat_cidadao_pec) AS total
            FROM tb_fat_visita_domiciliar fvd
            JOIN tb_dim_equipe de ON fvd.co_dim_equipe = de.co_seq_dim_equipe
            JOIN tb_dim_tempo  dt ON fvd.co_dim_tempo  = dt.co_seq_dim_tempo
            JOIN tb_dim_cbo    dc ON fvd.co_dim_cbo    = dc.co_seq_dim_cbo
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
              AND dc.nu_cbo IN ('515105', '322255') AND fvd.co_dim_desfecho_visita = 1
        ", [$ine, $ano, $quad]) ?: [null];
        $numerador  = (int)($num?->total ?? 0);
        $percentual = round($numerador / $denominador * 100, 1);
        return $this->resultado(8, 'Visita Domiciliar por ACS/TACS', 'eSF_eAP',
            $ine, $this->nomeEquipe($ine), $ano, $quad, $numerador, $denominador, $percentual, 'ind8_visita_acs',
            [['nome' => 'Pessoas com ≥1 visita ACS no quadrimestre', 'valor' => $numerador, 'total' => $denominador]]);
    }

    private function calcularInd9(string $ine, int $ano, int $quad): ?array
    {
        // Denominador: crianças < 2 anos ativas
        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_fat_cidadao_pec) AS total
            FROM tb_fat_cad_individual fci
            JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ficha_inativa = 0
              AND fci.dt_nascimento > CURRENT_DATE - INTERVAL '2 years'
        ", [$ine]) ?: [null];
        $denominador = (int)($den?->total ?? 0);
        if (!$denominador) return null;

        // Numerador: crianças com todos os 6 imunobiológicos do calendário
        // ds_filtro_imunobiologico = '|co_seq_dim_imunobiologico|...' (pipe-separated)
        // IDs: PENTA=15, VPC10=14, ROTA=17, MenC=6, VIP=16, VOPb=21
        [$num] = $this->db()->select("
            SELECT COUNT(*) AS total FROM (
              SELECT fv.co_fat_cidadao_pec
              FROM tb_fat_vacinacao fv
              JOIN tb_dim_equipe de ON fv.co_dim_equipe = de.co_seq_dim_equipe
              JOIN tb_dim_tempo  dt ON fv.co_dim_tempo  = dt.co_seq_dim_tempo
              JOIN tb_fat_cad_individual fci ON fv.co_fat_cidadao_pec = fci.co_fat_cidadao_pec
                AND fci.dt_nascimento > CURRENT_DATE - INTERVAL '2 years'
                AND fci.st_ficha_inativa = 0
              WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
              GROUP BY fv.co_fat_cidadao_pec
              HAVING
                BOOL_OR(fv.ds_filtro_imunobiologico LIKE '%|15|%') AND
                BOOL_OR(fv.ds_filtro_imunobiologico LIKE '%|14|%') AND
                BOOL_OR(fv.ds_filtro_imunobiologico LIKE '%|17|%') AND
                BOOL_OR(fv.ds_filtro_imunobiologico LIKE '%|6|%')  AND
                BOOL_OR(fv.ds_filtro_imunobiologico LIKE '%|16|%') AND
                BOOL_OR(fv.ds_filtro_imunobiologico LIKE '%|21|%')
            ) sq
        ", [$ine, $ano, $quad]) ?: [null];
        $numerador  = (int)($num?->total ?? 0);
        $percentual = round($numerador / $denominador * 100, 1);
        return $this->resultado(9, 'Vacinação na APS', 'eSF_eAP',
            $ine, $this->nomeEquipe($ine), $ano, $quad, $numerador, $denominador, $percentual, 'ind9_vacinacao',
            [['nome' => 'Crianças <2 anos com calendário básico completo', 'valor' => $numerador, 'total' => $denominador]]);
    }

    private function calcularInd10(string $ine, int $ano, int $quad): ?array
    {
        [$r] = $this->db()->select("
            SELECT COUNT(*) AS total_atividades, COALESCE(SUM(nu_participantes), 0) AS total_participantes
            FROM tb_fat_atividade_coletiva fac
            JOIN tb_dim_equipe de ON fac.co_dim_equipe = de.co_seq_dim_equipe
            JOIN tb_dim_tempo  dt ON fac.co_dim_tempo  = dt.co_seq_dim_tempo
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
        ", [$ine, $ano, $quad]) ?: [null];

        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_fat_cidadao_pec) AS total
            FROM tb_fat_cad_individual fci
            JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ficha_inativa = 0
        ", [$ine]) ?: [null];

        $participantes = (int)($r?->total_participantes ?? 0);
        $denominador   = (int)($den?->total ?? 0) ?: 1;
        $percentual    = round($participantes / $denominador * 100, 1);
        return $this->resultado(10, 'Ações Interprofissionais', 'eSF_eAP',
            $ine, $this->nomeEquipe($ine), $ano, $quad, $participantes, $denominador, $percentual, 'ind10_interprofissional', [
                ['nome' => 'Total de atividades coletivas', 'valor' => (int)($r?->total_atividades ?? 0), 'total' => '-'],
                ['nome' => 'Total de participantes',        'valor' => $participantes, 'total' => $denominador],
            ]);
    }

    private function calcularInd11(string $ine, int $ano, int $quad): ?array
    {
        // Data de referência = último dia do quadrimestre (Q1→abr, Q2→ago, Q3→dez)
        $mesRef  = $quad * 4;
        $refDate = date('Y-m-d', mktime(0, 0, 0, $mesRef + 1, 0, $ano));
        $refYM   = $ano * 12 + $mesRef;

        // Sub1: Citopatológico cervical — mulheres 25-64 anos, ≥1 exame últimos 36 meses
        [$den1] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_fat_cidadao_pec) AS total
            FROM tb_fat_cad_individual fci
            JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ficha_inativa = 0 AND fci.co_dim_sexo = 2
              AND EXTRACT(YEAR FROM AGE(? ::date, fci.dt_nascimento)) BETWEEN 25 AND 64
        ", [$ine, $refDate]) ?: [null];
        $den1 = (int)($den1?->total ?? 0);

        [$num1] = $this->db()->select("
            SELECT COUNT(DISTINCT p.co_fat_cidadao_pec) AS total
            FROM tb_fat_atd_ind_procedimentos p
            JOIN tb_dim_equipe de ON p.co_dim_equipe_1 = de.co_seq_dim_equipe
            JOIN tb_dim_tempo dt ON p.co_dim_tempo = dt.co_seq_dim_tempo
            JOIN tb_fat_cad_individual fci ON p.co_fat_cidadao_pec = fci.co_fat_cidadao_pec
              AND fci.st_ficha_inativa = 0 AND fci.co_dim_sexo = 2
              AND EXTRACT(YEAR FROM AGE(? ::date, fci.dt_nascimento)) BETWEEN 25 AND 64
            WHERE de.nu_ine = ?
              AND p.co_dim_procedimento_avaliado IN (21, 105, 106, 175, 328)
              AND (dt.nu_ano * 12 + dt.nu_mes) BETWEEN ? AND ?
        ", [$refDate, $ine, $refYM - 36, $refYM]) ?: [null];
        $num1 = (int)($num1?->total ?? 0);

        // Sub2: Vacina HPV — meninas 9-14 anos, ≥1 dose
        [$den2] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_fat_cidadao_pec) AS total
            FROM tb_fat_cad_individual fci
            JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ficha_inativa = 0 AND fci.co_dim_sexo = 2
              AND EXTRACT(YEAR FROM AGE(? ::date, fci.dt_nascimento)) BETWEEN 9 AND 14
        ", [$ine, $refDate]) ?: [null];
        $den2 = (int)($den2?->total ?? 0);

        [$num2] = $this->db()->select("
            SELECT COUNT(DISTINCT fv.co_fat_cidadao_pec) AS total
            FROM tb_fat_vacinacao fv
            JOIN tb_dim_equipe de ON fv.co_dim_equipe = de.co_seq_dim_equipe
            JOIN tb_fat_cad_individual fci ON fv.co_fat_cidadao_pec = fci.co_fat_cidadao_pec
              AND fci.st_ficha_inativa = 0 AND fci.co_dim_sexo = 2
              AND EXTRACT(YEAR FROM AGE(? ::date, fci.dt_nascimento)) BETWEEN 9 AND 14
            WHERE de.nu_ine = ?
              AND fv.ds_filtro_imunobiologico LIKE '%|13|%'
        ", [$refDate, $ine]) ?: [null];
        $num2 = (int)($num2?->total ?? 0);

        // Sub3: Atenção sexual/reprodutiva — mulheres 14-69 anos, ≥1 atendimento CIAP X últimos 12 meses
        [$den3] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_fat_cidadao_pec) AS total
            FROM tb_fat_cad_individual fci
            JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ficha_inativa = 0 AND fci.co_dim_sexo = 2
              AND EXTRACT(YEAR FROM AGE(? ::date, fci.dt_nascimento)) BETWEEN 14 AND 69
        ", [$ine, $refDate]) ?: [null];
        $den3 = (int)($den3?->total ?? 0);

        [$num3] = $this->db()->select("
            SELECT COUNT(DISTINCT fai.co_fat_cidadao_pec) AS total
            FROM tb_fat_atendimento_individual fai
            JOIN tb_dim_equipe de ON fai.co_dim_equipe_1 = de.co_seq_dim_equipe
            JOIN tb_dim_tempo dt ON fai.co_dim_tempo = dt.co_seq_dim_tempo
            JOIN tb_fat_cad_individual fci ON fai.co_fat_cidadao_pec = fci.co_fat_cidadao_pec
              AND fci.st_ficha_inativa = 0 AND fci.co_dim_sexo = 2
              AND EXTRACT(YEAR FROM AGE(? ::date, fci.dt_nascimento)) BETWEEN 14 AND 69
            WHERE de.nu_ine = ?
              AND fai.ds_filtro_ciaps ~ '\\|X'
              AND (dt.nu_ano * 12 + dt.nu_mes) BETWEEN ? AND ?
        ", [$refDate, $ine, $refYM - 12, $refYM]) ?: [null];
        $num3 = (int)($num3?->total ?? 0);

        // Sub4: Mamografia — mulheres 50-69 anos, ≥1 exame últimos 24 meses
        [$den4] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_fat_cidadao_pec) AS total
            FROM tb_fat_cad_individual fci
            JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ficha_inativa = 0 AND fci.co_dim_sexo = 2
              AND EXTRACT(YEAR FROM AGE(? ::date, fci.dt_nascimento)) BETWEEN 50 AND 69
        ", [$ine, $refDate]) ?: [null];
        $den4 = (int)($den4?->total ?? 0);

        [$num4] = $this->db()->select("
            SELECT COUNT(DISTINCT p.co_fat_cidadao_pec) AS total
            FROM tb_fat_atd_ind_procedimentos p
            JOIN tb_dim_equipe de ON p.co_dim_equipe_1 = de.co_seq_dim_equipe
            JOIN tb_dim_tempo dt ON p.co_dim_tempo = dt.co_seq_dim_tempo
            JOIN tb_fat_cad_individual fci ON p.co_fat_cidadao_pec = fci.co_fat_cidadao_pec
              AND fci.st_ficha_inativa = 0 AND fci.co_dim_sexo = 2
              AND EXTRACT(YEAR FROM AGE(? ::date, fci.dt_nascimento)) BETWEEN 50 AND 69
            WHERE de.nu_ine = ?
              AND p.co_dim_procedimento_avaliado IN (16, 46, 120, 51)
              AND (dt.nu_ano * 12 + dt.nu_mes) BETWEEN ? AND ?
        ", [$refDate, $ine, $refYM - 24, $refYM]) ?: [null];
        $num4 = (int)($num4?->total ?? 0);

        $pct1 = $den1 > 0 ? round($num1 / $den1 * 100, 1) : 0.0;
        $pct2 = $den2 > 0 ? round($num2 / $den2 * 100, 1) : 0.0;
        $pct3 = $den3 > 0 ? round($num3 / $den3 * 100, 1) : 0.0;
        $pct4 = $den4 > 0 ? round($num4 / $den4 * 100, 1) : 0.0;
        // Pesos C7 (Nota Metodológica): A=20 %, B=30 %, C=30 %, D=20 %
        $percentual = round($pct1 * 0.20 + $pct2 * 0.30 + $pct3 * 0.30 + $pct4 * 0.20, 1);

        return $this->resultado(11, 'Cuidado da Mulher na Prevenção do Câncer', 'eSF_eAP',
            $ine, $this->nomeEquipe($ine), $ano, $quad, null, null, $percentual, 'ind11_mulher_cancer', [
                ['nome' => 'Citopatológico cervical (25–64 anos)', 'valor' => $num1, 'total' => $den1],
                ['nome' => 'Vacina HPV (9–14 anos)',               'valor' => $num2, 'total' => $den2],
                ['nome' => 'Atenção sexual/reprodutiva (14–69)',   'valor' => $num3, 'total' => $den3],
                ['nome' => 'Mamografia (50–69 anos)',              'valor' => $num4, 'total' => $den4],
            ]);
    }

    // ---------------------------------------------------------------
    // Indicadores eSB (13-15)
    // ---------------------------------------------------------------

    private function calcularInd13(string $ine, int $ano, int $quad): ?array
    {
        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_fat_cidadao_pec) AS total
            FROM tb_fat_cad_individual fci
            JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ficha_inativa = 0
        ", [$ine]) ?: [null];
        $denominador = (int)($den?->total ?? 0);
        if (!$denominador) return null;

        // st_primeira_consulta → co_dim_tipo_consulta = 1
        [$num] = $this->db()->select("
            SELECT COUNT(*) AS total
            FROM tb_fat_atendimento_odonto fao
            JOIN tb_dim_equipe de ON fao.co_dim_equipe_1 = de.co_seq_dim_equipe
            JOIN tb_dim_tempo  dt ON fao.co_dim_tempo    = dt.co_seq_dim_tempo
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
              AND fao.co_dim_tipo_consulta = 1
        ", [$ine, $ano, $quad]) ?: [null];
        $numerador  = (int)($num?->total ?? 0);
        $percentual = round($numerador / $denominador * 100, 1);
        return $this->resultado(13, 'Acesso à Saúde Bucal', 'eSB',
            $ine, $this->nomeEquipe($ine), $ano, $quad, $numerador, $denominador, $percentual, 'ind13_acesso_bucal',
            [['nome' => 'Primeiras consultas odontológicas', 'valor' => $numerador, 'total' => $denominador]]);
    }

    private function calcularInd14(string $ine, int $ano, int $quad): ?array
    {
        // B2: denominador = número de primeiras consultas programáticas distintas no quadrimestre (Nota Metodológica B2/2025)
        [$total] = $this->db()->select("
            SELECT COUNT(DISTINCT fao.co_fat_cidadao_pec) AS total
            FROM tb_fat_atendimento_odonto fao
            JOIN tb_dim_equipe de ON fao.co_dim_equipe_1 = de.co_seq_dim_equipe
            JOIN tb_dim_tempo  dt ON fao.co_dim_tempo    = dt.co_seq_dim_tempo
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
              AND fao.co_dim_tipo_consulta = 1
        ", [$ine, $ano, $quad]) ?: [null];
        $denominador = (int)($total?->total ?? 0);
        if (!$denominador) return null;

        // numerador = pacientes com conclusão de tratamento registrada no mesmo período
        [$concl] = $this->db()->select("
            SELECT COUNT(DISTINCT fao.co_fat_cidadao_pec) AS total
            FROM tb_fat_atendimento_odonto fao
            JOIN tb_dim_equipe de ON fao.co_dim_equipe_1 = de.co_seq_dim_equipe
            JOIN tb_dim_tempo  dt ON fao.co_dim_tempo    = dt.co_seq_dim_tempo
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
              AND fao.st_conduta_tratamento_concluid = 1
        ", [$ine, $ano, $quad]) ?: [null];
        $numerador  = (int)($concl?->total ?? 0);
        $percentual = round($numerador / $denominador * 100, 1);
        return $this->resultado(14, 'Conclusão de Tratamento Odontológico', 'eSB',
            $ine, $this->nomeEquipe($ine), $ano, $quad, $numerador, $denominador, $percentual, 'ind14_conclusao',
            [['nome' => 'Tratamentos concluídos', 'valor' => $numerador, 'total' => $denominador]]);
    }

    private function calcularInd15(string $ine, int $ano, int $quad): ?array
    {
        // B5/2025: escovação dental supervisionada / crianças 6–12 cadastradas × 100
        // Numerador: participantes de atividades de escovação supervisionada (nu_participantes — DW não
        //   armazena faixa etária por atividade; contagem total é a melhor aproximação disponível)
        [$r] = $this->db()->select("
            SELECT COUNT(*) AS atividades, COALESCE(SUM(fac.nu_participantes), 0) AS participantes
            FROM tb_fat_atividade_coletiva fac
            JOIN tb_dim_equipe           de   ON fac.co_dim_equipe           = de.co_seq_dim_equipe
            JOIN tb_dim_tempo            dt   ON fac.co_dim_tempo            = dt.co_seq_dim_tempo
            JOIN tb_dim_tema_saude_bucal dtsb ON fac.co_dim_tema_saude_bucal = dtsb.co_seq_dim_tema_saude_bucal
            WHERE de.nu_ine = ? AND dt.nu_ano = ? AND CEIL(dt.nu_mes::numeric / 4) = ?
              AND LOWER(dtsb.ds_tema_saude_bucal) LIKE '%escova%'
        ", [$ine, $ano, $quad]) ?: [null];

        // Denominador: crianças 6–12 anos cadastradas na equipe (Nota B5/2025)
        [$den] = $this->db()->select("
            SELECT COUNT(DISTINCT fci.co_fat_cidadao_pec) AS total
            FROM tb_fat_cad_individual fci
            JOIN tb_dim_equipe de ON fci.co_dim_equipe = de.co_seq_dim_equipe
            WHERE de.nu_ine = ? AND fci.st_ficha_inativa = 0
              AND EXTRACT(YEAR FROM AGE(CURRENT_DATE, fci.dt_nascimento)) BETWEEN 6 AND 12
        ", [$ine]) ?: [null];

        $participantes = (int)($r?->participantes ?? 0);
        $denominador   = (int)($den?->total ?? 0) ?: 1;
        $percentual    = round($participantes / $denominador * 100, 1);
        return $this->resultado(15, 'Ações Coletivas em Saúde Bucal', 'eSB',
            $ine, $this->nomeEquipe($ine), $ano, $quad, $participantes, $denominador, $percentual, 'ind15_coletivas', [
                ['nome' => 'Atividades de escovação supervisionada', 'valor' => (int)($r?->atividades ?? 0), 'total' => '-'],
                ['nome' => 'Participantes em escovação supervisionada', 'valor' => $participantes, 'total' => $denominador],
            ]);
    }
}
