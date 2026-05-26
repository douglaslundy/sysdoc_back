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
                ['nu_cpf', 'co_cpf'])                          ?? 'nu_cpf',
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
            'busca'           => 'nullable|string|min:3|max:100',
            'page'            => 'nullable|integer|min:1',
            'per_page'        => 'nullable|integer|min:10|max:200',
        ]);

        $ine            = $request->query('ine');
        $profissionalId = $request->query('profissional_id');
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
            $hasPecNasc = $cols['hasPecNasc'];

            // Resolve data_nascimento e idade
            if ($dtNascCol) {
                $dtNascExpr = "TO_CHAR(fci.{$dtNascCol}, 'DD/MM/YYYY')";
                $idadeExpr  = "DATE_PART('year', AGE(fci.{$dtNascCol}))::int";
                $idosoExpr  = "CASE WHEN DATE_PART('year', AGE(fci.{$dtNascCol})) >= 60 THEN 1 ELSE 0 END";
                $pecJoin    = '';
            } elseif ($hasPecNasc) {
                $pecDtCol   = $this->firstExistingColumn('tb_fat_cidadao_pec', ['dt_nascimento', 'dt_nasc']) ?? 'dt_nascimento';
                $dtNascExpr = "TO_CHAR(pec.{$pecDtCol}, 'DD/MM/YYYY')";
                $idadeExpr  = "DATE_PART('year', AGE(pec.{$pecDtCol}))::int";
                $idosoExpr  = "CASE WHEN DATE_PART('year', AGE(pec.{$pecDtCol})) >= 60 THEN 1 ELSE 0 END";
                $pecJoin    = 'LEFT JOIN tb_fat_cidadao_pec pec ON pec.co_fat_cidadao_pec = fci.co_fat_cidadao_pec';
            } else {
                $dtNascExpr = 'NULL';
                $idadeExpr  = 'NULL';
                $idosoExpr  = '0';
                $pecJoin    = '';
            }

            $gestExpr = $stGestCol ? "fci.{$stGestCol}" : 'NULL';

            $where  = 'fci.st_ficha_inativa = 0 AND de.st_registro_valido = 1';
            $params = [];

            if ($ine) {
                $where   .= ' AND de.nu_ine = ?';
                $params[] = $ine;
            }
            if ($profissionalId) {
                $where   .= ' AND fci.co_dim_profissional = ?';
                $params[] = (int) $profissionalId;
            }
            if ($busca) {
                $b        = trim($busca);
                $digits   = preg_replace('/\D/', '', $b);
                $where   .= " AND (fci.{$nomeCol} ILIKE ? OR fci.{$cpfCol} = ? OR fci.{$cnsCol} = ?)";
                $params[] = '%' . $b . '%';
                $params[] = $digits;
                $params[] = $digits;
            }

            $sql = "
                SELECT
                    fci.co_fat_cidadao_pec,
                    fci.{$nomeCol}   AS nome,
                    fci.{$cpfCol}    AS cpf,
                    fci.{$cnsCol}    AS cns,
                    {$dtNascExpr}    AS data_nascimento,
                    {$idadeExpr}     AS idade,
                    de.nu_ine,
                    de.no_equipe,
                    dp.co_seq_dim_profissional AS profissional_id,
                    dp.no_profissional         AS agente,
                    dp.nu_cns                  AS cns_agente,
                    {$gestExpr}      AS st_gestante,
                    fci.{$hasCol}    AS st_has,
                    fci.{$dmCol}     AS st_dm,
                    {$idosoExpr}     AS st_idoso,
                    COUNT(*) OVER()  AS total_count
                FROM tb_fat_cad_individual fci
                JOIN tb_dim_equipe de
                    ON de.co_seq_dim_equipe = fci.co_dim_equipe
                LEFT JOIN tb_dim_profissional dp
                    ON dp.co_seq_dim_profissional = fci.co_dim_profissional
                {$pecJoin}
                WHERE {$where}
                ORDER BY fci.{$nomeCol}
                LIMIT ? OFFSET ?
            ";

            $rows  = $db->select($sql, array_merge($params, [$perPage, $offset]));
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
            $placeholders = implode(',', array_fill(0, count(self::ACS_CBOS), '?'));
            $where        = "dp.nu_cbo IN ({$placeholders})";
            $params       = self::ACS_CBOS;

            if ($ine) {
                $where   .= " AND dp.co_seq_dim_profissional IN (
                    SELECT DISTINCT fci.co_dim_profissional
                    FROM tb_fat_cad_individual fci
                    JOIN tb_dim_equipe de ON de.co_seq_dim_equipe = fci.co_dim_equipe
                    WHERE de.nu_ine = ? AND fci.st_ficha_inativa = 0
                )";
                $params[] = $ine;
            }

            $rows = $db->select("
                SELECT dp.co_seq_dim_profissional AS id,
                       dp.no_profissional         AS nome,
                       dp.nu_cns                  AS cns
                FROM tb_dim_profissional dp
                WHERE {$where}
                ORDER BY dp.no_profissional
            ", $params);

            return response()->json(['agentes' => $rows]);
        } catch (\Throwable $e) {
            Log::error('CidadaoAcs.agentes: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao consultar agentes.'], 500);
        }
    }
}
