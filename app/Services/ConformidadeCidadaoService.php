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
        ];
    }

    // ─── analisar() ───────────────────────────────────────────────────────────

    public function analisar(SincronizacaoCidadao $sync): void
    {
        try {
            $sync->update(['status' => 'analyzing']);

            $cols = $this->resolveEsusCols();

            // Carrega todos os clients do Sysdoc com endereços
            $sysdocClients = Client::with('addresses')->get();
            $sync->update(['total_sysdoc' => $sysdocClients->count()]);

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

            $this->chunkEsus($cols, function (array $rows) use (
                &$byCpf, &$byCns, &$totalEsus, &$criados, &$atualizados,
                &$obitos, &$semAlteracao, &$itens, $sync, $cols
            ) {
                foreach ($rows as $row) {
                    $totalEsus++;
                    $cpfRaw = $row['cpf'] ? preg_replace('/\D/', '', $row['cpf']) : null;
                    $cnsRaw = $row['cns'] ? preg_replace('/\D/', '', $row['cns']) : null;

                    if (!$cpfRaw && !$cnsRaw) {
                        $semAlteracao++;
                        continue;
                    }

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
        $cpfExpr    = $cols['cpf']        ? 'fci.' . $this->quoteCol($cols['cpf'])        : 'NULL';
        $cnsExpr    = $cols['cns']        ? 'fci.' . $this->quoteCol($cols['cns'])        : 'NULL';
        $nomeExpr   = $cols['nome']       ? 'fci.' . $this->quoteCol($cols['nome'])       : 'NULL';
        $dtNascExpr = $cols['dt_nasc']    ? 'fci.' . $this->quoteCol($cols['dt_nasc'])    : 'NULL';
        $falExpr    = $cols['st_faleceu'] ? 'fci.' . $this->quoteCol($cols['st_faleceu']) : 'NULL';
        $obitoExpr  = $cols['dt_obito']   ? 'fci.' . $this->quoteCol($cols['dt_obito'])   : 'NULL';
        $telExpr    = $cols['telefone']   ? 'fci.' . $this->quoteCol($cols['telefone'])   : 'NULL';
        $updExpr    = $cols['atualizado'] ? 'fci.' . $this->quoteCol($cols['atualizado']) : 'NULL';

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

        // Resolvê defesivamente qual coluna usar para ORDER BY
        $pkCol = $this->firstCol('tb_fat_cad_individual', [
            'co_seq_fat_cad_individual',
            'co_fat_cad_individual',
            'id',
        ]);
        $orderBy = $pkCol ? 'fci.' . $this->quoteCol($pkCol) : '1';

        $chunkSize = 500;
        $offset    = 0;

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
                    {$munExpr}    AS municipio
                FROM tb_fat_cad_individual fci
                {$domJoin}
                WHERE ({$cpfExpr} IS NOT NULL OR {$cnsExpr} IS NOT NULL)
                ORDER BY {$orderBy}
                LIMIT {$chunkSize} OFFSET {$offset}
            ";

            $rows = $this->esus()->select($sql);
            if (empty($rows)) break;

            $callback(array_map(fn($r) => (array) $r, $rows));
            $offset += $chunkSize;

        } while (count($rows) === $chunkSize);
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

        $client->update(['active' => false]);

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
