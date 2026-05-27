<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CidadaoAcsController extends MonitorApsBaseController
{
    private const ACS_CBOS = ['515105', '322255'];

    private function resolveColumns(): array
    {
        $dtNascCol  = $this->firstExistingColumn('tb_fat_cad_individual',
            ['dt_nascimento', 'dt_nasc', 'dt_data_nascimento']);
        $stGestCol  = $this->firstExistingColumn('tb_fat_cad_individual',
            ['st_gestante', 'st_em_gestacao', 'in_gestante']);

        return [
            'cpfCol'    => $this->firstExistingColumn('tb_fat_cad_individual',
                ['nu_cpf', 'nu_cpf_cidadao', 'co_cpf'])        ?? 'nu_cpf',
            'cnsCol'    => $this->firstExistingColumn('tb_fat_cad_individual',
                ['nu_cns', 'co_cns'])                          ?? 'nu_cns',
            'nomeCol'   => $this->firstExistingColumn('tb_fat_cad_individual',
                ['no_cidadao', 'no_nome'])                     ?? 'no_cidadao',
            'hasCol'    => $this->firstExistingColumn('tb_fat_cad_individual',
                ['st_hipertensao_arterial', 'st_hipertensao']) ?? 'st_hipertensao_arterial',
            'dmCol'     => $this->firstExistingColumn('tb_fat_cad_individual',
                ['st_diabete', 'st_diabetes'])                 ?? 'st_diabete',
            'dtNascCol' => $dtNascCol,
            'stGestCol' => $stGestCol,
            'hasPec'    => $this->hasTable('tb_fat_cidadao_pec'),
            'hasCidadao'=> $this->hasTable('tb_cidadao'),
            'hasPecNasc'=> ($dtNascCol === null) && $this->hasTable('tb_fat_cidadao_pec'),
        ];
    }

    /**
     * GET /monitor-aps/cidadaos?ine=&profissional_id=&busca=&page=&per_page=
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'ine'             => 'nullable|string',
            'profissional_id' => 'nullable|integer',
            'agente'          => 'nullable|string|max:255',
            'condicao'        => 'nullable|string|in:gestante,has,dm,idoso',
            'busca'           => 'nullable|string|min:3|max:100',
            'page'            => 'nullable|integer|min:1',
            'per_page'        => 'nullable|integer|min:10|max:200',
        ]);

        $ine            = $request->query('ine');
        $profissionalId = $request->query('profissional_id');
        $agente         = $request->query('agente');
        $condicao       = $request->query('condicao');
        $busca          = $request->query('busca');
        $page           = max(1, (int) ($request->query('page', 1)));
        $perPage        = min(200, max(10, (int) ($request->query('per_page', 50))));
        $offset         = ($page - 1) * $perPage;

        try {
            $db = $this->db();
        } catch (\Throwable) {
            return response()->json(['error' => 'Não foi possível conectar ao e-SUS.'], 503);
        }

        try {
            $cols       = $this->resolveColumns();
            $cpfCol     = $cols['cpfCol'];
            $cnsCol     = $cols['cnsCol'];
            $nomeCol    = $cols['nomeCol'];
            $hasCol     = $cols['hasCol'];
            $dmCol      = $cols['dmCol'];
            $dtNascCol  = $cols['dtNascCol'];
            $stGestCol  = $cols['stGestCol'];
            $hasPec      = $cols['hasPec'];
            $hasCidadao  = $cols['hasCidadao'];
            $hasPecNasc = $cols['hasPecNasc'];
            $pecJoin     = $hasPec
                ? 'LEFT JOIN tb_fat_cidadao_pec pec ON pec.co_seq_fat_cidadao_pec = fci.co_fat_cidadao_pec'
                : '';
            $cidadaoJoin = ($hasPec && $hasCidadao)
                ? 'LEFT JOIN tb_cidadao cid ON cid.co_seq_cidadao = pec.co_cidadao'
                : '';
            $pecNomeCol  = $hasPec ? $this->firstExistingColumn('tb_fat_cidadao_pec', ['no_cidadao', 'no_nome']) : null;
            $cidNomeCol  = $hasCidadao ? $this->firstExistingColumn('tb_cidadao', ['no_cidadao', 'no_nome']) : null;
            $nomeParts   = [];
            if ($cidNomeCol && $hasPec) {
                $nomeParts[] = "NULLIF(CASE WHEN cid.{$cidNomeCol} ~ '^[0-9a-f]{64}$' THEN NULL ELSE cid.{$cidNomeCol} END, '')";
            }
            if ($pecNomeCol) {
                $nomeParts[] = "NULLIF(CASE WHEN pec.{$pecNomeCol} ~ '^[0-9a-f]{64}$' THEN NULL ELSE pec.{$pecNomeCol} END, '')";
            }
            $nomeParts[] = "NULLIF(CASE WHEN fci.{$nomeCol} ~ '^[0-9a-f]{64}$' THEN NULL ELSE fci.{$nomeCol} END, '')";
            $nomeParts[] = "'Nome não disponível'";
            $nomeExpr = count($nomeParts) > 1 ? 'COALESCE(' . implode(', ', $nomeParts) . ')' : $nomeParts[0];

            // Resolve data_nascimento e idade
            if ($dtNascCol) {
                $dtNascExpr = "TO_CHAR(fci.{$dtNascCol}, 'DD/MM/YYYY')";
                $idadeExpr  = "DATE_PART('year', AGE(fci.{$dtNascCol}))::int";
                $idosoExpr  = "CASE WHEN DATE_PART('year', AGE(fci.{$dtNascCol})) >= 60 THEN 1 ELSE 0 END";
            } elseif ($hasPecNasc) {
                $pecDtCol   = $this->firstExistingColumn('tb_fat_cidadao_pec', ['dt_nascimento', 'dt_nasc']) ?? 'dt_nascimento';
                $dtNascExpr = "TO_CHAR(pec.{$pecDtCol}, 'DD/MM/YYYY')";
                $idadeExpr  = "DATE_PART('year', AGE(pec.{$pecDtCol}))::int";
                $idosoExpr  = "CASE WHEN DATE_PART('year', AGE(pec.{$pecDtCol})) >= 60 THEN 1 ELSE 0 END";
            } else {
                $dtNascExpr = 'NULL';
                $idadeExpr  = 'NULL';
                $idosoExpr  = '0';
            }

            $gestExpr = $stGestCol ? "fci.{$stGestCol}" : 'NULL';
            $condicaoColumns = [
                'gestante' => 'st_gestante',
                'has'      => 'st_has',
                'dm'       => 'st_dm',
                'idoso'    => 'st_idoso',
            ];

            $where  = 'fci.st_ficha_inativa = 0 AND de.st_registro_valido = 1';
            $params = [];
            $outerWhere  = '';
            $outerParams = [];

            if ($ine) {
                $outerWhere   .= ' AND base.nu_ine = ?';
                $outerParams[] = $ine;
            }
            if ($profissionalId) {
                $outerWhere   .= ' AND base.profissional_id = ?';
                $outerParams[] = (int) $profissionalId;
            }
            if ($agente) {
                $outerWhere   .= ' AND base.agente = ?';
                $outerParams[] = $agente;
            }
            if ($busca) {
                $b        = trim($busca);
                $digits   = preg_replace('/\D/', '', $b);
                $where   .= " AND ({$nomeExpr} ILIKE ? OR fci.{$cpfCol} = ? OR fci.{$cnsCol} = ?)";
                $params[] = '%' . $b . '%';
                $params[] = $digits;
                $params[] = $digits;
            }

            $sql = "
                SELECT
                    base.co_fat_cidadao_pec,
                    base.nome,
                    base.cpf,
                    base.cns,
                    base.data_nascimento,
                    base.idade,
                    base.data_atualizacao,
                    base.nu_ine,
                    base.no_equipe,
                    base.profissional_id,
                    base.agente,
                    base.cns_agente,
                    base.st_gestante,
                    base.st_has,
                    base.st_dm,
                    base.st_idoso,
                    COUNT(*) OVER() AS total_count
                FROM (
                    SELECT
                        fci.co_fat_cidadao_pec,
                        {$nomeExpr}      AS nome,
                        fci.{$cpfCol}    AS cpf,
                        fci.{$cnsCol}    AS cns,
                        {$dtNascExpr}    AS data_nascimento,
                        {$idadeExpr}     AS idade,
                        TO_CHAR(TO_DATE(fci.co_dim_tempo::text, 'YYYYMMDD'), 'DD/MM/YYYY') AS data_atualizacao,
                        de.nu_ine,
                        de.no_equipe,
                        dp.co_seq_dim_profissional AS profissional_id,
                        dp.no_profissional         AS agente,
                        dp.nu_cns                  AS cns_agente,
                        {$gestExpr}      AS st_gestante,
                        fci.{$hasCol}    AS st_has,
                        fci.{$dmCol}     AS st_dm,
                        {$idosoExpr}     AS st_idoso,
                        ROW_NUMBER() OVER (
                            PARTITION BY fci.co_fat_cidadao_pec
                            ORDER BY fci.co_dim_tempo DESC NULLS LAST, fci.co_seq_fat_cad_individual DESC
                        ) AS row_num
                    FROM tb_fat_cad_individual fci
                    JOIN tb_dim_equipe de
                        ON de.co_seq_dim_equipe = fci.co_dim_equipe
                    LEFT JOIN tb_dim_profissional dp
                        ON dp.co_seq_dim_profissional = fci.co_dim_profissional
                    {$pecJoin}
                    {$cidadaoJoin}
                    WHERE {$where}
                ) base
                WHERE base.row_num = 1
                  " . ($condicao ? "AND base.{$condicaoColumns[$condicao]} = 1" : '') . "
                  {$outerWhere}
                ORDER BY base.nome
                LIMIT ? OFFSET ?
            ";

            $rows  = $db->select($sql, array_merge($params, $outerParams, [$perPage, $offset]));
            $total = count($rows) > 0 ? (int) $rows[0]->total_count : 0;

            return response()->json([
                'cidadaos' => $rows,
                'meta'     => [
                    'total'    => $total,
                    'page'     => $page,
                    'per_page' => $perPage,
                    'pages'    => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('CidadaoAcs.index: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao consultar cidadãos.'], 500);
        }
    }

    /**
     * GET /monitor-aps/cidadaos/agentes?ine=X
     */
    public function agentes(Request $request): JsonResponse
    {
        $request->validate([
            'ine' => 'nullable|string',
        ]);
        $ine = $request->query('ine');

        try {
            $db = $this->db();
        } catch (\Throwable) {
            return response()->json(['error' => 'Não foi possível conectar ao e-SUS.'], 503);
        }

        try {
            $outerWhere  = '';
            $outerParams = [];

            if ($ine) {
                $outerWhere   .= ' AND base.nu_ine = ?';
                $outerParams[] = $ine;
            }

            $rows = $db->select("
                SELECT
                    MIN(base.profissional_id) AS id,
                    base.nome                 AS nome
                FROM (
                    SELECT
                        dp.co_seq_dim_profissional AS profissional_id,
                        dp.no_profissional         AS nome,
                        de.nu_ine,
                        ROW_NUMBER() OVER (
                            PARTITION BY fci.co_fat_cidadao_pec
                            ORDER BY fci.co_dim_tempo DESC NULLS LAST, fci.co_seq_fat_cad_individual DESC
                        ) AS row_num
                    FROM tb_fat_cad_individual fci
                    JOIN tb_dim_equipe de
                        ON de.co_seq_dim_equipe = fci.co_dim_equipe
                    JOIN tb_dim_profissional dp
                        ON dp.co_seq_dim_profissional = fci.co_dim_profissional
                    WHERE fci.st_ficha_inativa = 0
                      AND de.st_registro_valido = 1
                      AND dp.st_registro_valido = 1
                      AND dp.no_profissional IS NOT NULL
                ) base
                WHERE base.row_num = 1
                  {$outerWhere}
                GROUP BY base.nome
                ORDER BY base.nome
            ", $outerParams);

            return response()->json(['agentes' => $rows]);
        } catch (\Throwable $e) {
            Log::error('CidadaoAcs.agentes: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao consultar agentes.'], 500);
        }
    }
}
