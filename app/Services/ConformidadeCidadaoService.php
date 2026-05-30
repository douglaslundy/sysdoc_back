<?php

namespace App\Services;

use App\Models\Addresses;
use App\Models\Client;
use App\Models\SincronizacaoCidadao;
use App\Models\SincronizacaoItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;

class ConformidadeCidadaoService
{
    private ?\Illuminate\Database\ConnectionInterface $esusConn = null;
    private array $colCache = [];

    // ─── Conexão e-SUS ────────────────────────────────────────────────────────

    private function esus(): \Illuminate\Database\ConnectionInterface
    {
        if ($this->esusConn !== null) return $this->esusConn;

        $row = Cache::get('aps_db_config');
        if ($row === null) {
            try { $row = DB::table('monitor_aps_configs')->first(); } catch (\Throwable) { $row = null; }
        }

        $host     = $row?->aps_db_host     ?? env('APS_DB_HOST', '');
        $port     = (int) ($row?->aps_db_port ?? env('APS_DB_PORT', 5432));
        $database = $row?->aps_db_database ?? env('APS_DB_DATABASE', 'esus');
        $username = $row?->aps_db_username ?? env('APS_DB_USERNAME', '');
        $password = $row?->aps_db_password ?? env('APS_DB_PASSWORD', '');

        if ($password) {
            try { $password = decrypt($password); } catch (\Throwable) {}
        }

        if ($host) {
            $socket = @fsockopen($host, $port, $errno, $errstr, 3.0);
            if ($socket === false) {
                throw new \RuntimeException("eSUS PEC inacessível ({$host}:{$port}): {$errstr}");
            }
            fclose($socket);
        }

        config(['database.connections.pgsql_conformidade' => [
            'driver'   => 'pgsql',
            'host'     => $host,
            'port'     => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
            'sslmode'  => 'prefer',
            'options'  => [PDO::ATTR_TIMEOUT => 10],
        ]]);

        $this->esusConn = DB::connection('pgsql_conformidade');
        $this->esusConn->statement("SET statement_timeout = '120s'");

        return $this->esusConn;
    }

    private function hasCol(string $table, string $col): bool
    {
        $key = "{$table}.{$col}";
        if (isset($this->colCache[$key])) return $this->colCache[$key];

        try {
            $result = $this->esus()->selectOne(
                "SELECT 1 FROM pg_catalog.pg_attribute
                 WHERE attrelid = ?::regclass AND attname = ? AND attnum > 0 AND NOT attisdropped LIMIT 1",
                [$table, $col]
            );
            return $this->colCache[$key] = ($result !== null);
        } catch (\Throwable) {
            return $this->colCache[$key] = false;
        }
    }

    private function firstCol(string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if ($this->hasCol($table, $c)) return $c;
        }
        return null;
    }

    private function hasTable(string $table): bool
    {
        try {
            $r = $this->esus()->selectOne(
                "SELECT 1 FROM pg_catalog.pg_class c
                 JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                 WHERE c.relname = ? AND c.relkind = 'r' AND n.nspname = 'public' LIMIT 1",
                [$table]
            );
            return $r !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    // ─── Resolução de colunas ─────────────────────────────────────────────────

    private function resolveEsusCols(): array
    {
        $hasDom = $this->hasTable('tb_fat_cad_domiciliar');
        $hasPec = $this->hasTable('tb_fat_cidadao_pec');

        return [
            'cpf'         => $this->firstCol('tb_fat_cad_individual', ['nu_cpf', 'nu_cpf_cidadao', 'co_cpf']),
            'cns'         => $this->firstCol('tb_fat_cad_individual', ['nu_cns', 'co_cns']),
            'nome'        => $this->firstCol('tb_fat_cad_individual', ['no_cidadao', 'no_nome']),
            'dt_nasc'     => $this->firstCol('tb_fat_cad_individual', ['dt_nascimento', 'dt_nasc', 'dt_data_nascimento']),
            'st_faleceu'  => $this->firstCol('tb_fat_cad_individual', ['st_faleceu', 'in_falecido', 'st_obito']),
            'dt_obito'    => $this->firstCol('tb_fat_cad_individual', ['dt_obito', 'dt_data_obito']),
            'telefone'    => $this->firstCol('tb_fat_cad_individual', ['nu_telefone_celular', 'nu_telefone_residencial', 'nu_contato']),
            'atualizado'  => $this->firstCol('tb_fat_cad_individual', ['dh_ultima_atualizacao', 'dt_ultima_atualizacao', 'updated_at']),
            'dom_fk'      => $hasDom ? $this->firstCol('tb_fat_cad_individual', ['co_fat_cad_domiciliar', 'co_cad_domiciliar']) : null,
            'dom_pk'      => $hasDom ? $this->firstCol('tb_fat_cad_domiciliar', ['co_seq_fat_cad_domiciliar', 'co_fat_cad_domiciliar']) : null,
            'logradouro'  => $hasDom ? $this->firstCol('tb_fat_cad_domiciliar', ['ds_logradouro', 'no_logradouro', 'ds_endereco']) : null,
            'numero'      => $hasDom ? $this->firstCol('tb_fat_cad_domiciliar', ['nu_numero', 'ds_numero']) : null,
            'complemento' => $hasDom ? $this->firstCol('tb_fat_cad_domiciliar', ['ds_complemento']) : null,
            'cep'         => $hasDom ? $this->firstCol('tb_fat_cad_domiciliar', ['nu_cep', 'co_cep', 'ds_cep']) : null,
            'bairro'      => $hasDom ? $this->firstCol('tb_fat_cad_domiciliar', ['ds_bairro', 'no_bairro']) : null,
            'municipio'   => $hasDom ? $this->firstCol('tb_fat_cad_domiciliar', ['no_municipio', 'ds_municipio']) : null,
            'hasDom'      => $hasDom,
            // tb_fat_cidadao_pec: fonte do nome real e fallback para CPF/CNS
            'pec_fk'      => $hasPec ? $this->firstCol('tb_fat_cad_individual', ['co_fat_cidadao_pec', 'co_seq_fat_cidadao_pec', 'co_cidadao_pec']) : null,
            'pec_pk'      => $hasPec ? $this->firstCol('tb_fat_cidadao_pec', ['co_seq_fat_cidadao_pec']) : null,
            'pec_nome'    => $hasPec ? $this->firstCol('tb_fat_cidadao_pec', ['no_cidadao', 'no_nome_cidadao']) : null,
            'pec_cpf'     => $hasPec ? $this->firstCol('tb_fat_cidadao_pec', ['nu_cpf_cidadao', 'nu_cpf', 'co_cpf']) : null,
            'pec_cns'     => $hasPec ? $this->firstCol('tb_fat_cidadao_pec', ['nu_cns']) : null,
            'raca_cor_fk'  => $this->firstCol('tb_fat_cad_individual', ['co_dim_raca_cor']),
            'raca_cor_pk'  => ($hasRaca = $this->hasTable('tb_dim_raca_cor'))
                ? $this->firstCol('tb_dim_raca_cor', ['co_seq_dim_raca_cor'])
                : null,
            'raca_cor_ds'  => $hasRaca
                ? $this->firstCol('tb_dim_raca_cor', ['ds_raca_cor'])
                : null,
        ];
    }

    // ─── Contagem total eSUS ──────────────────────────────────────────────────

    private function countEsus(array $cols): int
    {
        $cpfExpr = $cols['cpf'] ? 'fci.' . $this->quoteCol($cols['cpf']) : 'NULL';
        $cnsExpr = $cols['cns'] ? 'fci.' . $this->quoteCol($cols['cns']) : 'NULL';

        // Conta cidadãos únicos: tb_fat_cad_individual tem múltiplas fichas por cidadão
        // (diferentes equipes, períodos distintos). Usar DISTINCT para contar pessoas, não fichas.
        try {
            $result = $this->esus()->selectOne(
                "SELECT COUNT(DISTINCT CASE
                    WHEN {$cpfExpr} IS NOT NULL THEN 'c:' || {$cpfExpr}
                    ELSE 'n:' || {$cnsExpr}
                 END) AS total
                 FROM tb_fat_cad_individual fci
                 WHERE ({$cpfExpr} IS NOT NULL OR {$cnsExpr} IS NOT NULL)"
            );
            return (int) ($result->total ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    // ─── analisar() ───────────────────────────────────────────────────────────

    public function analisar(SincronizacaoCidadao $sync): void
    {
        try {
            $sync->update(['status' => 'analyzing']);

            $cols = $this->resolveEsusCols();

            // Conta total de registros eSUS para cálculo de progresso
            $totalPrevisto = $this->countEsus($cols);

            // Carrega todos os clients do Sysdoc com endereços
            $sysdocClients = Client::with('addresses')->get();
            $sync->update([
                'total_sysdoc'        => $sysdocClients->count(),
                'total_esus_previsto' => $totalPrevisto,
            ]);

            // Indexa por CPF e CNS para lookup O(1)
            $byCpf = [];
            $byCns = [];
            foreach ($sysdocClients as $c) {
                if ($c->cpf) $byCpf[preg_replace('/\D/', '', $c->cpf)] = $c;
                if ($c->cns) $byCns[preg_replace('/\D/', '', $c->cns)] = $c;
            }

            $totalEsus    = 0;
            $criados      = 0;
            $atualizados  = 0;
            $obitos       = 0;
            $semAlteracao = 0;
            $itens        = [];
            $lastProgress = 0;
            $seenKeys     = []; // deduplicação: tb_fat_cad_individual tem N fichas por cidadão

            $this->chunkEsus($cols, function (array $rows) use (
                &$byCpf, &$byCns, &$totalEsus, &$criados, &$atualizados,
                &$obitos, &$semAlteracao, &$itens, &$lastProgress, &$seenKeys, $sync, $cols
            ) {
                foreach ($rows as $row) {
                    $cpfRaw = $row['cpf'] ? preg_replace('/\D/', '', $row['cpf']) : null;
                    $cnsRaw = $row['cns'] ? preg_replace('/\D/', '', $row['cns']) : null;

                    if (!$cpfRaw && !$cnsRaw) {
                        continue;
                    }

                    // Pula fichas duplicadas do mesmo cidadão (mesmo CPF ou CNS já processado)
                    $key = $cpfRaw ? "c:{$cpfRaw}" : "n:{$cnsRaw}";
                    if (isset($seenKeys[$key])) {
                        continue;
                    }
                    $seenKeys[$key] = true;
                    $totalEsus++;

                    // Match: CPF → CNS fallback
                    $client = null;
                    if ($cpfRaw && isset($byCpf[$cpfRaw])) $client = $byCpf[$cpfRaw];
                    elseif ($cnsRaw && isset($byCns[$cnsRaw])) $client = $byCns[$cnsRaw];

                    if ($client === null) {
                        // Sem match → criar (somente se não for óbito)
                        $isFalecido = $this->isTruthy($row['st_faleceu'] ?? null);
                        if ($isFalecido) { $semAlteracao++; continue; }
                        if (!$row['nome']) { $semAlteracao++; continue; }
                        if (!$row['dt_nasc']) { $semAlteracao++; continue; }

                        $payload = $this->buildCreatePayload($row, $cols);
                        $itens[] = [
                            'sincronizacao_id' => $sync->id,
                            'acao'      => 'criar',
                            'cpf'       => $row['cpf'] ? substr($row['cpf'], 0, 18) : null,
                            'cns'       => $row['cns'] ? substr($row['cns'], 0, 15) : null,
                            'nome_esus' => substr($row['nome'] ?? '', 0, 150),
                            'client_id' => null,
                            'payload'   => json_encode($payload),
                            'aplicado'  => false,
                        ];
                        $criados++;
                    } else {
                        // Match encontrado
                        $isFalecido    = $this->isTruthy($row['st_faleceu'] ?? null);
                        $esusUpdated   = $row['atualizado'] ? Carbon::parse($row['atualizado']) : null;
                        $sysdocUpdated = $client->updated_at;

                        // Só processa se e-SUS for mais recente (ou não tiver timestamp)
                        if ($esusUpdated && $sysdocUpdated && $esusUpdated->lte($sysdocUpdated)) {
                            $semAlteracao++;
                            continue;
                        }

                        if ($isFalecido && $client->active) {
                            $dtObito = $row['dt_obito'] ?? null;
                            $itens[] = [
                                'sincronizacao_id' => $sync->id,
                                'acao'      => 'obito',
                                'cpf'       => $row['cpf'] ? substr($row['cpf'], 0, 18) : null,
                                'cns'       => $row['cns'] ? substr($row['cns'], 0, 15) : null,
                                'nome_esus' => substr($row['nome'] ?? $client->name, 0, 150),
                                'client_id' => $client->id,
                                'payload'   => json_encode(['dt_obito' => $dtObito]),
                                'aplicado'  => false,
                            ];
                            $obitos++;
                        } else {
                            $diff = $this->buildDiffPayload($client, $row, $cols);
                            if (empty($diff)) { $semAlteracao++; continue; }
                            $itens[] = [
                                'sincronizacao_id' => $sync->id,
                                'acao'      => 'atualizar',
                                'cpf'       => $row['cpf'] ? substr($row['cpf'], 0, 18) : null,
                                'cns'       => $row['cns'] ? substr($row['cns'], 0, 15) : null,
                                'nome_esus' => substr($row['nome'] ?? $client->name, 0, 150),
                                'client_id' => $client->id,
                                'payload'   => json_encode($diff),
                                'aplicado'  => false,
                            ];
                            $atualizados++;
                        }

                    }

                    if (count($itens) >= 200) {
                        SincronizacaoItem::insert($itens);
                        $itens = [];
                    }
                }

                // Atualiza progresso a cada 2000 registros para feedback em tempo real
                if ($totalEsus - $lastProgress >= 2000) {
                    $sync->update(['total_esus' => $totalEsus]);
                    $lastProgress = $totalEsus;
                }
            });

            if (!empty($itens)) {
                SincronizacaoItem::insert($itens);
            }

            $sync->update([
                'status'                => 'preview_ready',
                'total_esus'            => $totalEsus,
                'preview_criados'       => $criados,
                'preview_atualizados'   => $atualizados,
                'preview_obitos'        => $obitos,
                'preview_sem_alteracao' => $semAlteracao,
                'analisado_em'          => now(),
            ]);

        } catch (\Throwable $e) {
            Log::error('[ConformidadeCidadao] analisar() falhou', ['error' => $e->getMessage()]);
            $sync->update(['status' => 'failed', 'erro_mensagem' => $e->getMessage()]);
            throw $e;
        }
    }

    private function quoteCol(?string $col): string
    {
        if ($col === null) return 'NULL';
        return '"' . str_replace('"', '', $col) . '"';
    }

    private function chunkEsus(array $cols, callable $callback): void
    {
        $fciCpf = $cols['cpf'] ? 'fci.' . $this->quoteCol($cols['cpf']) : null;
        $pecCpf = ($cols['pec_fk'] && $cols['pec_cpf']) ? 'pec.' . $this->quoteCol($cols['pec_cpf']) : null;
        $cpfExpr = match (true) {
            $fciCpf && $pecCpf => "COALESCE({$fciCpf}, {$pecCpf})",
            (bool) $fciCpf     => $fciCpf,
            (bool) $pecCpf     => $pecCpf,
            default            => 'NULL',
        };

        $fciCns = $cols['cns'] ? 'fci.' . $this->quoteCol($cols['cns']) : null;
        $pecCns = ($cols['pec_fk'] && $cols['pec_cns']) ? 'pec.' . $this->quoteCol($cols['pec_cns']) : null;
        $cnsExpr = match (true) {
            $fciCns && $pecCns => "COALESCE({$fciCns}, {$pecCns})",
            (bool) $fciCns     => $fciCns,
            (bool) $pecCns     => $pecCns,
            default            => 'NULL',
        };
        $dtNascExpr = $cols['dt_nasc']    ? 'fci.' . $this->quoteCol($cols['dt_nasc'])    : 'NULL';
        $falExpr    = $cols['st_faleceu'] ? 'fci.' . $this->quoteCol($cols['st_faleceu']) : 'NULL';
        $obitoExpr  = $cols['dt_obito']   ? 'fci.' . $this->quoteCol($cols['dt_obito'])   : 'NULL';
        $telExpr    = $cols['telefone']   ? 'fci.' . $this->quoteCol($cols['telefone'])   : 'NULL';
        $updExpr    = $cols['atualizado'] ? 'fci.' . $this->quoteCol($cols['atualizado']) : 'NULL';

        // Nome: tb_fat_cad_individual armazena SHA-256 por LGPD no PEC 5+.
        // Busca o nome real em tb_fat_cidadao_pec; filtra qualquer hash hex em ambas as fontes.
        $hashFilter = "~* '^[0-9a-f]{32,}$' OR %s ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$'";
        $pecJoin  = '';
        $nomeExpr = 'NULL';

        $racaJoin = '';
        $racaExpr = 'NULL';
        if ($cols['raca_cor_fk'] && $cols['raca_cor_pk'] && $cols['raca_cor_ds']) {
            $racaPk   = $this->quoteCol($cols['raca_cor_pk']);
            $racaFk   = $this->quoteCol($cols['raca_cor_fk']);
            $racaJoin = "LEFT JOIN tb_dim_raca_cor rc ON rc.{$racaPk} = fci.{$racaFk}";
            $racaExpr = 'rc.' . $this->quoteCol($cols['raca_cor_ds']);
        }

        $fciNomeCol = $cols['nome'] ? 'fci.' . $this->quoteCol($cols['nome']) : null;

        if ($cols['pec_fk'] && $cols['pec_pk'] && $cols['pec_nome']) {
            $pecPk   = $this->quoteCol($cols['pec_pk']);
            $pecFk   = $this->quoteCol($cols['pec_fk']);
            $pecNome = 'pec.' . $this->quoteCol($cols['pec_nome']);
            $pecJoin = "LEFT JOIN tb_fat_cidadao_pec pec ON pec.{$pecPk} = fci.{$pecFk}";

            $filterPec = "NULLIF(CASE WHEN {$pecNome} ~* '^[0-9a-f]{32,}$' OR {$pecNome} ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$' THEN NULL ELSE {$pecNome} END, '')";

            if ($fciNomeCol) {
                $filterFci = "NULLIF(CASE WHEN {$fciNomeCol} ~* '^[0-9a-f]{32,}$' OR {$fciNomeCol} ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$' THEN NULL ELSE {$fciNomeCol} END, '')";
                $nomeExpr  = "COALESCE({$filterPec}, {$filterFci})";
            } else {
                $nomeExpr = $filterPec;
            }
        } elseif ($fciNomeCol) {
            $nomeExpr = "NULLIF(CASE WHEN {$fciNomeCol} ~* '^[0-9a-f]{32,}$' OR {$fciNomeCol} ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$' THEN NULL ELSE {$fciNomeCol} END, '')";
        }

        $domJoin = '';
        $logExpr = 'NULL'; $numExpr = 'NULL'; $compExpr = 'NULL';
        $cepExpr = 'NULL'; $baiExpr = 'NULL'; $munExpr  = 'NULL';

        if ($cols['hasDom'] && $cols['dom_fk'] && $cols['dom_pk']) {
            $domPk   = $this->quoteCol($cols['dom_pk']);
            $domFk   = $this->quoteCol($cols['dom_fk']);
            $domJoin = "LEFT JOIN tb_fat_cad_domiciliar dom ON dom.{$domPk} = fci.{$domFk}";
            if ($cols['logradouro'])  $logExpr  = 'dom.' . $this->quoteCol($cols['logradouro']);
            if ($cols['numero'])      $numExpr  = 'dom.' . $this->quoteCol($cols['numero']);
            if ($cols['complemento']) $compExpr = 'dom.' . $this->quoteCol($cols['complemento']);
            if ($cols['cep'])         $cepExpr  = 'dom.' . $this->quoteCol($cols['cep']);
            if ($cols['bairro'])      $baiExpr  = 'dom.' . $this->quoteCol($cols['bairro']);
            if ($cols['municipio'])   $munExpr  = 'dom.' . $this->quoteCol($cols['municipio']);
        }

        $pkCol = $this->firstCol('tb_fat_cad_individual', [
            'co_seq_fat_cad_individual',
            'co_fat_cad_individual',
            'id',
        ]);

        $chunkSize = 500;

        if ($pkCol) {
            // Cursor-based pagination: evita lentidão de OFFSET em tabelas grandes
            $pkExpr    = 'fci.' . $this->quoteCol($pkCol);
            $lastCursor = null;

            do {
                $cursorWhere = $lastCursor !== null ? "AND {$pkExpr} > ?" : '';
                $params      = $lastCursor !== null ? [$lastCursor] : [];

                $sql = "
                    SELECT
                        {$pkExpr}     AS _cursor,
                        {$cpfExpr}    AS cpf,
                        {$cnsExpr}    AS cns,
                        {$nomeExpr}   AS nome,
                        {$dtNascExpr} AS dt_nasc,
                        {$falExpr}    AS st_faleceu,
                        {$obitoExpr}  AS dt_obito,
                        {$telExpr}    AS telefone,
                        {$updExpr}    AS atualizado,
                        {$logExpr}    AS logradouro,
                        {$numExpr}    AS numero,
                        {$compExpr}   AS complemento,
                        {$cepExpr}    AS cep,
                        {$baiExpr}    AS bairro,
                        {$munExpr}    AS municipio,
                        {$racaExpr}   AS raca_cor
                    FROM tb_fat_cad_individual fci
                    {$domJoin}
                    {$pecJoin}
                    {$racaJoin}
                    WHERE ({$cpfExpr} IS NOT NULL OR {$cnsExpr} IS NOT NULL) {$cursorWhere}
                    ORDER BY {$pkExpr} ASC
                    LIMIT {$chunkSize}
                ";

                $rows = $this->esus()->select($sql, $params);
                if (empty($rows)) break;

                $rowsArray  = array_map(fn($r) => (array) $r, $rows);
                $lastCursor = end($rowsArray)['_cursor'];

                $cleanRows = array_map(function ($r) { unset($r['_cursor']); return $r; }, $rowsArray);
                $callback($cleanRows);

            } while (count($rows) === $chunkSize);
        } else {
            // Fallback OFFSET quando não há PK conhecida
            $offset = 0;

            do {
                $sql = "
                    SELECT
                        {$cpfExpr}    AS cpf,
                        {$cnsExpr}    AS cns,
                        {$nomeExpr}   AS nome,
                        {$dtNascExpr} AS dt_nasc,
                        {$falExpr}    AS st_faleceu,
                        {$obitoExpr}  AS dt_obito,
                        {$telExpr}    AS telefone,
                        {$updExpr}    AS atualizado,
                        {$logExpr}    AS logradouro,
                        {$numExpr}    AS numero,
                        {$compExpr}   AS complemento,
                        {$cepExpr}    AS cep,
                        {$baiExpr}    AS bairro,
                        {$munExpr}    AS municipio,
                        {$racaExpr}   AS raca_cor
                    FROM tb_fat_cad_individual fci
                    {$domJoin}
                    {$pecJoin}
                    {$racaJoin}
                    WHERE ({$cpfExpr} IS NOT NULL OR {$cnsExpr} IS NOT NULL)
                    ORDER BY 1
                    LIMIT {$chunkSize} OFFSET {$offset}
                ";

                $rows = $this->esus()->select($sql);
                if (empty($rows)) break;

                $callback(array_map(fn($r) => (array) $r, $rows));
                $offset += $chunkSize;

            } while (count($rows) === $chunkSize);
        }
    }

    private function isTruthy(mixed $val): bool
    {
        if ($val === null) return false;
        if (is_bool($val)) return $val;
        return in_array(strtolower((string) $val), ['t', 'true', '1', 'yes', 's', 'sim'], true);
    }

    private function buildCreatePayload(array $row, array $cols): array
    {
        $payload = [
            'name'      => $row['nome'],
            'born_date' => $row['dt_nasc'],
            'phone'     => $row['telefone'] ?? null,
            'sexo'      => 'INDETERMINATE',
            'raca_cor'  => $row['raca_cor'] ?? null,
        ];

        if ($row['logradouro'] ?? null) {
            $payload['address'] = [
                'street'     => $row['logradouro'],
                'number'     => $row['numero']      ?? '',
                'complement' => $row['complemento'] ?? null,
                'zip_code'   => $row['cep']         ?? null,
                'district'   => $row['bairro']      ?? '',
                'city'       => $row['municipio']   ?? '',
            ];
        }

        return $payload;
    }

    private function buildDiffPayload(Client $client, array $row, array $cols): array
    {
        $diff = [];

        if ($row['nome'] && $row['nome'] !== $client->name) {
            $diff['nome'] = ['de' => $client->name, 'para' => $row['nome']];
        }

        if ($row['dt_nasc']) {
            $esusDate   = Carbon::parse($row['dt_nasc'])->format('Y-m-d');
            $sysdocDate = $client->born_date ? Carbon::parse($client->born_date)->format('Y-m-d') : null;
            if ($esusDate !== $sysdocDate) {
                $diff['born_date'] = ['de' => $sysdocDate, 'para' => $esusDate];
            }
        }

        if (($row['telefone'] ?? null) && $row['telefone'] !== $client->phone) {
            $diff['phone'] = ['de' => $client->phone, 'para' => $row['telefone']];
        }

        if (($row['raca_cor'] ?? null) && $row['raca_cor'] !== $client->raca_cor) {
            $diff['raca_cor'] = ['de' => $client->raca_cor, 'para' => $row['raca_cor']];
        }

        $addr = $client->addresses;
        if ($row['logradouro'] ?? null) {
            $addrDiff = [];
            if (($row['logradouro'] ?? null) && $row['logradouro'] !== $addr?->street)
                $addrDiff['street'] = ['de' => $addr?->street, 'para' => $row['logradouro']];
            if (($row['numero'] ?? null) && $row['numero'] !== $addr?->number)
                $addrDiff['number'] = ['de' => $addr?->number, 'para' => $row['numero']];
            if (($row['bairro'] ?? null) && $row['bairro'] !== $addr?->district)
                $addrDiff['district'] = ['de' => $addr?->district, 'para' => $row['bairro']];
            if (($row['cep'] ?? null) && $row['cep'] !== $addr?->zip_code)
                $addrDiff['zip_code'] = ['de' => $addr?->zip_code, 'para' => $row['cep']];
            if (($row['complemento'] ?? null) && $row['complemento'] !== $addr?->complement)
                $addrDiff['complement'] = ['de' => $addr?->complement, 'para' => $row['complemento']];
            if (($row['municipio'] ?? null) && $row['municipio'] !== $addr?->city)
                $addrDiff['city'] = ['de' => $addr?->city, 'para' => $row['municipio']];
            if (!empty($addrDiff)) $diff['address'] = $addrDiff;
        }

        return $diff;
    }

    // ─── aplicar() ────────────────────────────────────────────────────────────

    public function aplicar(SincronizacaoCidadao $sync): void
    {
        try {
            $sync->update(['status' => 'applying']);

            $criados = $atualizados = $obitos = $erros = 0;

            $sync->itens()->where('aplicado', false)->chunkById(100, function ($chunk) use (
                &$criados, &$atualizados, &$obitos, &$erros
            ) {
                DB::transaction(function () use ($chunk, &$criados, &$atualizados, &$obitos, &$erros) {
                    foreach ($chunk as $item) {
                        try {
                            match ($item->acao) {
                                'criar'     => $this->aplicarCriar($item) && $criados++,
                                'atualizar' => $this->aplicarAtualizar($item) && $atualizados++,
                                'obito'     => $this->aplicarObito($item) && $obitos++,
                            };
                            $item->update(['aplicado' => true]);
                        } catch (\Throwable $e) {
                            Log::warning('[ConformidadeCidadao] Item falhou', ['id' => $item->id, 'error' => $e->getMessage()]);
                            $item->update(['erro' => substr($e->getMessage(), 0, 255)]);
                            $erros++;
                        }
                    }
                });
            });

            $counts = $sync->itens()
                ->selectRaw('acao, COUNT(*) as n')
                ->where('aplicado', true)
                ->groupBy('acao')
                ->pluck('n', 'acao');

            $errosCount = $sync->itens()->whereNotNull('erro')->count();

            $sync->update([
                'status'             => 'completed',
                'result_criados'     => $counts['criar']     ?? 0,
                'result_atualizados' => $counts['atualizar'] ?? 0,
                'result_obitos'      => $counts['obito']     ?? 0,
                'result_erros'       => $errosCount,
                'aplicado_em'        => now(),
            ]);

        } catch (\Throwable $e) {
            Log::error('[ConformidadeCidadao] aplicar() falhou', ['error' => $e->getMessage()]);
            $sync->update(['status' => 'failed', 'erro_mensagem' => $e->getMessage()]);
            throw $e;
        }
    }

    private function aplicarCriar(SincronizacaoItem $item): bool
    {
        $payload = $item->payload;

        $client = Client::create([
            'name'      => $payload['name'],
            'cpf'       => $item->cpf,
            'cns'       => $item->cns,
            'born_date' => $payload['born_date'],
            'phone'     => $payload['phone'] ?? null,
            'sexo'      => $payload['sexo'] ?? 'INDETERMINATE',
            'raca_cor'  => $payload['raca_cor'] ?? null,
            'active'    => true,
        ]);

        if (!empty($payload['address']) && !empty($payload['address']['street'])) {
            Addresses::create([
                'id_client'  => $client->id,
                'street'     => $payload['address']['street'],
                'number'     => $payload['address']['number']     ?? '',
                'complement' => $payload['address']['complement'] ?? null,
                'zip_code'   => $payload['address']['zip_code']   ?? null,
                'district'   => $payload['address']['district']   ?? '',
                'city'       => $payload['address']['city']       ?? '',
                'active'     => true,
            ]);
        }

        return true;
    }

    private function aplicarAtualizar(SincronizacaoItem $item): bool
    {
        $client  = Client::findOrFail($item->client_id);
        $payload = $item->payload;

        $clientData = [];
        if (isset($payload['nome']))      $clientData['name']      = $payload['nome']['para'];
        if (isset($payload['born_date'])) $clientData['born_date'] = $payload['born_date']['para'];
        if (isset($payload['phone']))     $clientData['phone']     = $payload['phone']['para'];
        if (isset($payload['raca_cor'])) $clientData['raca_cor'] = $payload['raca_cor']['para'];

        if (!empty($clientData)) {
            $client->update($clientData);
        }

        if (isset($payload['address'])) {
            $addrData = [];
            foreach ($payload['address'] as $field => $change) {
                $addrData[$field] = $change['para'];
            }
            if ($client->addresses) {
                $client->addresses->update($addrData);
            } else {
                Addresses::create(array_merge($addrData, ['id_client' => $client->id, 'active' => true]));
            }
        }

        return true;
    }

    private function aplicarObito(SincronizacaoItem $item): bool
    {
        $client  = Client::findOrFail($item->client_id);

        if (!$client->active) {
            return true; // já inativo, nada a fazer
        }

        $payload = $item->payload;

        $dtObito     = $payload['dt_obito'] ?? null;
        $dtFormatada = $dtObito
            ? Carbon::parse($dtObito)->format('d/m/Y')
            : null;

        $obsTexto = $dtFormatada
            ? "Baixa automática devido ao óbito ocorrido em {$dtFormatada}"
            : "Baixa automática devido ao óbito (data não informada)";

        $client->update([
            'active'      => false,
            'st_falecido' => true,
            'data_obito'  => $dtObito ? Carbon::parse($dtObito)->format('Y-m-d') : null,
        ]);

        // Inativa todas as filas abertas do cliente
        $client->queue()
            ->where('done', false)
            ->each(function ($queue) use ($obsTexto) {
                $novaObs = $queue->obs
                    ? $queue->obs . ' | ' . $obsTexto
                    : $obsTexto;
                $queue->update([
                    'done'             => true,
                    'date_of_realized' => now()->toDateString(),
                    'obs'              => substr($novaObs, 0, 200),
                ]);
            });

        return true;
    }
}
