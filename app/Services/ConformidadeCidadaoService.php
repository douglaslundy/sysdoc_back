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
        $hasDom      = $this->hasTable('tb_fat_cad_domiciliar');
        $hasPec      = $this->hasTable('tb_fat_cidadao_pec');
        $hasFamilia  = $this->hasTable('tb_fat_cad_dom_familia');
        $hasCidadao  = $this->hasTable('tb_cidadao');
        if (!$hasDom) {
            Log::warning('[ConformidadeCidadao] tb_fat_cad_domiciliar nao encontrada - enderecos nao serao sincronizados');
        }

        return [
            // Identificadores — CPF em fci é em texto claro; CNS em fci costuma ser "0"
            'cpf'         => $this->firstCol('tb_fat_cad_individual', ['nu_cpf_cidadao', 'nu_cpf', 'co_cpf']),
            'cns'         => $this->firstCol('tb_fat_cad_individual', ['nu_cns', 'co_cns']),
            'nome'        => $this->firstCol('tb_fat_cad_individual', ['no_cidadao', 'no_nome']),
            'dt_nasc'     => $this->firstCol('tb_fat_cad_individual', ['dt_nascimento', 'dt_nasc', 'dt_data_nascimento']),
            'st_faleceu'  => $this->firstCol('tb_fat_cad_individual', ['st_faleceu', 'in_falecido', 'st_obito']),
            'dt_obito'    => $this->firstCol('tb_fat_cad_individual', ['dt_obito', 'dt_data_obito']),
            // Telefone e sexo em fci estão hasheados/como FK — buscamos de pec/cidadao
            'fci_sexo'    => $this->firstCol('tb_fat_cad_individual', ['co_dim_sexo']),
            'fci_escolaridade_fk' => $this->firstCol('tb_fat_cad_individual', ['co_dim_tipo_escolaridade', 'co_escolaridade']),
            'fci_nacionalidade_fk' => $this->firstCol('tb_fat_cad_individual', ['co_dim_nacionalidade', 'co_nacionalidade']),
            'atualizado'  => $this->firstCol('tb_fat_cad_individual', ['dh_ultima_atualizacao', 'dt_ultima_atualizacao', 'updated_at']),
            // Endereço via tb_fat_cad_dom_familia (fci não tem FK direta para domiciliar)
            'hasDom'         => $hasDom,
            'hasFamilia'     => $hasFamilia,
            'familia_cid_fk' => $hasFamilia ? $this->firstCol('tb_fat_cad_dom_familia', ['co_fat_cidadao_pec', 'co_seq_fat_cidadao_pec']) : null,
            'familia_dom_fk' => $hasFamilia ? $this->firstCol('tb_fat_cad_dom_familia', ['co_fat_cad_domiciliar', 'co_seq_fat_cad_domiciliar']) : null,
            'dom_pk'         => $hasDom ? $this->firstCol('tb_fat_cad_domiciliar', ['co_seq_fat_cad_domiciliar', 'co_fat_cad_domiciliar']) : null,
            'logradouro'     => $hasDom ? $this->firstCol('tb_fat_cad_domiciliar', ['no_logradouro', 'ds_logradouro', 'ds_endereco']) : null,
            'numero'         => $hasDom ? $this->firstCol('tb_fat_cad_domiciliar', ['nu_num_logradouro', 'nu_numero', 'ds_numero']) : null,
            'complemento'    => $hasDom ? $this->firstCol('tb_fat_cad_domiciliar', ['no_complemento', 'ds_complemento']) : null,
            'cep'            => $hasDom ? $this->firstCol('tb_fat_cad_domiciliar', ['nu_cep', 'co_cep', 'ds_cep']) : null,
            'bairro'         => $hasDom ? $this->firstCol('tb_fat_cad_domiciliar', ['no_bairro', 'ds_bairro']) : null,
            'municipio'      => $hasDom ? $this->firstCol('tb_fat_cad_domiciliar', ['no_municipio', 'ds_municipio']) : null,
            // tb_fat_cidadao_pec: nome real, CPF/CNS limpos, sexo, telefone
            'pec_fk'      => $hasPec ? $this->firstCol('tb_fat_cad_individual', ['co_fat_cidadao_pec', 'co_seq_fat_cidadao_pec', 'co_cidadao_pec']) : null,
            'pec_pk'      => $hasPec ? $this->firstCol('tb_fat_cidadao_pec', ['co_seq_fat_cidadao_pec']) : null,
            'pec_nome'    => $hasPec ? $this->firstCol('tb_fat_cidadao_pec', ['no_cidadao', 'no_nome_cidadao']) : null,
            'pec_dt_nasc' => $hasPec ? $this->firstCol('tb_fat_cidadao_pec', ['dt_nascimento', 'dt_nasc', 'dt_data_nascimento', 'dt_nascimento_cidadao']) : null,
            'pec_cpf'     => $hasPec ? $this->firstCol('tb_fat_cidadao_pec', ['nu_cpf_cidadao', 'nu_cpf', 'co_cpf']) : null,
            'pec_cns'     => $hasPec ? $this->firstCol('tb_fat_cidadao_pec', ['nu_cns']) : null,
            'pec_sexo'    => $hasPec ? $this->firstCol('tb_fat_cidadao_pec', ['co_dim_sexo']) : null,
            'pec_telefone'=> $hasPec ? $this->firstCol('tb_fat_cidadao_pec', ['nu_telefone_celular']) : null,
            'pec_cid_fk'  => $hasPec ? $this->firstCol('tb_fat_cidadao_pec', ['co_cidadao']) : null,
            'pec_st_faleceu' => $hasPec ? $this->firstCol('tb_fat_cidadao_pec', ['st_faleceu']) : null,
            'pec_dt_obito' => $hasPec ? $this->firstCol('tb_fat_cidadao_pec', ['dt_obito', 'dt_data_obito']) : null,
            // tb_cidadao: nome da mãe em texto claro (fci.no_nome_mae é SHA-1)
            'hasCidadao'      => $hasCidadao,
            'cid_pk'          => ($hasCidadao && $hasPec) ? $this->firstCol('tb_cidadao', ['co_seq_cidadao']) : null,
            'cid_mae'         => ($hasCidadao && $hasPec) ? $this->firstCol('tb_cidadao', ['no_mae', 'no_mae_filtro', 'no_nome_mae']) : null,
            'cid_dt_nasc'     => ($hasCidadao && $hasPec) ? $this->firstCol('tb_cidadao', ['dt_nascimento', 'dt_nasc', 'dt_data_nascimento', 'dt_nascimento_cidadao']) : null,
            'cid_logradouro'  => ($hasCidadao && $hasPec) ? $this->firstCol('tb_cidadao', ['ds_logradouro', 'no_logradouro']) : null,
            'cid_numero'      => ($hasCidadao && $hasPec) ? $this->firstCol('tb_cidadao', ['nu_numero', 'nu_num_logradouro']) : null,
            'cid_complemento' => ($hasCidadao && $hasPec) ? $this->firstCol('tb_cidadao', ['ds_complemento', 'no_complemento']) : null,
            'cid_cep'         => ($hasCidadao && $hasPec) ? $this->firstCol('tb_cidadao', ['ds_cep', 'nu_cep']) : null,
            'cid_bairro'      => ($hasCidadao && $hasPec) ? $this->firstCol('tb_cidadao', ['no_bairro', 'ds_bairro']) : null,
            'cid_municipio'   => ($hasCidadao && $hasPec) ? $this->firstCol('tb_cidadao', ['no_municipio', 'ds_municipio']) : null,
            'cid_st_faleceu'  => ($hasCidadao && $hasPec) ? $this->firstCol('tb_cidadao', ['st_faleceu']) : null,
            'cid_dt_obito'    => ($hasCidadao && $hasPec) ? $this->firstCol('tb_cidadao', ['dt_obito']) : null,
            'dom_municipio_fk'=> $hasDom ? $this->firstCol('tb_fat_cad_domiciliar', ['co_dim_municipio', 'co_dim_municipio_cidadao']) : null,
            'dim_mun_pk'      => $this->hasTable('tb_dim_municipio') ? $this->firstCol('tb_dim_municipio', ['co_seq_dim_municipio']) : null,
            'dim_mun_nome'    => $this->hasTable('tb_dim_municipio') ? $this->firstCol('tb_dim_municipio', ['no_municipio']) : null,
            // raça/cor
            'raca_cor_fk'  => $this->firstCol('tb_fat_cad_individual', ['co_dim_raca_cor']),
            'raca_cor_pk'  => ($hasRaca = $this->hasTable('tb_dim_raca_cor'))
                ? $this->firstCol('tb_dim_raca_cor', ['co_seq_dim_raca_cor'])
                : null,
            'raca_cor_ds'  => $hasRaca
                ? $this->firstCol('tb_dim_raca_cor', ['ds_raca_cor'])
                : null,
            'esc_dim_pk'   => ($hasEsc = $this->hasTable('tb_dim_tipo_escolaridade'))
                ? $this->firstCol('tb_dim_tipo_escolaridade', ['co_seq_dim_tipo_escolaridade'])
                : null,
            'esc_dim_ds'   => $hasEsc
                ? $this->firstCol('tb_dim_tipo_escolaridade', ['ds_dim_tipo_escolaridade'])
                : null,
            'nac_dim_pk'   => ($hasNac = $this->hasTable('tb_dim_nacionalidade'))
                ? $this->firstCol('tb_dim_nacionalidade', ['co_seq_dim_nacionalidade', 'co_nacionalidade'])
                : null,
            'nac_dim_ds'   => $hasNac
                ? $this->firstCol('tb_dim_nacionalidade', ['ds_nacionalidade'])
                : null,
        ];
    }

    // ─── Contagem total eSUS ──────────────────────────────────────────────────

    private function countEsus(array $cols): int
    {
        $pecJoin = '';
        $fciCpf = $cols['cpf'] ? "NULLIF(NULLIF(trim(fci." . $this->quoteCol($cols['cpf']) . "::text), ''), '0')" : null;
        $pecCpf = null;
        $fciCns = $cols['cns'] ? "NULLIF(NULLIF(trim(fci." . $this->quoteCol($cols['cns']) . "::text), ''), '0')" : null;
        $pecCns = null;

        if ($cols['pec_fk'] && $cols['pec_pk']) {
            $pecJoin = 'LEFT JOIN tb_fat_cidadao_pec pec ON pec.'
                . $this->quoteCol($cols['pec_pk'])
                . ' = fci.'
                . $this->quoteCol($cols['pec_fk']);

            if ($cols['pec_cpf']) {
                $pecCpf = "NULLIF(NULLIF(trim(pec." . $this->quoteCol($cols['pec_cpf']) . "::text), ''), '0')";
            }

            if ($cols['pec_cns']) {
                $pecCns = "NULLIF(NULLIF(trim(pec." . $this->quoteCol($cols['pec_cns']) . "::text), ''), '0')";
            }
        }

        $cpfExpr = match (true) {
            $fciCpf && $pecCpf => "COALESCE({$fciCpf}, {$pecCpf})",
            (bool) $fciCpf     => $fciCpf,
            (bool) $pecCpf     => $pecCpf,
            default            => 'NULL',
        };

        $cnsExpr = match (true) {
            $fciCns && $pecCns => "COALESCE({$fciCns}, {$pecCns})",
            (bool) $fciCns     => $fciCns,
            (bool) $pecCns     => $pecCns,
            default            => 'NULL',
        };

        // Conta cidadãos únicos: tb_fat_cad_individual tem múltiplas fichas por cidadão
        // (diferentes equipes, períodos distintos). Usar DISTINCT para contar pessoas, não fichas.
        try {
            $result = $this->esus()->selectOne(
                "SELECT COUNT(DISTINCT CASE
                    WHEN {$cpfExpr} IS NOT NULL THEN 'c:' || {$cpfExpr}
                    ELSE 'n:' || {$cnsExpr}
                 END) AS total
                 FROM tb_fat_cad_individual fci
                 {$pecJoin}
                 WHERE ({$cpfExpr} IS NOT NULL OR {$cnsExpr} IS NOT NULL)"
            );
            return (int) ($result->total ?? 0);
        } catch (\Throwable $e) {
            Log::warning('[ConformidadeCidadao] Falha ao contar eSUS para progresso', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    // ─── analisar() ───────────────────────────────────────────────────────────

    public function analisar(SincronizacaoCidadao $sync): void
    {
        try {
            $sync->update(['status' => 'analyzing']);

            $cols = $this->resolveEsusCols();
            Log::info('[ConformidadeCidadao] Colunas resolvidas', [
                'st_faleceu' => $cols['st_faleceu'] ?? null,
                'pec_st_faleceu' => $cols['pec_st_faleceu'] ?? null,
                'cid_st_faleceu' => $cols['cid_st_faleceu'] ?? null,
                'dt_obito'   => $cols['dt_obito'] ?? null,
                'cid_dt_obito' => $cols['cid_dt_obito'] ?? null,
                'dt_nasc'    => $cols['dt_nasc'] ?? null,
                'pec_dt_nasc' => $cols['pec_dt_nasc'] ?? null,
                'cid_dt_nasc' => $cols['cid_dt_nasc'] ?? null,
                'logradouro' => $cols['logradouro'] ?? null,
                'municipio'  => $cols['municipio'] ?? null,
                'hasDom'     => $cols['hasDom'] ?? null,
            ]);

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
                    $cpfRaw = $this->normalizeDocument($row['cpf'] ?? null, 11);
                    $cnsRaw = $this->normalizeDocument($row['cns'] ?? null, 15);

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
                        $isFalecido = $this->isObitoRow($row);
                        if ($isFalecido) { $semAlteracao++; continue; }
                        if (!$row['nome']) { $semAlteracao++; continue; }
                        if (!$row['dt_nasc']) { $semAlteracao++; continue; }

                        $payload = $this->buildCreatePayload($row, $cols);
                        $itens[] = [
                            'sincronizacao_id' => $sync->id,
                            'acao'      => 'criar',
                            'cpf'       => $cpfRaw ? substr($cpfRaw, 0, 18) : null,
                            'cns'       => $cnsRaw ? substr($cnsRaw, 0, 15) : null,
                            'nome_esus' => substr($row['nome'] ?? '', 0, 150),
                            'client_id' => null,
                            'payload'   => json_encode($payload),
                            'aplicado'  => false,
                        ];
                        $criados++;
                    } else {
                        // Match encontrado
                        $isFalecido    = $this->isObitoRow($row);
                        $esusUpdated   = $row['atualizado'] ? Carbon::parse($row['atualizado']) : null;
                        $sysdocUpdated = $client->updated_at;
                        $esusMaisRecente = $esusUpdated && (!$sysdocUpdated || $esusUpdated->gt($sysdocUpdated));

                        if ($isFalecido) {
                            if ($this->shouldProcessObito($client, $row)) {
                                $dtObito = $row['dt_obito'] ?? null;
                                $itens[] = [
                                    'sincronizacao_id' => $sync->id,
                                    'acao'      => 'obito',
                                    'cpf'       => $cpfRaw ? substr($cpfRaw, 0, 18) : null,
                                    'cns'       => $cnsRaw ? substr($cnsRaw, 0, 15) : null,
                                    'nome_esus' => substr($row['nome'] ?? $client->name, 0, 150),
                                    'client_id' => $client->id,
                                    'payload'   => json_encode(['dt_obito' => $dtObito]),
                                    'aplicado'  => false,
                                ];
                                $obitos++;
                            } else {
                                $semAlteracao++;
                            }
                            continue;
                        }

                        // Só processa se e-SUS for mais recente (ou não tiver timestamp)
                        $temCampoNuloRelevante = $this->hasMissingCriticalData($client);
                        if (!$temCampoNuloRelevante && !$esusMaisRecente) {
                            $semAlteracao++;
                            continue;
                        }

                        $diff = $this->buildDiffPayload($client, $row, $cols, $esusMaisRecente);
                        if (empty($diff)) { $semAlteracao++; continue; }
                        $itens[] = [
                            'sincronizacao_id' => $sync->id,
                            'acao'      => 'atualizar',
                            'cpf'       => $cpfRaw ? substr($cpfRaw, 0, 18) : null,
                            'cns'       => $cnsRaw ? substr($cnsRaw, 0, 15) : null,
                            'nome_esus' => substr($row['nome'] ?? $client->name, 0, 150),
                            'client_id' => $client->id,
                            'payload'   => json_encode($diff),
                            'aplicado'  => false,
                        ];
                        $atualizados++;

                    }

                    if (count($itens) >= 200) {
                        SincronizacaoItem::insert($itens);
                        $itens = [];
                    }
                }

                // Atualiza progresso a cada 200 registros para feedback em tempo real
                if ($totalEsus - $lastProgress >= 200) {
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
        // CPF/CNS podem vir numericos ou zerados no e-SUS; normaliza na SQL antes do COALESCE.
        $fciCpf = $cols['cpf'] ? "NULLIF(NULLIF(trim(fci." . $this->quoteCol($cols['cpf']) . "::text), ''), '0')" : null;
        $pecCpf = ($cols['pec_fk'] && $cols['pec_pk'] && $cols['pec_cpf'])
            ? "NULLIF(NULLIF(trim(pec." . $this->quoteCol($cols['pec_cpf']) . "::text), ''), '0')"
            : null;
        $cpfExpr = match (true) {
            $fciCpf && $pecCpf => "COALESCE({$fciCpf}, {$pecCpf})",
            (bool) $fciCpf     => $fciCpf,
            (bool) $pecCpf     => $pecCpf,
            default            => 'NULL',
        };

        // CNS: fci.nu_cns costuma ser "0" (placeholder) — filtrar antes de usar; real vem de pec
        $fciCns = $cols['cns'] ? "NULLIF(NULLIF(trim(fci." . $this->quoteCol($cols['cns']) . "::text), ''), '0')" : null;
        $pecCns = ($cols['pec_fk'] && $cols['pec_pk'] && $cols['pec_cns'])
            ? "NULLIF(NULLIF(trim(pec." . $this->quoteCol($cols['pec_cns']) . "::text), ''), '0')"
            : null;
        $cnsExpr = match (true) {
            $fciCns && $pecCns => "COALESCE({$fciCns}, {$pecCns})",
            (bool) $fciCns     => $fciCns,
            (bool) $pecCns     => $pecCns,
            default            => 'NULL',
        };

        $fciDtNasc = $cols['dt_nasc'] ? 'fci.' . $this->quoteCol($cols['dt_nasc']) : null;
        $dtNascExpr = $fciDtNasc ?? 'NULL';
        $falExpr    = 'NULL';
        $obitoExpr  = 'NULL';
        $updExpr    = $cols['atualizado'] ? 'fci.' . $this->quoteCol($cols['atualizado']) : 'NULL';

        // Telefone: fci.nu_celular é MD5 hashed — usar pec.nu_telefone_celular
        $telExpr = ($cols['pec_fk'] && $cols['pec_telefone'])
            ? 'pec.' . $this->quoteCol($cols['pec_telefone'])
            : 'NULL';

        // Sexo: co_dim_sexo 1=Masculino 2=Feminino; fci preferido, pec como fallback
        $sexoSrc = null;
        if ($cols['fci_sexo']) {
            $sexoSrc = 'fci.' . $this->quoteCol($cols['fci_sexo']);
        } elseif ($cols['pec_fk'] && $cols['pec_sexo']) {
            $sexoSrc = 'pec.' . $this->quoteCol($cols['pec_sexo']);
        }
        $sexoExpr = $sexoSrc
            ? "CASE WHEN {$sexoSrc} = 1 THEN 'MASCULINE' WHEN {$sexoSrc} = 2 THEN 'FEMININE' ELSE 'INDETERMINATE' END"
            : "'INDETERMINATE'";

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

        $escJoin = '';
        $escExpr = 'NULL';
        if ($cols['fci_escolaridade_fk'] && $cols['esc_dim_pk'] && $cols['esc_dim_ds']) {
            $escJoin = 'LEFT JOIN tb_dim_tipo_escolaridade esc ON esc.'
                . $this->quoteCol($cols['esc_dim_pk'])
                . ' = fci.' . $this->quoteCol($cols['fci_escolaridade_fk']);
            $escExpr = 'esc.' . $this->quoteCol($cols['esc_dim_ds']);
        }

        $nacJoin = '';
        $nacExpr = 'NULL';
        if ($cols['fci_nacionalidade_fk'] && $cols['nac_dim_pk'] && $cols['nac_dim_ds']) {
            $nacJoin = 'LEFT JOIN tb_dim_nacionalidade nac ON nac.'
                . $this->quoteCol($cols['nac_dim_pk'])
                . ' = fci.' . $this->quoteCol($cols['fci_nacionalidade_fk']);
            $nacExpr = 'nac.' . $this->quoteCol($cols['nac_dim_ds']);
        }

        $fciNomeCol = $cols['nome'] ? 'fci.' . $this->quoteCol($cols['nome']) : null;

        $needsPecJoin = $cols['pec_nome']
            || $cols['pec_dt_nasc']
            || $cols['pec_cpf']
            || $cols['pec_cns']
            || $cols['pec_sexo']
            || $cols['pec_telefone']
            || $cols['pec_cid_fk']
            || $cols['pec_st_faleceu'];

        if ($cols['pec_fk'] && $cols['pec_pk'] && $needsPecJoin) {
            $pecPk   = $this->quoteCol($cols['pec_pk']);
            $pecFk   = $this->quoteCol($cols['pec_fk']);
            $pecJoin = "LEFT JOIN tb_fat_cidadao_pec pec ON pec.{$pecPk} = fci.{$pecFk}";

            if ($cols['pec_nome']) {
                $pecNome = 'pec.' . $this->quoteCol($cols['pec_nome']);
                $filterPec = "NULLIF(CASE WHEN {$pecNome} ~* '^[0-9a-f]{32,}$' OR {$pecNome} ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$' THEN NULL ELSE {$pecNome} END, '')";

                if ($fciNomeCol) {
                    $filterFci = "NULLIF(CASE WHEN {$fciNomeCol} ~* '^[0-9a-f]{32,}$' OR {$fciNomeCol} ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$' THEN NULL ELSE {$fciNomeCol} END, '')";
                    $nomeExpr  = "COALESCE({$filterPec}, {$filterFci})";
                } else {
                    $nomeExpr = $filterPec;
                }
            }
        } elseif ($fciNomeCol) {
            $nomeExpr = "NULLIF(CASE WHEN {$fciNomeCol} ~* '^[0-9a-f]{32,}$' OR {$fciNomeCol} ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$' THEN NULL ELSE {$fciNomeCol} END, '')";
        }

        // Nome da mãe: fci.no_nome_mae é SHA-1 hashed — vem de tb_cidadao via pec.co_cidadao
        $cidJoin = '';
        $maeExpr = 'NULL';
        $needsCidJoin = $cols['cid_mae']
            || $cols['cid_dt_nasc']
            || $cols['cid_logradouro']
            || $cols['cid_numero']
            || $cols['cid_complemento']
            || $cols['cid_cep']
            || $cols['cid_bairro']
            || $cols['cid_municipio']
            || $cols['cid_st_faleceu']
            || $cols['cid_dt_obito'];

        if ($cols['pec_fk'] && $cols['pec_pk'] && $cols['pec_cid_fk'] && $cols['cid_pk'] && $needsCidJoin) {
            $pecCidFkQ = $this->quoteCol($cols['pec_cid_fk']);
            $cidPkQ    = $this->quoteCol($cols['cid_pk']);
            $cidJoin   = "LEFT JOIN tb_cidadao cid ON cid.{$cidPkQ} = pec.{$pecCidFkQ}";
            if ($cols['cid_mae']) {
                $cidMaeCol = 'cid.' . $this->quoteCol($cols['cid_mae']);
                $maeExpr   = "NULLIF(CASE WHEN {$cidMaeCol} ~* '^[0-9a-f]{32,}$' THEN NULL ELSE {$cidMaeCol} END, '')";
            }
        }

        $dtNascParts = array_values(array_filter([
            $fciDtNasc,
            ($cols['pec_dt_nasc'] && $pecJoin) ? 'pec.' . $this->quoteCol($cols['pec_dt_nasc']) : null,
            ($cols['cid_dt_nasc'] && $cidJoin) ? 'cid.' . $this->quoteCol($cols['cid_dt_nasc']) : null,
        ]));
        $dtNascExpr = count($dtNascParts) > 1
            ? 'COALESCE(' . implode(', ', $dtNascParts) . ')'
            : ($dtNascParts[0] ?? 'NULL');

        $fciFal = $cols['st_faleceu'] ? 'fci.' . $this->quoteCol($cols['st_faleceu']) : null;
        $pecFal = ($cols['pec_fk'] && $cols['pec_st_faleceu']) ? 'pec.' . $this->quoteCol($cols['pec_st_faleceu']) : null;
        $cidFal = ($cols['cid_pk'] && $cols['cid_st_faleceu']) ? 'cid.' . $this->quoteCol($cols['cid_st_faleceu']) : null;
        $falExpr = $fciFal ?? $pecFal ?? $cidFal ?? 'NULL';
        if ($pecFal || $cidFal) {
            $parts = [];
            if ($fciFal) $parts[] = $fciFal;
            if ($pecFal) $parts[] = $pecFal;
            if ($cidFal) $parts[] = $cidFal;
            $falExpr = 'COALESCE(' . implode(', ', $parts) . ')';
        }

        $fciObito = $cols['dt_obito'] ? 'fci.' . $this->quoteCol($cols['dt_obito']) : null;
        $pecObito = ($cols['pec_fk'] && $cols['pec_dt_obito']) ? 'pec.' . $this->quoteCol($cols['pec_dt_obito']) : null;
        $cidObito = ($cols['cid_pk'] && $cols['cid_dt_obito']) ? 'cid.' . $this->quoteCol($cols['cid_dt_obito']) : null;
        $obitoParts = array_values(array_filter([$fciObito, $pecObito, $cidObito]));
        $obitoExpr = count($obitoParts) > 0
            ? (count($obitoParts) === 1 ? $obitoParts[0] : 'COALESCE(' . implode(', ', $obitoParts) . ')')
            : 'NULL';

        // Endereço: fci não tem FK direta para domiciliar — LATERAL via tb_fat_cad_dom_familia.
        // tb_fat_cad_domiciliar pode ter campos hasheados → hash guard + fallback em tb_cidadao.
        $hg = fn(string $col) => "NULLIF(CASE WHEN {$col} ~* '^[0-9a-f]{32,}$' THEN NULL ELSE {$col} END, '')";

        $domLateral = '';
        $logExpr = 'NULL'; $numExpr = 'NULL'; $compExpr = 'NULL';
        $cepExpr = 'NULL'; $baiExpr = 'NULL'; $munExpr = 'NULL';

        // Expressões de fallback via tb_cidadao (texto limpo)
        $cidLog  = ($cols['cid_pk'] && $cols['cid_logradouro'])  ? 'cid.' . $this->quoteCol($cols['cid_logradouro'])  : null;
        $cidNum  = ($cols['cid_pk'] && $cols['cid_numero'])      ? 'cid.' . $this->quoteCol($cols['cid_numero'])      : null;
        $cidComp = ($cols['cid_pk'] && $cols['cid_complemento']) ? 'cid.' . $this->quoteCol($cols['cid_complemento']) : null;
        $cidCep  = ($cols['cid_pk'] && $cols['cid_cep'])         ? 'cid.' . $this->quoteCol($cols['cid_cep'])         : null;
        $cidBai  = ($cols['cid_pk'] && $cols['cid_bairro'])      ? 'cid.' . $this->quoteCol($cols['cid_bairro'])      : null;
        $cidMun  = ($cols['cid_pk'] && $cols['cid_municipio'])   ? 'cid.' . $this->quoteCol($cols['cid_municipio'])   : null;

        if (
            $cols['hasDom'] && $cols['hasFamilia']
            && $cols['familia_cid_fk'] && $cols['familia_dom_fk'] && $cols['dom_pk']
            && $cols['pec_fk']
        ) {
            $famCidFkQ = $this->quoteCol($cols['familia_cid_fk']);
            $famDomFkQ = $this->quoteCol($cols['familia_dom_fk']);
            $domPkQ    = $this->quoteCol($cols['dom_pk']);
            $fciPecFkQ = $this->quoteCol($cols['pec_fk']);

            // Hash guard em cada coluna dentro do LATERAL
            $dLog  = $cols['logradouro']  ? $hg('d.' . $this->quoteCol($cols['logradouro']))  : 'NULL';
            $dNum  = $cols['numero']      ? $hg('d.' . $this->quoteCol($cols['numero']))      : 'NULL';
            $dComp = $cols['complemento'] ? $hg('d.' . $this->quoteCol($cols['complemento'])) : 'NULL';
            $dCep  = $cols['cep']         ? $hg('d.' . $this->quoteCol($cols['cep']))         : 'NULL';
            $dBai  = $cols['bairro']      ? $hg('d.' . $this->quoteCol($cols['bairro']))      : 'NULL';
            $dMun  = $cols['municipio']   ? $hg('d.' . $this->quoteCol($cols['municipio']))   : 'NULL';
            if (
                !$cols['municipio']
                && $cols['dom_municipio_fk']
                && $cols['dim_mun_pk']
                && $cols['dim_mun_nome']
            ) {
                $dMun = $hg('dm.' . $this->quoteCol($cols['dim_mun_nome']));
            }

            $dimMunJoin = '';
            if ($cols['dom_municipio_fk'] && $cols['dim_mun_pk']) {
                $dimMunJoin = 'LEFT JOIN tb_dim_municipio dm ON dm.'
                    . $this->quoteCol($cols['dim_mun_pk'])
                    . ' = d.' . $this->quoteCol($cols['dom_municipio_fk']);
            }

            $domLateral = "
            LEFT JOIN LATERAL (
                SELECT {$dLog} AS logradouro, {$dNum} AS numero,
                       {$dComp} AS complemento, {$dCep} AS cep, {$dBai} AS bairro, {$dMun} AS municipio
                FROM tb_fat_cad_dom_familia f
                JOIN tb_fat_cad_domiciliar d ON d.{$domPkQ} = f.{$famDomFkQ}
                {$dimMunJoin}
                WHERE f.{$famCidFkQ} = fci.{$fciPecFkQ}
                ORDER BY f.{$famDomFkQ} DESC
                LIMIT 1
            ) dom_addr ON true";

            // COALESCE: dom_addr primeiro (mais recente CDS), tb_cidadao como fallback
            $logExpr  = $cidLog  ? "COALESCE(dom_addr.logradouro, {$cidLog})"  : 'dom_addr.logradouro';
            $numExpr  = $cidNum  ? "COALESCE(dom_addr.numero, {$cidNum})"      : 'dom_addr.numero';
            $compExpr = $cidComp ? "COALESCE(dom_addr.complemento, {$cidComp})" : 'dom_addr.complemento';
            $cepExpr  = $cidCep  ? "COALESCE(dom_addr.cep, {$cidCep})"         : 'dom_addr.cep';
            $baiExpr  = $cidBai  ? "COALESCE(dom_addr.bairro, {$cidBai})"      : 'dom_addr.bairro';
            $munExpr  = $cidMun  ? "COALESCE(dom_addr.municipio, {$cidMun})"   : 'dom_addr.municipio';

        } elseif ($cidLog) {
            // Sem dom_familia — usar tb_cidadao diretamente
            $logExpr  = $cidLog;
            $numExpr  = $cidNum  ?? 'NULL';
            $compExpr = $cidComp ?? 'NULL';
            $cepExpr  = $cidCep  ?? 'NULL';
            $baiExpr  = $cidBai  ?? 'NULL';
            $munExpr  = $cidMun  ?? 'NULL';
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
                        {$maeExpr}    AS mae,
                        {$dtNascExpr} AS dt_nasc,
                        {$sexoExpr}   AS sexo,
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
                        {$racaExpr}   AS raca_cor,
                        {$escExpr}    AS escolaridade,
                        {$nacExpr}    AS nacionalidade
                    FROM tb_fat_cad_individual fci
                    {$pecJoin}
                    {$cidJoin}
                    {$racaJoin}
                    {$escJoin}
                    {$nacJoin}
                    {$domLateral}
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
                        {$maeExpr}    AS mae,
                        {$dtNascExpr} AS dt_nasc,
                        {$sexoExpr}   AS sexo,
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
                        {$racaExpr}   AS raca_cor,
                        {$escExpr}    AS escolaridade,
                        {$nacExpr}    AS nacionalidade
                    FROM tb_fat_cad_individual fci
                    {$pecJoin}
                    {$cidJoin}
                    {$racaJoin}
                    {$escJoin}
                    {$nacJoin}
                    {$domLateral}
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

    private function hasDateValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        return trim((string) $value) !== '';
    }

    private function isObitoRow(array $row): bool
    {
        return $this->isTruthy($row['st_faleceu'] ?? null)
            || $this->hasDateValue($row['dt_obito'] ?? null);
    }

    private function shouldProcessObito(Client $client, array $row): bool
    {
        return $client->active && $this->isObitoRow($row);
    }

    private function normalizeDocument(mixed $value, int $expectedLength): ?string
    {
        if ($value === null) return null;
        $digits = preg_replace('/\D/', '', (string) $value);
        if ($digits === '' || preg_match('/^0+$/', $digits)) return null;

        return strlen($digits) <= $expectedLength ? $digits : substr($digits, 0, $expectedLength);
    }

    private function hasMissingCriticalData(Client $client): bool
    {
        $address = $client->addresses;

        return empty($client->born_date)
            || empty($client->cpf)
            || empty($client->cns)
            || !$address
            || empty($address->zip_code)
            || empty($address->street)
            || empty($address->number)
            || empty($address->district)
            || empty($address->complement);
    }

    private function hasFilledValue(mixed $value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }

    private function buildCreatePayload(array $row, array $cols): array
    {
        $addrNorm = $this->normalizeAddressFromRow($row);

        $payload = [
            'name'      => $row['nome'],
            'mother'    => $row['mae']      ?? null,
            'born_date' => $row['dt_nasc'],
            'phone'     => $row['telefone'] ?? null,
            'sexo'      => $row['sexo']     ?? 'INDETERMINATE',
            'raca_cor'  => $row['raca_cor'] ?? null,
            'escolaridade' => $row['escolaridade'] ?? null,
            'nacionalidade' => $row['nacionalidade'] ?? null,
        ];

        if (!empty($addrNorm['street'])) {
            $payload['address'] = [
                'street'     => $addrNorm['street'],
                'number'     => $addrNorm['number'],
                'complement' => $addrNorm['complement'],
                'zip_code'   => $addrNorm['zip_code'],
                'district'   => $addrNorm['district'],
                'city'       => $addrNorm['city'],
            ];
        }

        return $payload;
    }

    private function shouldSyncField(mixed $newValue, mixed $currentValue, bool $allowOverwrite): bool
    {
        if (!$this->hasFilledValue($newValue)) return false;

        $new = trim((string) $newValue);
        $current = $currentValue === null ? '' : trim((string) $currentValue);

        return $new !== $current && ($allowOverwrite || $current === '');
    }

    private function buildDiffPayload(Client $client, array $row, array $cols, bool $allowOverwrite = false): array
    {
        $addrNorm = $this->normalizeAddressFromRow($row);
        $diff = [];

        if ($this->shouldSyncField($row['nome'] ?? null, $client->name, $allowOverwrite)) {
            $diff['nome'] = ['de' => $client->name, 'para' => $row['nome']];
        }

        if ($this->shouldSyncField($row['mae'] ?? null, $client->mother, $allowOverwrite)) {
            $diff['mother'] = ['de' => $client->mother, 'para' => $row['mae']];
        }

        $cpf = $this->normalizeDocument($row['cpf'] ?? null, 11);
        if ($this->shouldSyncField($cpf, $this->normalizeDocument($client->cpf, 11), $allowOverwrite)) {
            $diff['cpf'] = ['de' => $client->cpf, 'para' => $cpf];
        }

        $cns = $this->normalizeDocument($row['cns'] ?? null, 15);
        if ($this->shouldSyncField($cns, $this->normalizeDocument($client->cns, 15), $allowOverwrite)) {
            $diff['cns'] = ['de' => $client->cns, 'para' => $cns];
        }

        if ($row['dt_nasc']) {
            $esusDate   = Carbon::parse($row['dt_nasc'])->format('Y-m-d');
            $sysdocDate = $client->born_date ? Carbon::parse($client->born_date)->format('Y-m-d') : null;
            if ($this->shouldSyncField($esusDate, $sysdocDate, $allowOverwrite)) {
                $diff['born_date'] = ['de' => $sysdocDate, 'para' => $esusDate];
            }
        }

        // Atualiza sexo quando e-SUS tem valor determinado e diverge do Sysdoc
        $rowSexo = $row['sexo'] ?? null;
        if ($rowSexo && $rowSexo !== 'INDETERMINATE' && $this->shouldSyncField($rowSexo, $client->sexo, $allowOverwrite)) {
            $diff['sexo'] = ['de' => $client->sexo, 'para' => $rowSexo];
        }

        if ($this->shouldSyncField($row['telefone'] ?? null, $client->phone, $allowOverwrite)) {
            $diff['phone'] = ['de' => $client->phone, 'para' => $row['telefone']];
        }

        if ($this->shouldSyncField($row['raca_cor'] ?? null, $client->raca_cor, $allowOverwrite)) {
            $diff['raca_cor'] = ['de' => $client->raca_cor, 'para' => $row['raca_cor']];
        }
        if ($this->shouldSyncField($row['escolaridade'] ?? null, $client->escolaridade, $allowOverwrite)) {
            $diff['escolaridade'] = ['de' => $client->escolaridade, 'para' => $row['escolaridade']];
        }
        if ($this->shouldSyncField($row['nacionalidade'] ?? null, $client->nacionalidade, $allowOverwrite)) {
            $diff['nacionalidade'] = ['de' => $client->nacionalidade, 'para' => $row['nacionalidade']];
        }

        $addr = $client->addresses;
        if (!empty($addrNorm['street'])) {
            $addrDiff = [];
            if ($this->shouldSyncField($addrNorm['street'], $addr?->street, $allowOverwrite))
                $addrDiff['street'] = ['de' => $addr?->street, 'para' => $addrNorm['street']];
            if ($this->shouldSyncField($addrNorm['number'], $addr?->number, $allowOverwrite))
                $addrDiff['number'] = ['de' => $addr?->number, 'para' => $addrNorm['number']];
            if ($this->shouldSyncField($addrNorm['district'], $addr?->district, $allowOverwrite))
                $addrDiff['district'] = ['de' => $addr?->district, 'para' => $addrNorm['district']];
            if ($this->shouldSyncField($addrNorm['zip_code'] ?? null, $addr?->zip_code, $allowOverwrite))
                $addrDiff['zip_code'] = ['de' => $addr?->zip_code, 'para' => $addrNorm['zip_code']];
            if ($this->shouldSyncField($addrNorm['complement'] ?? null, $addr?->complement, $allowOverwrite))
                $addrDiff['complement'] = ['de' => $addr?->complement, 'para' => $addrNorm['complement']];
            if ($this->shouldSyncField($addrNorm['city'], $addr?->city, $allowOverwrite))
                $addrDiff['city'] = ['de' => $addr?->city, 'para' => $addrNorm['city']];
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
            $resumoErros = null;
            if ($errosCount > 0) {
                $primeiros = $sync->itens()
                    ->whereNotNull('erro')
                    ->orderBy('id')
                    ->limit(10)
                    ->pluck('erro', 'nome_esus')
                    ->map(fn($err, $nome) => "{$nome}: {$err}")
                    ->implode(' | ');
                $resumoErros = substr("Erros ({$errosCount}): " . $primeiros, 0, 1000);
            }

            $sync->update([
                'status'             => 'completed',
                'result_criados'     => $counts['criar']     ?? 0,
                'result_atualizados' => $counts['atualizar'] ?? 0,
                'result_obitos'      => $counts['obito']     ?? 0,
                'result_erros'       => $errosCount,
                'erro_mensagem'      => $resumoErros,
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
            'mother'    => $payload['mother']   ?? null,
            'cpf'       => $item->cpf,
            'cns'       => $item->cns,
            'born_date' => $payload['born_date'],
            'phone'     => $payload['phone']    ?? null,
            'sexo'      => $payload['sexo']     ?? 'INDETERMINATE',
            'raca_cor'  => $payload['raca_cor'] ?? null,
            'escolaridade' => $payload['escolaridade'] ?? null,
            'nacionalidade' => $payload['nacionalidade'] ?? null,
            'active'    => true,
        ]);

        if (!empty($payload['address']) && !empty($payload['address']['street'])) {
            $address = $this->sanitizeAddressData($payload['address']);
            Addresses::create([
                'id_client'  => $client->id,
                'street'     => $address['street'],
                'number'     => $address['number'],
                'complement' => $address['complement'],
                'zip_code'   => $address['zip_code'],
                'district'   => $address['district'],
                'city'       => $address['city'],
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
        if (isset($payload['mother']))    $clientData['mother']    = $payload['mother']['para'];
        if (isset($payload['cpf']))       $clientData['cpf']       = $payload['cpf']['para'];
        if (isset($payload['cns']))       $clientData['cns']       = $payload['cns']['para'];
        if (isset($payload['born_date'])) $clientData['born_date'] = $payload['born_date']['para'];
        if (isset($payload['sexo']))      $clientData['sexo']      = $payload['sexo']['para'];
        if (isset($payload['phone']))     $clientData['phone']     = $payload['phone']['para'];
        if (isset($payload['raca_cor']))  $clientData['raca_cor']  = $payload['raca_cor']['para'];
        if (isset($payload['escolaridade'])) $clientData['escolaridade'] = $payload['escolaridade']['para'];
        if (isset($payload['nacionalidade'])) $clientData['nacionalidade'] = $payload['nacionalidade']['para'];

        if (!empty($clientData)) {
            $client->update($clientData);
        }

        if (isset($payload['address'])) {
            $address = $client->addresses;
            $addrData = $address ? [
                'street'     => $address->street,
                'number'     => $address->number,
                'complement' => $address->complement,
                'zip_code'   => $address->zip_code,
                'district'   => $address->district,
                'city'       => $address->city,
            ] : [];

            foreach ($payload['address'] as $field => $change) {
                $newValue = $change['para'] ?? null;
                if ($this->hasFilledValue($newValue)) {
                    $addrData[$field] = $newValue;
                }
            }
            $addrData = $this->sanitizeAddressData($addrData);
            if ($address) {
                $address->update($addrData);
            } else {
                Addresses::create(array_merge($addrData, ['id_client' => $client->id, 'active' => true]));
            }
        }

        return true;
    }

    private function normalizeAddressFromRow(array $row): array
    {
        $rawNumber = trim((string) ($row['numero'] ?? ''));
        $rawComplement = trim((string) ($row['complemento'] ?? ''));

        $number = $rawNumber;
        $complement = $rawComplement !== '' ? $rawComplement : null;

        if ($rawNumber !== '' && preg_match('/^(\d+)\s*([A-Za-z].*)$/u', $rawNumber, $m)) {
            $number = $m[1];
            $suffix = trim($m[2]);
            if ($suffix !== '') {
                $complement = $complement ? "{$suffix} {$complement}" : $suffix;
            }
        }

        $base = [
            'street' => $row['logradouro'] ?? null,
            'number' => $number !== '' ? $number : '',
            'complement' => $complement,
            'zip_code' => $row['cep'] ?? null,
            'district' => $row['bairro'] ?? '',
            'city' => !empty($row['municipio']) ? $row['municipio'] : 'Ilicinea',
        ];

        return $this->sanitizeAddressData($base);
    }

    private function sanitizeAddressData(array $data): array
    {
        $sanitize = function ($value, int $max, bool $allowNull = false): ?string {
            if ($value === null) return $allowNull ? null : '';
            $v = trim((string) $value);
            if ($v === '' && $allowNull) return null;
            return mb_substr($v, 0, $max);
        };

        $zip = isset($data['zip_code']) ? preg_replace('/\D/', '', (string) $data['zip_code']) : null;

        return [
            'city' => $sanitize($data['city'] ?? 'Ilicinea', 50),
            'street' => $sanitize($data['street'] ?? '', 100),
            'number' => $sanitize($data['number'] ?? '', 6),
            'complement' => $sanitize($data['complement'] ?? null, 50, true),
            'zip_code' => $sanitize($zip, 10, true),
            'district' => $sanitize($data['district'] ?? '', 100),
        ];
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
