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
                ['nu_cpf', 'nu_cpf_cidadao', 'co_cpf']),
            'cnsCol'    => $this->firstExistingColumn('tb_fat_cad_individual',
                ['nu_cns', 'co_cns']),
            'nomeCol'   => $this->firstExistingColumn('tb_fat_cad_individual',
                ['no_cidadao', 'no_nome']),
            'hasCol'    => $this->firstExistingColumn('tb_fat_cad_individual',
                ['st_hipertensao_arterial', 'st_hipertensao']),
            'dmCol'     => $this->firstExistingColumn('tb_fat_cad_individual',
                ['st_diabete', 'st_diabetes']),
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
            'ine'              => 'nullable|string',
            'profissional_id'  => 'nullable|integer',
            'agente'           => 'nullable|string|max:255',
            'condicao'         => 'nullable|string|in:gestante,has,dm,idoso',
            'busca'            => 'nullable|string|min:3|max:100',
            'multi_domicilio'  => 'nullable|boolean',
            'page'             => 'nullable|integer|min:1',
            'per_page'         => 'nullable|integer|min:10|max:200',
        ]);

        $ine            = $request->query('ine') ?: null;
        $this->assertIneAllowed($request, $ine);
        $allowedInes    = $this->resolveAllowedInes($request);
        $profissionalId = $request->query('profissional_id');
        $agente         = $request->query('agente');
        $condicao       = $request->query('condicao');
        $busca          = $request->query('busca');
        $multiDomicilio = filter_var($request->query('multi_domicilio', false), FILTER_VALIDATE_BOOLEAN);
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
            $pecPkCol    = $hasPec ? $this->firstExistingColumn('tb_fat_cidadao_pec', ['co_seq_fat_cidadao_pec', 'co_fat_cidadao_pec']) : null;
            $pecCidCol   = $hasPec ? $this->firstExistingColumn('tb_fat_cidadao_pec', ['co_cidadao']) : null;
            $cidPkCol    = $hasCidadao ? $this->firstExistingColumn('tb_cidadao', ['co_seq_cidadao', 'co_cidadao']) : null;
            $pecJoin     = ($hasPec && $pecPkCol)
                ? "LEFT JOIN tb_fat_cidadao_pec pec ON pec.{$pecPkCol} = fci.co_fat_cidadao_pec"
                : '';
            $cidadaoJoin = ($pecJoin && $hasCidadao && $pecCidCol && $cidPkCol)
                ? "LEFT JOIN tb_cidadao cid ON cid.{$cidPkCol} = pec.{$pecCidCol}"
                : '';
            $pecNomeCol  = $hasPec ? $this->firstExistingColumn('tb_fat_cidadao_pec', ['no_cidadao', 'no_nome']) : null;
            $cidNomeCol  = $cidadaoJoin ? $this->firstExistingColumn('tb_cidadao', ['no_cidadao', 'no_nome']) : null;
            $pecCnsCol   = $pecJoin ? $this->firstExistingColumn('tb_fat_cidadao_pec', ['nu_cns', 'co_cns']) : null;
            $nomeParts   = [];
            if ($cidNomeCol) {
                $nomeParts[] = "NULLIF(CASE WHEN cid.{$cidNomeCol} ~ '^[0-9a-f]{64}$' THEN NULL ELSE cid.{$cidNomeCol} END, '')";
            }
            if ($pecJoin && $pecNomeCol) {
                $nomeParts[] = "NULLIF(CASE WHEN pec.{$pecNomeCol} ~ '^[0-9a-f]{64}$' THEN NULL ELSE pec.{$pecNomeCol} END, '')";
            }
            if ($nomeCol) {
                $nomeParts[] = "NULLIF(CASE WHEN fci.{$nomeCol} ~ '^[0-9a-f]{64}$' THEN NULL ELSE fci.{$nomeCol} END, '')";
            }
            $nomeParts[] = "'Nome não disponível'";
            $nomeExpr = count($nomeParts) > 1 ? 'COALESCE(' . implode(', ', $nomeParts) . ')' : $nomeParts[0];
            $cpfExpr  = $cpfCol ? "fci.{$cpfCol}" : 'NULL::text';
            // fci.nu_cns armazena '0' como sentinela quando CNS não foi digitado.
            // pec.nu_cns tem o CNS real. Priorizamos pec e filtramos '0' com NULLIF.
            $cnsParts = [];
            if ($pecCnsCol) $cnsParts[] = "NULLIF(TRIM(pec.{$pecCnsCol}), '0')";
            if ($cnsCol)    $cnsParts[] = "NULLIF(TRIM(fci.{$cnsCol}), '0')";
            $cnsExpr  = count($cnsParts) > 0
                ? 'COALESCE(' . implode(', ', $cnsParts) . ')'
                : 'NULL::text';
            $hasExpr  = $hasCol ? "fci.{$hasCol}" : '0';
            $dmExpr   = $dmCol ? "fci.{$dmCol}" : '0';

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

            // ── Múltiplos domicílios ──────────────────────────────────────────
            $hasFamilia    = $this->hasTable('tb_fat_cad_dom_familia')
                && $this->hasColumn('tb_fat_cad_dom_familia', 'co_fat_cidadao_pec')
                && $this->hasColumn('tb_fat_cad_dom_familia', 'co_fat_cad_domiciliar');
            $hasDomiciliar = $this->hasTable('tb_fat_cad_domiciliar')
                && $this->hasColumn('tb_fat_cad_domiciliar', 'co_seq_fat_cad_domiciliar');

            $multiDomicilioFilter = null;

            if ($hasFamilia && $hasDomiciliar) {
                $domLogCol    = $this->firstExistingColumn('tb_fat_cad_domiciliar', ['no_logradouro', 'ds_logradouro', 'logradouro']);
                $domNumCol    = $this->firstExistingColumn('tb_fat_cad_domiciliar', ['nu_num_logradouro', 'nu_numero', 'nu_endereco']);
                $domBairroCol = $this->firstExistingColumn('tb_fat_cad_domiciliar', ['no_bairro', 'ds_bairro', 'bairro']);

                // Filtra valores hasheados (MD5/SHA-256) que o e-SUS armazena em vez do texto real
                $hashFilter = fn(string $col) =>
                    "NULLIF(CASE WHEN d.{$col}::text ~ '^[0-9a-f]{32,64}$' THEN NULL ELSE NULLIF(TRIM(d.{$col}::text), '') END, '')";

                $concatParts = array_filter([
                    $domLogCol    ? $hashFilter($domLogCol)    : null,
                    $domNumCol    ? $hashFilter($domNumCol)    : null,
                    $domBairroCol ? $hashFilter($domBairroCol) : null,
                ]);

                // Quando campos de endereço estiverem todos hasheados/nulos, exibe ID do domicílio como fallback
                $addrExpr = count($concatParts) > 0
                    ? 'NULLIF(CONCAT_WS(\', \', ' . implode(', ', $concatParts) . '), \'\')'
                    : 'NULL';
                $concatExpr = ($addrExpr !== 'NULL')
                    ? "COALESCE({$addrExpr}, 'Domicílio #' || d.co_seq_fat_cad_domiciliar::text)"
                    : "'Domicílio #' || d.co_seq_fat_cad_domiciliar::text";

                // Filtros de cadastro ativo — padrão das tabelas DW do e-SUS
                // co_dim_tempo_validade = 30001231 → registro vigente (sem data de fim)
                // st_recusa / st_mudou → membro não recusou cadastro e não mudou
                $famFilters = [];
                if ($this->hasColumn('tb_fat_cad_dom_familia', 'co_dim_tempo_validade')) {
                    $famFilters[] = 'f.co_dim_tempo_validade = 30001231';
                }
                if ($this->hasColumn('tb_fat_cad_dom_familia', 'st_recusa_cadastro')) {
                    $famFilters[] = 'COALESCE(f.st_recusa_cadastro, 0) = 0';
                }
                if ($this->hasColumn('tb_fat_cad_dom_familia', 'st_mudou')) {
                    $famFilters[] = 'COALESCE(f.st_mudou, 0) = 0';
                }
                $famAtivoFilter = $famFilters ? ('AND ' . implode(' AND ', $famFilters)) : '';

                $domFilters = [];
                if ($this->hasColumn('tb_fat_cad_domiciliar', 'co_dim_tempo_validade')) {
                    $domFilters[] = 'd.co_dim_tempo_validade = 30001231';
                }
                if ($this->hasColumn('tb_fat_cad_domiciliar', 'st_recusa_cadastro')) {
                    $domFilters[] = 'COALESCE(d.st_recusa_cadastro, 0) = 0';
                }
                $domAtivoFilter = $domFilters ? ('AND ' . implode(' AND ', $domFilters)) : '';

                $domiciliosExpr = "(
                    SELECT STRING_AGG(DISTINCT {$concatExpr}, ' | ')
                    FROM tb_fat_cad_dom_familia f
                    JOIN tb_fat_cad_domiciliar d
                        ON d.co_seq_fat_cad_domiciliar = f.co_fat_cad_domiciliar
                    WHERE f.co_fat_cidadao_pec = fci.co_fat_cidadao_pec
                      {$famAtivoFilter}
                      {$domAtivoFilter}
                )";

                // Filtro de múltiplos domicílios: mesmos joins e filtros de ativo para consistência
                $multiDomicilioFilter = "(
                    SELECT COUNT(DISTINCT f.co_fat_cad_domiciliar)
                    FROM tb_fat_cad_dom_familia f
                    JOIN tb_fat_cad_domiciliar d
                        ON d.co_seq_fat_cad_domiciliar = f.co_fat_cad_domiciliar
                    WHERE f.co_fat_cidadao_pec = base.co_fat_cidadao_pec
                      {$famAtivoFilter}
                      {$domAtivoFilter}
                ) > 1";
            } else {
                $domiciliosExpr = 'NULL::text';
            }

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

            [$ineWhere, $ineBindings] = $this->buildIneWhere($ine, $allowedInes, 'base.nu_ine');
            if ($ineWhere) {
                $outerWhere   .= " AND {$ineWhere}";
                $outerParams   = array_merge($outerParams, $ineBindings);
            }
            if ($profissionalId) {
                $outerWhere   .= ' AND base.profissional_id = ?';
                $outerParams[] = (int) $profissionalId;
            }
            if ($agente) {
                $outerWhere   .= ' AND base.agente = ?';
                $outerParams[] = $agente;
            }
            if ($multiDomicilio && $multiDomicilioFilter) {
                $outerWhere .= " AND {$multiDomicilioFilter}";
            }
            if ($busca) {
                $b        = trim($busca);
                $digits   = preg_replace('/\D/', '', $b);
                $searchParts = ["{$nomeExpr} ILIKE ?"];
                $params[] = '%' . $b . '%';
                if ($cpfCol) {
                    $searchParts[] = "fci.{$cpfCol} = ?";
                    $params[] = $digits;
                }
                if ($cnsCol) {
                    $searchParts[] = "fci.{$cnsCol} = ?";
                    $params[] = $digits;
                }
                if ($pecCnsCol) {
                    $searchParts[] = "pec.{$pecCnsCol} = ?";
                    $params[] = $digits;
                }
                $where .= ' AND (' . implode(' OR ', $searchParts) . ')';
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
                    base.domicilios,
                    COUNT(*) OVER() AS total_count
                FROM (
                    SELECT
                        fci.co_fat_cidadao_pec,
                        {$nomeExpr}      AS nome,
                        {$cpfExpr}       AS cpf,
                        {$cnsExpr}       AS cns,
                        {$dtNascExpr}    AS data_nascimento,
                        {$idadeExpr}     AS idade,
                        TO_CHAR(TO_DATE(fci.co_dim_tempo::text, 'YYYYMMDD'), 'DD/MM/YYYY') AS data_atualizacao,
                        de.nu_ine,
                        de.no_equipe,
                        dp.co_seq_dim_profissional AS profissional_id,
                        dp.no_profissional         AS agente,
                        dp.nu_cns                  AS cns_agente,
                        {$gestExpr}      AS st_gestante,
                        {$hasExpr}       AS st_has,
                        {$dmExpr}        AS st_dm,
                        {$idosoExpr}     AS st_idoso,
                        {$domiciliosExpr} AS domicilios,
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
        $ine = $request->query('ine') ?: null;
        $this->assertIneAllowed($request, $ine);
        $allowedInes = $this->resolveAllowedInes($request);

        try {
            $db = $this->db();
        } catch (\Throwable) {
            return response()->json(['error' => 'Não foi possível conectar ao e-SUS.'], 503);
        }

        try {
            $outerWhere  = '';
            $outerParams = [];

            [$ineWhere, $ineBindings] = $this->buildIneWhere($ine, $allowedInes, 'base.nu_ine');
            if ($ineWhere) {
                $outerWhere   .= " AND {$ineWhere}";
                $outerParams   = array_merge($outerParams, $ineBindings);
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
