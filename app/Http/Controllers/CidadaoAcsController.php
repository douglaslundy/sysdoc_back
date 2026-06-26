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
        $fciInativaCol = $this->firstExistingColumn('tb_fat_cad_individual',
            ['st_ficha_inativa', 'in_ficha_inativa']);
        $fciAtivaCol = $this->firstExistingColumn('tb_fat_cad_individual',
            ['st_ativo', 'in_ativo']);
        $stGestCol  = $this->firstExistingColumn('tb_fat_cad_individual',
            ['st_gestante', 'st_em_gestacao', 'in_gestante']);
        $dtObitoCol = $this->firstExistingColumn('tb_fat_cad_individual',
            ['dt_obito', 'dt_data_obito', 'dt_falecimento', 'data_obito']);
        $stObitoCol = $this->firstExistingColumn('tb_fat_cad_individual',
            ['st_obito', 'st_falecido', 'in_obito', 'in_falecido']);
        $deValidaCol = $this->firstExistingColumn('tb_dim_equipe',
            ['st_registro_valido', 'in_registro_valido']);
        $deAtivaCol = $this->firstExistingColumn('tb_dim_equipe',
            ['st_ativo', 'in_ativo']);
        $dpValidaCol = $this->firstExistingColumn('tb_dim_profissional',
            ['st_registro_valido', 'in_registro_valido']);
        $dpAtivaCol = $this->firstExistingColumn('tb_dim_profissional',
            ['st_ativo', 'in_ativo']);

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
            'fciInativaCol' => $fciInativaCol,
            'fciAtivaCol' => $fciAtivaCol,
            'stGestCol' => $stGestCol,
            'dtObitoCol'=> $dtObitoCol,
            'stObitoCol'=> $stObitoCol,
            'deValidaCol' => $deValidaCol,
            'deAtivaCol' => $deAtivaCol,
            'dpValidaCol' => $dpValidaCol,
            'dpAtivaCol' => $dpAtivaCol,
            'hasPec'    => $this->hasTable('tb_fat_cidadao_pec'),
            'hasCidadao'=> $this->hasTable('tb_cidadao'),
            'hasPecNasc'=> ($dtNascCol === null) && $this->hasTable('tb_fat_cidadao_pec'),
        ];
    }

    private function truthySql(string $expr): string
    {
        return "LOWER(TRIM(COALESCE({$expr}::text, '0'))) IN ('1','t','true','s','sim','y','yes')";
    }

    private function statusFilter(?string $expr, bool $expectedTruthy = true): ?string
    {
        if (!$expr) {
            return null;
        }

        $truthyExpr = $this->truthySql($expr);

        return $expectedTruthy ? $truthyExpr : "NOT ({$truthyExpr})";
    }

    /**
     * GET /monitor-aps/cidadaos?ine=&profissional_id=&agente_cns=&busca=&page=&per_page=
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'ine'              => 'nullable|string',
            'profissional_id'  => 'nullable|integer',
            'agente'           => 'nullable|string|max:255',
            'agente_cns'       => 'nullable|string|max:255',
            'condicao'         => 'nullable|string|in:gestante,has,dm,idoso,obito',
            'busca'            => 'nullable|string|min:3|max:100',
            'multi_domicilio'  => 'nullable|boolean',
            'sort'             => 'nullable|string|in:nome,idade',
            'dir'              => 'nullable|string|in:asc,desc',
            'page'             => 'nullable|integer|min:1',
            'per_page'         => 'nullable|integer|min:10|max:200',
        ]);

        $ine            = $request->query('ine') ?: null;
        $this->assertIneAllowed($request, $ine);
        $allowedInes    = $this->resolveAllowedInes($request);
        $profissionalId = $request->query('profissional_id');
        $agente         = $request->query('agente');
        $agenteCns      = trim((string) $request->query('agente_cns', ''));
        $condicao       = $request->query('condicao');
        $busca          = $request->query('busca');
        $multiDomicilio = filter_var($request->query('multi_domicilio', false), FILTER_VALIDATE_BOOLEAN);
        $sort           = $request->query('sort', 'nome');
        $dir            = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'DESC' : 'ASC';
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
            $dtObitoCol = $cols['dtObitoCol'];
            $stObitoCol = $cols['stObitoCol'];
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
            $pecDtObitoCol = $pecJoin ? $this->firstExistingColumn('tb_fat_cidadao_pec', ['dt_obito', 'dt_data_obito', 'dt_falecimento', 'data_obito']) : null;
            $pecStObitoCol = $pecJoin ? $this->firstExistingColumn('tb_fat_cidadao_pec', ['st_obito', 'st_falecido', 'in_obito', 'in_falecido']) : null;
            $cidDtObitoCol = $cidadaoJoin ? $this->firstExistingColumn('tb_cidadao', ['dt_obito', 'dt_data_obito', 'dt_falecimento', 'data_obito']) : null;
            $cidStObitoCol = $cidadaoJoin ? $this->firstExistingColumn('tb_cidadao', ['st_obito', 'st_falecido', 'in_obito', 'in_falecido']) : null;
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
            $truthyExpr = static fn(string $expr): string =>
                "LOWER(TRIM(COALESCE({$expr}::text, '0'))) IN ('1','t','true','s','sim','y','yes')";
            $stObitoTruthy = $stObitoCol ? $truthyExpr("fci.{$stObitoCol}") : null;
            $pecStObitoTruthy = $pecStObitoCol ? $truthyExpr("pec.{$pecStObitoCol}") : null;
            $cidStObitoTruthy = $cidStObitoCol ? $truthyExpr("cid.{$cidStObitoCol}") : null;

            $obitoChecks = [];
            if ($dtObitoCol) $obitoChecks[] = "fci.{$dtObitoCol} IS NOT NULL";
            if ($stObitoTruthy) $obitoChecks[] = $stObitoTruthy;
            if ($pecDtObitoCol) $obitoChecks[] = "pec.{$pecDtObitoCol} IS NOT NULL";
            if ($pecStObitoTruthy) $obitoChecks[] = $pecStObitoTruthy;
            if ($cidDtObitoCol) $obitoChecks[] = "cid.{$cidDtObitoCol} IS NOT NULL";
            if ($cidStObitoTruthy) $obitoChecks[] = $cidStObitoTruthy;
            $obitoExpr = count($obitoChecks) > 0
                ? "CASE WHEN (" . implode(' OR ', $obitoChecks) . ") THEN 1 ELSE 0 END"
                : '0';

            $dataObitoParts = [];
            if ($dtObitoCol) $dataObitoParts[] = "fci.{$dtObitoCol}";
            if ($pecDtObitoCol) $dataObitoParts[] = "pec.{$pecDtObitoCol}";
            if ($cidDtObitoCol) $dataObitoParts[] = "cid.{$cidDtObitoCol}";
            if (count($dataObitoParts) > 0) {
                $dataObitoExpr = "TO_CHAR(COALESCE(" . implode(', ', $dataObitoParts) . ")::date, 'DD/MM/YYYY')";
            } else {
                $dataObitoExpr = 'NULL::text';
            }

            $fonteObitoWhen = [];
            if ($dtObitoCol) $fonteObitoWhen[] = "WHEN fci.{$dtObitoCol} IS NOT NULL THEN 'fci_dt_obito'";
            if ($stObitoTruthy) $fonteObitoWhen[] = "WHEN {$stObitoTruthy} THEN 'fci_st_obito'";
            if ($pecDtObitoCol) $fonteObitoWhen[] = "WHEN pec.{$pecDtObitoCol} IS NOT NULL THEN 'pec_dt_obito'";
            if ($pecStObitoTruthy) $fonteObitoWhen[] = "WHEN {$pecStObitoTruthy} THEN 'pec_st_obito'";
            if ($cidDtObitoCol) $fonteObitoWhen[] = "WHEN cid.{$cidDtObitoCol} IS NOT NULL THEN 'cid_dt_obito'";
            if ($cidStObitoTruthy) $fonteObitoWhen[] = "WHEN {$cidStObitoTruthy} THEN 'cid_st_obito'";
            $fonteObitoExpr = count($fonteObitoWhen) > 0
                ? ("CASE " . implode(' ', $fonteObitoWhen) . " ELSE NULL END")
                : "NULL::text";

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
                'obito'    => 'st_obito',
            ];

            $whereParts = [];
            if ($filter = $this->statusFilter($cols['fciInativaCol'] ? "fci.{$cols['fciInativaCol']}" : null, false)) {
                $whereParts[] = $filter;
            } elseif ($filter = $this->statusFilter($cols['fciAtivaCol'] ? "fci.{$cols['fciAtivaCol']}" : null, true)) {
                $whereParts[] = $filter;
            }
            if ($filter = $this->statusFilter($cols['deValidaCol'] ? "de.{$cols['deValidaCol']}" : null, true)) {
                $whereParts[] = $filter;
            } elseif ($filter = $this->statusFilter($cols['deAtivaCol'] ? "de.{$cols['deAtivaCol']}" : null, true)) {
                $whereParts[] = $filter;
            }
            $where  = $whereParts ? implode(' AND ', $whereParts) : '1=1';
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
            if ($agenteCns !== '') {
                $outerWhere   .= ' AND TRIM(COALESCE(base.cns_agente::text, \'\')) = ?';
                $outerParams[] = $agenteCns;
            } elseif ($agente) {
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

            $orderBy = 'base.nome ASC';
            if ($sort === 'idade') {
                $orderBy = "base.idade {$dir} NULLS LAST, base.nome ASC";
            } elseif ($sort === 'nome') {
                $orderBy = "base.nome {$dir}";
            }

            $condicaoSql = '';
            if ($condicao) {
                $condicaoSql = 'AND ' . $this->truthySql("base.{$condicaoColumns[$condicao]}");
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
                    base.st_obito,
                    base.data_obito,
                    base.fonte_obito,
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
                        {$obitoExpr}     AS st_obito,
                        {$dataObitoExpr} AS data_obito,
                        {$fonteObitoExpr} AS fonte_obito,
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
                  {$condicaoSql}
                  {$outerWhere}
                ORDER BY {$orderBy}
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
            $cols       = $this->resolveColumns();
            $outerWhere  = '';
            $outerParams = [];

            $agentesWhereParts = [];
            if ($filter = $this->statusFilter($cols['fciInativaCol'] ? "fci.{$cols['fciInativaCol']}" : null, false)) {
                $agentesWhereParts[] = $filter;
            } elseif ($filter = $this->statusFilter($cols['fciAtivaCol'] ? "fci.{$cols['fciAtivaCol']}" : null, true)) {
                $agentesWhereParts[] = $filter;
            }
            if ($filter = $this->statusFilter($cols['deValidaCol'] ? "de.{$cols['deValidaCol']}" : null, true)) {
                $agentesWhereParts[] = $filter;
            } elseif ($filter = $this->statusFilter($cols['deAtivaCol'] ? "de.{$cols['deAtivaCol']}" : null, true)) {
                $agentesWhereParts[] = $filter;
            }
            if ($filter = $this->statusFilter($cols['dpValidaCol'] ? "dp.{$cols['dpValidaCol']}" : null, true)) {
                $agentesWhereParts[] = $filter;
            } elseif ($filter = $this->statusFilter($cols['dpAtivaCol'] ? "dp.{$cols['dpAtivaCol']}" : null, true)) {
                $agentesWhereParts[] = $filter;
            }
            $agentesWhereParts[] = 'dp.no_profissional IS NOT NULL';

            [$ineWhere, $ineBindings] = $this->buildIneWhere($ine, $allowedInes, 'base.nu_ine');
            if ($ineWhere) {
                $outerWhere   .= " AND {$ineWhere}";
                $outerParams   = array_merge($outerParams, $ineBindings);
            }

            $agentesWhere = implode("\n                      AND ", $agentesWhereParts);
            $rows = $db->select("
                SELECT
                    MIN(base.profissional_id) AS id,
                    MIN(base.cns)             AS agente_cns,
                    base.nome                 AS nome
                FROM (
                    SELECT
                        dp.co_seq_dim_profissional AS profissional_id,
                        dp.no_profissional         AS nome,
                        dp.nu_cns                  AS cns,
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
                    WHERE {$agentesWhere}
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
