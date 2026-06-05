# Conformidade de Cidadão — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Criar uma página que sincroniza `clients` do Sysdoc com cidadãos ativos do e-SUS PEC, atualizando dados divergentes, processando óbitos e criando novos registros — com prévia antes de aplicar.

**Architecture:** Job em background (Laravel Queue) realiza análise e aplica mudanças em chunks. Com `QUEUE_CONNECTION=sync` (atual) o Job roda na mesma request. Frontend faz polling a cada 3s até preview ficar pronto, exibe resumo, e aguarda confirmação antes de aplicar.

**Tech Stack:** Laravel 10 + PHP 8.1, MySQL (Sysdoc), PostgreSQL (e-SUS, somente leitura), Next.js 12, React 17, MUI v5, Axios.

**Spec:** `docs/superpowers/specs/2026-05-29-conformidade-cidadao-design.md`

---

## File Map

### Backend (sysdoc_back/)

| Ação | Arquivo |
|---|---|
| Criar | `database/migrations/2026_05_29_100000_create_sincronizacoes_cidadao_table.php` |
| Criar | `database/migrations/2026_05_29_100001_create_sincronizacao_itens_table.php` |
| Criar | `app/Models/SincronizacaoCidadao.php` |
| Criar | `app/Models/SincronizacaoItem.php` |
| Criar | `app/Services/ConformidadeCidadaoService.php` |
| Criar | `app/Jobs/SincronizacaoCidadaoJob.php` |
| Criar | `app/Jobs/AplicarSincronizacaoJob.php` |
| Criar | `app/Http/Controllers/ConformidadeCidadaoController.php` |
| Criar | `database/seeders/ConformidadeCidadaoPageSeeder.php` |
| Modificar | `routes/api.php` |
| Modificar | `database/seeders/DatabaseSeeder.php` |
| Criar | `tests/Feature/ConformidadeCidadaoTest.php` |

### Frontend (sysdoc_front/)

| Ação | Arquivo |
|---|---|
| Criar | `src/services/conformidadeCidadaoApi.js` |
| Criar | `src/components/conformidadeCidadao/index.js` |
| Criar | `pages/conformidade-cidadao.js` |

---

## Task 1: Migrations

**Files:**
- Create: `sysdoc_back/database/migrations/2026_05_29_100000_create_sincronizacoes_cidadao_table.php`
- Create: `sysdoc_back/database/migrations/2026_05_29_100001_create_sincronizacao_itens_table.php`

- [ ] **Step 1: Criar migration sincronizacoes_cidadao**

```php
<?php
// database/migrations/2026_05_29_100000_create_sincronizacoes_cidadao_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sincronizacoes_cidadao', function (Blueprint $table) {
            $table->id();
            $table->string('job_id', 36)->unique();
            $table->enum('status', ['pending', 'analyzing', 'preview_ready', 'applying', 'completed', 'failed'])->default('pending');
            $table->unsignedInteger('total_esus')->default(0);
            $table->unsignedInteger('total_sysdoc')->default(0);
            $table->unsignedInteger('preview_criados')->default(0);
            $table->unsignedInteger('preview_atualizados')->default(0);
            $table->unsignedInteger('preview_obitos')->default(0);
            $table->unsignedInteger('preview_sem_alteracao')->default(0);
            $table->unsignedInteger('result_criados')->nullable();
            $table->unsignedInteger('result_atualizados')->nullable();
            $table->unsignedInteger('result_obitos')->nullable();
            $table->unsignedInteger('result_erros')->nullable();
            $table->unsignedBigInteger('iniciado_por');
            $table->unsignedBigInteger('aplicado_por')->nullable();
            $table->foreign('iniciado_por')->references('id')->on('users');
            $table->foreign('aplicado_por')->references('id')->on('users');
            $table->timestamp('analisado_em')->nullable();
            $table->timestamp('aplicado_em')->nullable();
            $table->text('erro_mensagem')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sincronizacoes_cidadao');
    }
};
```

- [ ] **Step 2: Criar migration sincronizacao_itens**

```php
<?php
// database/migrations/2026_05_29_100001_create_sincronizacao_itens_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sincronizacao_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sincronizacao_id');
            $table->foreign('sincronizacao_id')->references('id')->on('sincronizacoes_cidadao')->cascadeOnDelete();
            $table->enum('acao', ['criar', 'atualizar', 'obito']);
            $table->string('cpf', 18)->nullable();
            $table->string('cns', 15)->nullable();
            $table->string('nome_esus', 150);
            $table->unsignedBigInteger('client_id')->nullable();
            $table->json('payload');
            $table->boolean('aplicado')->default(false);
            $table->string('erro', 255)->nullable();
            $table->index(['sincronizacao_id', 'acao']);
            $table->index(['sincronizacao_id', 'aplicado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sincronizacao_itens');
    }
};
```

- [ ] **Step 3: Rodar migrations**

```bash
cd sysdoc_back && php artisan migrate
```

Esperado: `Migrating: 2026_05_29_100000_create_sincronizacoes_cidadao_table` e `2026_05_29_100001_create_sincronizacao_itens_table` — ambas com `Migrated`.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_05_29_100000_create_sincronizacoes_cidadao_table.php
git add database/migrations/2026_05_29_100001_create_sincronizacao_itens_table.php
git commit -m "feat: migrations sincronizacoes_cidadao e sincronizacao_itens"
```

---

## Task 2: Models

**Files:**
- Create: `sysdoc_back/app/Models/SincronizacaoCidadao.php`
- Create: `sysdoc_back/app/Models/SincronizacaoItem.php`

- [ ] **Step 1: Criar SincronizacaoCidadao**

```php
<?php
// app/Models/SincronizacaoCidadao.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SincronizacaoCidadao extends Model
{
    protected $table = 'sincronizacoes_cidadao';

    protected $fillable = [
        'job_id', 'status', 'total_esus', 'total_sysdoc',
        'preview_criados', 'preview_atualizados', 'preview_obitos', 'preview_sem_alteracao',
        'result_criados', 'result_atualizados', 'result_obitos', 'result_erros',
        'iniciado_por', 'aplicado_por', 'analisado_em', 'aplicado_em', 'erro_mensagem',
    ];

    protected $casts = [
        'analisado_em' => 'datetime',
        'aplicado_em'  => 'datetime',
    ];

    public function itens(): HasMany
    {
        return $this->hasMany(SincronizacaoItem::class, 'sincronizacao_id');
    }

    public function iniciadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iniciado_por');
    }

    public function estaEmAndamento(): bool
    {
        return in_array($this->status, ['pending', 'analyzing', 'applying']);
    }
}
```

- [ ] **Step 2: Criar SincronizacaoItem**

```php
<?php
// app/Models/SincronizacaoItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SincronizacaoItem extends Model
{
    public $timestamps = false;

    protected $table = 'sincronizacao_itens';

    protected $fillable = [
        'sincronizacao_id', 'acao', 'cpf', 'cns', 'nome_esus',
        'client_id', 'payload', 'aplicado', 'erro',
    ];

    protected $casts = [
        'payload'  => 'array',
        'aplicado' => 'boolean',
    ];

    public function sincronizacao(): BelongsTo
    {
        return $this->belongsTo(SincronizacaoCidadao::class, 'sincronizacao_id');
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Models/SincronizacaoCidadao.php app/Models/SincronizacaoItem.php
git commit -m "feat: models SincronizacaoCidadao e SincronizacaoItem"
```

---

## Task 3: Feature Tests (TDD)

**Files:**
- Create: `sysdoc_back/tests/Feature/ConformidadeCidadaoTest.php`

- [ ] **Step 1: Criar arquivo de testes**

```php
<?php
// tests/Feature/ConformidadeCidadaoTest.php

namespace Tests\Feature;

use App\Models\SincronizacaoCidadao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConformidadeCidadaoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $this->user  = User::factory()->create(['profile' => 'user',  'active' => true]);
    }

    public function test_analisar_requer_autenticacao(): void
    {
        $r = $this->postJson('/api/conformidade-cidadao/analisar');
        $this->assertEquals(401, $r->status());
    }

    public function test_historico_requer_autenticacao(): void
    {
        $r = $this->getJson('/api/conformidade-cidadao/historico');
        $this->assertEquals(401, $r->status());
    }

    public function test_rotas_existem(): void
    {
        $r = $this->actingAs($this->admin, 'sanctum')->getJson('/api/conformidade-cidadao/historico');
        $this->assertNotEquals(404, $r->status());
    }

    public function test_historico_retorna_lista_vazia_inicialmente(): void
    {
        $r = $this->actingAs($this->admin, 'sanctum')->getJson('/api/conformidade-cidadao/historico');
        $r->assertStatus(200)->assertJsonStructure(['data', 'meta' => ['total', 'per_page', 'current_page']]);
        $this->assertCount(0, $r->json('data'));
    }

    public function test_status_retorna_404_para_job_inexistente(): void
    {
        $r = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/conformidade-cidadao/status/uuid-inexistente');
        $r->assertStatus(404);
    }

    public function test_aplicar_retorna_409_se_status_nao_e_preview_ready(): void
    {
        $sync = SincronizacaoCidadao::create([
            'job_id'       => Str::uuid(),
            'status'       => 'analyzing',
            'iniciado_por' => $this->admin->id,
        ]);

        $r = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/conformidade-cidadao/aplicar/{$sync->job_id}");
        $r->assertStatus(409);
    }

    public function test_analisar_retorna_409_se_ja_existe_sync_em_andamento(): void
    {
        SincronizacaoCidadao::create([
            'job_id'       => Str::uuid(),
            'status'       => 'analyzing',
            'iniciado_por' => $this->admin->id,
        ]);

        // Mocka o serviço para não tentar conectar ao e-SUS real
        $this->mock(\App\Services\ConformidadeCidadaoService::class, function ($mock) {
            $mock->shouldNotReceive('analisar');
        });

        $r = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/conformidade-cidadao/analisar');
        $r->assertStatus(409);
    }

    public function test_status_retorna_estrutura_correta(): void
    {
        $sync = SincronizacaoCidadao::create([
            'job_id'       => Str::uuid(),
            'status'       => 'preview_ready',
            'total_esus'   => 100,
            'total_sysdoc' => 90,
            'preview_criados'    => 10,
            'preview_atualizados' => 5,
            'preview_obitos'     => 2,
            'iniciado_por' => $this->admin->id,
        ]);

        $r = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/conformidade-cidadao/status/{$sync->job_id}");

        $r->assertStatus(200)->assertJsonStructure([
            'job_id', 'status', 'total_esus', 'total_sysdoc',
            'preview_criados', 'preview_atualizados', 'preview_obitos',
        ]);
        $this->assertEquals('preview_ready', $r->json('status'));
    }
}
```

- [ ] **Step 2: Rodar testes — devem falhar (rotas não existem ainda)**

```bash
cd sysdoc_back && ./vendor/bin/phpunit tests/Feature/ConformidadeCidadaoTest.php --testdox
```

Esperado: falhas como `Route [api/conformidade-cidadao/historico] not found` ou `404`. Testes de autenticação vão passar (401 qualquer rota).

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/ConformidadeCidadaoTest.php
git commit -m "test: scaffold ConformidadeCidadaoTest (TDD — falha esperada)"
```

---

## Task 4: ConformidadeCidadaoService — analisar()

**Files:**
- Create: `sysdoc_back/app/Services/ConformidadeCidadaoService.php`

- [ ] **Step 1: Criar o Service com conexão e-SUS e detecção de colunas**

```php
<?php
// app/Services/ConformidadeCidadaoService.php

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

            $totalEsus   = 0;
            $criados     = 0;
            $atualizados = 0;
            $obitos      = 0;
            $semAlteracao = 0;
            $itens       = [];

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
                        if (!$row['nome']) { $semAlteracao++; continue; } // nome obrigatório
                        if (!$row['dt_nasc']) { $semAlteracao++; continue; } // born_date obrigatório

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
                        $isFalecido  = $this->isTruthy($row['st_faleceu'] ?? null);
                        $esusUpdated = $row['atualizado'] ? Carbon::parse($row['atualizado']) : null;
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

                        if (count($itens) >= 200) {
                            SincronizacaoItem::insert($itens);
                            $itens = [];
                        }
                    }
                }
            });

            if (!empty($itens)) {
                SincronizacaoItem::insert($itens);
            }

            $sync->update([
                'status'               => 'preview_ready',
                'total_esus'           => $totalEsus,
                'preview_criados'      => $criados,
                'preview_atualizados'  => $atualizados,
                'preview_obitos'       => $obitos,
                'preview_sem_alteracao' => $semAlteracao,
                'analisado_em'         => now(),
            ]);

        } catch (\Throwable $e) {
            Log::error('[ConformidadeCidadao] analisar() falhou', ['error' => $e->getMessage()]);
            $sync->update(['status' => 'failed', 'erro_mensagem' => $e->getMessage()]);
            throw $e;
        }
    }

    private function chunkEsus(array $cols, callable $callback): void
    {
        $cpfExpr    = $cols['cpf']        ? "fci.{$cols['cpf']}"       : 'NULL';
        $cnsExpr    = $cols['cns']        ? "fci.{$cols['cns']}"       : 'NULL';
        $nomeExpr   = $cols['nome']       ? "fci.{$cols['nome']}"      : 'NULL';
        $dtNascExpr = $cols['dt_nasc']    ? "fci.{$cols['dt_nasc']}"   : 'NULL';
        $falExpr    = $cols['st_faleceu'] ? "fci.{$cols['st_faleceu']}" : 'NULL';
        $obitoExpr  = $cols['dt_obito']   ? "fci.{$cols['dt_obito']}"  : 'NULL';
        $telExpr    = $cols['telefone']   ? "fci.{$cols['telefone']}"  : 'NULL';
        $updExpr    = $cols['atualizado'] ? "fci.{$cols['atualizado']}" : 'NULL';

        $domJoin = '';
        $logExpr = 'NULL'; $numExpr = 'NULL'; $compExpr = 'NULL';
        $cepExpr = 'NULL'; $baiExpr = 'NULL'; $munExpr  = 'NULL';

        if ($cols['hasDom'] && $cols['dom_fk'] && $cols['dom_pk']) {
            $domJoin = "LEFT JOIN tb_fat_cad_domiciliar dom ON dom.{$cols['dom_pk']} = fci.{$cols['dom_fk']}";
            if ($cols['logradouro'])  $logExpr  = "dom.{$cols['logradouro']}";
            if ($cols['numero'])      $numExpr  = "dom.{$cols['numero']}";
            if ($cols['complemento']) $compExpr = "dom.{$cols['complemento']}";
            if ($cols['cep'])         $cepExpr  = "dom.{$cols['cep']}";
            if ($cols['bairro'])      $baiExpr  = "dom.{$cols['bairro']}";
            if ($cols['municipio'])   $munExpr  = "dom.{$cols['municipio']}";
        }

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
                ORDER BY fci.co_seq_fat_cad_individual
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
                'number'     => $row['numero']     ?? '',
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

            $sync->update([
                'status'           => 'completed',
                'result_criados'   => $criados,
                'result_atualizados' => $atualizados,
                'result_obitos'    => $obitos,
                'result_erros'     => $erros,
                'aplicado_em'      => now(),
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

        DB::transaction(function () use ($item, $payload) {
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
        });

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
        $payload = $item->payload;

        $dtObito = $payload['dt_obito'] ?? null;
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
```

- [ ] **Step 2: Commit**

```bash
git add app/Services/ConformidadeCidadaoService.php
git commit -m "feat: ConformidadeCidadaoService (analisar + aplicar)"
```

---

## Task 5: Jobs

**Files:**
- Create: `sysdoc_back/app/Jobs/SincronizacaoCidadaoJob.php`
- Create: `sysdoc_back/app/Jobs/AplicarSincronizacaoJob.php`

- [ ] **Step 1: Criar SincronizacaoCidadaoJob**

```php
<?php
// app/Jobs/SincronizacaoCidadaoJob.php

namespace App\Jobs;

use App\Models\SincronizacaoCidadao;
use App\Services\ConformidadeCidadaoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SincronizacaoCidadaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutos

    public function __construct(public SincronizacaoCidadao $sync) {}

    public function handle(ConformidadeCidadaoService $service): void
    {
        $service->analisar($this->sync);
    }

    public function failed(\Throwable $e): void
    {
        $this->sync->update([
            'status'        => 'failed',
            'erro_mensagem' => $e->getMessage(),
        ]);
    }
}
```

- [ ] **Step 2: Criar AplicarSincronizacaoJob**

```php
<?php
// app/Jobs/AplicarSincronizacaoJob.php

namespace App\Jobs;

use App\Models\SincronizacaoCidadao;
use App\Services\ConformidadeCidadaoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AplicarSincronizacaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutos

    public function __construct(public SincronizacaoCidadao $sync) {}

    public function handle(ConformidadeCidadaoService $service): void
    {
        $service->aplicar($this->sync);
    }

    public function failed(\Throwable $e): void
    {
        $this->sync->update([
            'status'        => 'failed',
            'erro_mensagem' => $e->getMessage(),
        ]);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Jobs/SincronizacaoCidadaoJob.php app/Jobs/AplicarSincronizacaoJob.php
git commit -m "feat: jobs SincronizacaoCidadaoJob e AplicarSincronizacaoJob"
```

---

## Task 6: Controller e Rotas

**Files:**
- Create: `sysdoc_back/app/Http/Controllers/ConformidadeCidadaoController.php`
- Modify: `sysdoc_back/routes/api.php`

- [ ] **Step 1: Criar o controller**

```php
<?php
// app/Http/Controllers/ConformidadeCidadaoController.php

namespace App\Http\Controllers;

use App\Jobs\AplicarSincronizacaoJob;
use App\Jobs\SincronizacaoCidadaoJob;
use App\Models\SincronizacaoCidadao;
use App\Models\SincronizacaoItem;
use App\Services\Authorization\PagePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ConformidadeCidadaoController extends Controller
{
    private function autorizado(Request $request): bool
    {
        return app(PagePermissionService::class)->canAccess($request->user(), '/conformidade-cidadao');
    }

    public function analisar(Request $request): JsonResponse
    {
        if (!$this->autorizado($request)) {
            return response()->json(['message' => 'Sem permissão para executar a sincronização.'], 403);
        }

        $emAndamento = SincronizacaoCidadao::whereIn('status', ['pending', 'analyzing', 'applying'])->exists();
        if ($emAndamento) {
            return response()->json(['message' => 'Já existe uma sincronização em andamento.'], 409);
        }

        $sync = SincronizacaoCidadao::create([
            'job_id'       => (string) Str::uuid(),
            'status'       => 'pending',
            'iniciado_por' => $request->user()->id,
        ]);

        SincronizacaoCidadaoJob::dispatch($sync);

        return response()->json(['job_id' => $sync->job_id], 202);
    }

    public function status(Request $request, string $jobId): JsonResponse
    {
        if (!$this->autorizado($request)) {
            return response()->json(['message' => 'Sem permissão.'], 403);
        }

        $sync = SincronizacaoCidadao::where('job_id', $jobId)->first();
        if (!$sync) {
            return response()->json(['message' => 'Sincronização não encontrada.'], 404);
        }

        $data = $sync->only([
            'job_id', 'status', 'total_esus', 'total_sysdoc',
            'preview_criados', 'preview_atualizados', 'preview_obitos', 'preview_sem_alteracao',
            'result_criados', 'result_atualizados', 'result_obitos', 'result_erros',
            'analisado_em', 'aplicado_em', 'erro_mensagem',
        ]);

        if ($sync->status === 'preview_ready') {
            $perPage = min(50, max(10, (int) $request->query('per_page', 20)));
            $page    = max(1, (int) $request->query('page', 1));

            $itens = SincronizacaoItem::where('sincronizacao_id', $sync->id)
                ->select(['id', 'acao', 'cpf', 'cns', 'nome_esus', 'client_id', 'payload'])
                ->forPage($page, $perPage)
                ->get();

            $total = SincronizacaoItem::where('sincronizacao_id', $sync->id)->count();

            $data['itens'] = $itens;
            $data['itens_meta'] = [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ];
        }

        return response()->json($data);
    }

    public function aplicar(Request $request, string $jobId): JsonResponse
    {
        if (!$this->autorizado($request)) {
            return response()->json(['message' => 'Sem permissão.'], 403);
        }

        $sync = SincronizacaoCidadao::where('job_id', $jobId)->first();
        if (!$sync) {
            return response()->json(['message' => 'Sincronização não encontrada.'], 404);
        }

        if ($sync->status !== 'preview_ready') {
            return response()->json([
                'message' => "Não é possível aplicar no status atual: {$sync->status}.",
            ], 409);
        }

        $sync->update(['aplicado_por' => $request->user()->id]);
        AplicarSincronizacaoJob::dispatch($sync);

        return response()->json(['message' => 'Aplicação iniciada.'], 202);
    }

    public function historico(Request $request): JsonResponse
    {
        if (!$this->autorizado($request)) {
            return response()->json(['message' => 'Sem permissão.'], 403);
        }

        $perPage = min(50, max(5, (int) $request->query('per_page', 15)));
        $syncs   = SincronizacaoCidadao::select([
                'job_id', 'status', 'total_esus', 'total_sysdoc',
                'preview_criados', 'preview_atualizados', 'preview_obitos',
                'result_criados', 'result_atualizados', 'result_obitos', 'result_erros',
                'iniciado_por', 'analisado_em', 'aplicado_em', 'created_at',
            ])
            ->with('iniciadoPor:id,name')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $syncs->items(),
            'meta' => [
                'total'        => $syncs->total(),
                'per_page'     => $syncs->perPage(),
                'current_page' => $syncs->currentPage(),
                'last_page'    => $syncs->lastPage(),
            ],
        ]);
    }
}
```

- [ ] **Step 2: Adicionar rotas em routes/api.php**

Localizar o bloco `Route::group(['middleware' => ['auth:sanctum']]` e adicionar antes do fechamento:

```php
// Adicionar no início do arquivo, na lista de use statements:
use App\Http\Controllers\ConformidadeCidadaoController;

// Adicionar dentro do grupo auth:sanctum:
Route::prefix('conformidade-cidadao')->group(function () {
    Route::post('analisar', [ConformidadeCidadaoController::class, 'analisar']);
    Route::get('status/{job_id}', [ConformidadeCidadaoController::class, 'status']);
    Route::post('aplicar/{job_id}', [ConformidadeCidadaoController::class, 'aplicar']);
    Route::get('historico', [ConformidadeCidadaoController::class, 'historico']);
});
```

- [ ] **Step 3: Rodar os testes — devem passar agora**

```bash
cd sysdoc_back && ./vendor/bin/phpunit tests/Feature/ConformidadeCidadaoTest.php --testdox
```

Esperado: todos os testes PASS. Se algum falhar, verificar as mensagens de erro antes de continuar.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/ConformidadeCidadaoController.php routes/api.php
git commit -m "feat: ConformidadeCidadaoController e rotas"
```

---

## Task 7: Seeder de Página do Sistema

**Files:**
- Create: `sysdoc_back/database/seeders/ConformidadeCidadaoPageSeeder.php`
- Modify: `sysdoc_back/database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Criar o seeder**

```php
<?php
// database/seeders/ConformidadeCidadaoPageSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConformidadeCidadaoPageSeeder extends Seeder
{
    public function run(): void
    {
        $categoria = DB::table('page_categories')->where('nome', 'Cadastros')->first();

        DB::table('system_pages')->updateOrInsert(
            ['path' => '/conformidade-cidadao'],
            [
                'titulo'      => 'Conformidade de Cidadãos',
                'icone'       => 'refresh-cw',
                'categoria'   => 'Cadastros',
                'category_id' => $categoria?->id,
                'ativo'       => true,
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );

        // Permissão padrão para admin
        $page    = DB::table('system_pages')->where('path', '/conformidade-cidadao')->first();
        $profile = DB::table('access_profiles')->where('slug', 'admin')->first();

        if ($page && $profile) {
            DB::table('profile_page_permissions')->updateOrInsert(
                ['access_profile_id' => $profile->id, 'system_page_id' => $page->id],
                ['updated_at' => now()]
            );
        }
    }
}
```

- [ ] **Step 2: Registrar em DatabaseSeeder.php**

Abrir `database/seeders/DatabaseSeeder.php` e adicionar na lista de seeders:

```php
$this->call([
    // ... seeders existentes ...
    ConformidadeCidadaoPageSeeder::class,
]);
```

- [ ] **Step 3: Rodar o seeder**

```bash
cd sysdoc_back && php artisan db:seed --class=ConformidadeCidadaoPageSeeder
```

Esperado: `Database seeding completed successfully.`

- [ ] **Step 4: Commit**

```bash
git add database/seeders/ConformidadeCidadaoPageSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "feat: seeder da página conformidade-cidadao em system_pages"
```

---

## Task 8: Frontend — Service

**Files:**
- Create: `sysdoc_front/src/services/conformidadeCidadaoApi.js`

- [ ] **Step 1: Criar o service**

```js
// src/services/conformidadeCidadaoApi.js
import { api } from './api';

const BASE = '/conformidade-cidadao';

export const conformidadeCidadaoApi = {
    analisar: () =>
        api.post(`${BASE}/analisar`).then(r => r.data),

    status: (jobId, page = 1, perPage = 20) =>
        api.get(`${BASE}/status/${jobId}`, { params: { page, per_page: perPage } }).then(r => r.data),

    aplicar: (jobId) =>
        api.post(`${BASE}/aplicar/${jobId}`).then(r => r.data),

    historico: (page = 1, perPage = 15) =>
        api.get(`${BASE}/historico`, { params: { page, per_page: perPage } }).then(r => r.data),
};
```

- [ ] **Step 2: Commit**

```bash
git add src/services/conformidadeCidadaoApi.js
git commit -m "feat: conformidadeCidadaoApi service"
```

---

## Task 9: Frontend — Componente

**Files:**
- Create: `sysdoc_front/src/components/conformidadeCidadao/index.js`

- [ ] **Step 1: Criar o componente**

```js
// src/components/conformidadeCidadao/index.js
import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
    Box, Typography, Button, Alert, CircularProgress,
    Table, TableBody, TableCell, TableHead, TableRow,
    TableContainer, Paper, Chip, Grid, Divider,
    TablePagination,
} from '@mui/material';
import FeatherIcon from 'feather-icons-react';
import BaseCard from '../baseCard/BaseCard';
import { conformidadeCidadaoApi } from '../../services/conformidadeCidadaoApi';

const CHIP_COLORS = { criar: 'success', atualizar: 'info', obito: 'error' };
const CHIP_LABELS = { criar: 'Criar', atualizar: 'Atualizar', obito: 'Óbito' };

function diffToString(payload) {
    return Object.keys(payload)
        .filter(k => k !== 'address')
        .map(k => {
            if (k === 'nome') return 'Nome';
            if (k === 'born_date') return 'Nascimento';
            if (k === 'phone') return 'Telefone';
            return k;
        })
        .concat(payload.address ? ['Endereço'] : [])
        .join(', ') || '—';
}

export default function ConformidadeCidadao() {
    const [fase, setFase] = useState('idle'); // idle | analyzing | preview | applying | done | error
    const [jobId, setJobId] = useState(null);
    const [syncData, setSyncData] = useState(null);
    const [itens, setItens] = useState([]);
    const [itensMeta, setItensMeta] = useState({ total: 0, per_page: 20, current_page: 1, last_page: 1 });
    const [historico, setHistorico] = useState([]);
    const [histMeta, setHistMeta] = useState({ total: 0, per_page: 15, current_page: 1, last_page: 1 });
    const [erro, setErro] = useState(null);
    const pollingRef = useRef(null);

    const carregarHistorico = useCallback(async (page = 1) => {
        try {
            const data = await conformidadeCidadaoApi.historico(page);
            setHistorico(data.data);
            setHistMeta(data.meta);
        } catch (_) {}
    }, []);

    useEffect(() => { carregarHistorico(); }, [carregarHistorico]);

    const iniciarPolling = useCallback((jid) => {
        pollingRef.current = setInterval(async () => {
            try {
                const data = await conformidadeCidadaoApi.status(jid);
                setSyncData(data);
                if (data.status === 'preview_ready') {
                    setItens(data.itens || []);
                    setItensMeta(data.itens_meta || itensMeta);
                    setFase('preview');
                    clearInterval(pollingRef.current);
                } else if (data.status === 'completed') {
                    setFase('done');
                    clearInterval(pollingRef.current);
                    carregarHistorico();
                } else if (data.status === 'failed') {
                    setErro(data.erro_mensagem || 'Erro desconhecido na sincronização.');
                    setFase('error');
                    clearInterval(pollingRef.current);
                }
            } catch (_) {}
        }, 3000);
    }, [carregarHistorico]);

    useEffect(() => () => clearInterval(pollingRef.current), []);

    const handleAnalisar = async () => {
        setErro(null);
        setFase('analyzing');
        try {
            const data = await conformidadeCidadaoApi.analisar();
            setJobId(data.job_id);
            if (data.status === 'preview_ready') {
                // QUEUE_CONNECTION=sync: já concluiu
                const status = await conformidadeCidadaoApi.status(data.job_id);
                setSyncData(status);
                setItens(status.itens || []);
                setItensMeta(status.itens_meta || itensMeta);
                setFase('preview');
            } else {
                iniciarPolling(data.job_id);
            }
        } catch (err) {
            const status = err?.response?.status;
            if (status === 409) setErro('Já existe uma sincronização em andamento.');
            else if (status === 403) setErro('Sem permissão para executar a sincronização.');
            else setErro('Erro ao iniciar a análise. Verifique a conexão com o e-SUS.');
            setFase('idle');
        }
    };

    const handleAplicar = async () => {
        setFase('applying');
        try {
            await conformidadeCidadaoApi.aplicar(jobId);
            iniciarPolling(jobId);
        } catch (err) {
            setErro('Erro ao iniciar a aplicação das alterações.');
            setFase('preview');
        }
    };

    const handleDescartarPreview = () => {
        setFase('idle');
        setJobId(null);
        setSyncData(null);
        setItens([]);
    };

    const handleItensPagina = async (_, newPage) => {
        try {
            const data = await conformidadeCidadaoApi.status(jobId, newPage + 1, itensMeta.per_page);
            setItens(data.itens || []);
            setItensMeta(data.itens_meta || itensMeta);
        } catch (_) {}
    };

    const handleNovaSincronizacao = () => {
        setFase('idle');
        setJobId(null);
        setSyncData(null);
        setItens([]);
        setErro(null);
        carregarHistorico();
    };

    return (
        <Box display="flex" flexDirection="column" gap={3}>
            {erro && <Alert severity="error" onClose={() => setErro(null)}>{erro}</Alert>}

            {/* ── IDLE ──────────────────────────────────────────────── */}
            {fase === 'idle' && (
                <BaseCard title="Conformidade de Cidadãos — e-SUS PEC">
                    <Box display="flex" flexDirection="column" gap={2} maxWidth={560}>
                        <Typography variant="body2" color="textSecondary">
                            Compara os clientes cadastrados no Sysdoc com os cidadãos ativos no e-SUS PEC.
                            Atualiza dados divergentes, processa óbitos e cria novos registros. Uma prévia
                            é exibida antes de qualquer alteração ser aplicada.
                        </Typography>
                        <Box>
                            <Button
                                variant="contained"
                                startIcon={<FeatherIcon icon="refresh-cw" width="16" />}
                                onClick={handleAnalisar}
                            >
                                Analisar agora
                            </Button>
                        </Box>
                    </Box>
                </BaseCard>
            )}

            {/* ── ANALISANDO ────────────────────────────────────────── */}
            {fase === 'analyzing' && (
                <BaseCard title="Analisando...">
                    <Box display="flex" alignItems="center" gap={2}>
                        <CircularProgress size={24} />
                        <Typography>Comparando cidadãos com e-SUS PEC...</Typography>
                    </Box>
                </BaseCard>
            )}

            {/* ── PRÉVIA ────────────────────────────────────────────── */}
            {fase === 'preview' && syncData && (
                <BaseCard title="Prévia das Alterações">
                    <Box display="flex" flexDirection="column" gap={3}>
                        <Grid container spacing={2}>
                            <Grid item xs={12} sm={4}>
                                <Paper variant="outlined" sx={{ p: 2, textAlign: 'center' }}>
                                    <Typography variant="h4" color="success.main">{syncData.preview_criados}</Typography>
                                    <Typography variant="body2">Novos para criar</Typography>
                                </Paper>
                            </Grid>
                            <Grid item xs={12} sm={4}>
                                <Paper variant="outlined" sx={{ p: 2, textAlign: 'center' }}>
                                    <Typography variant="h4" color="info.main">{syncData.preview_atualizados}</Typography>
                                    <Typography variant="body2">Com dados diferentes</Typography>
                                </Paper>
                            </Grid>
                            <Grid item xs={12} sm={4}>
                                <Paper variant="outlined" sx={{ p: 2, textAlign: 'center' }}>
                                    <Typography variant="h4" color="error.main">{syncData.preview_obitos}</Typography>
                                    <Typography variant="body2">Óbitos a processar</Typography>
                                </Paper>
                            </Grid>
                        </Grid>

                        <TableContainer component={Paper} variant="outlined">
                            <Table size="small">
                                <TableHead>
                                    <TableRow>
                                        <TableCell>Ação</TableCell>
                                        <TableCell>Nome (e-SUS)</TableCell>
                                        <TableCell>CPF / CNS</TableCell>
                                        <TableCell>Campos</TableCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {itens.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell>
                                                <Chip
                                                    label={CHIP_LABELS[item.acao]}
                                                    color={CHIP_COLORS[item.acao]}
                                                    size="small"
                                                />
                                            </TableCell>
                                            <TableCell>{item.nome_esus}</TableCell>
                                            <TableCell sx={{ fontSize: '0.75rem' }}>
                                                {item.cpf || '—'}<br />{item.cns || '—'}
                                            </TableCell>
                                            <TableCell sx={{ fontSize: '0.75rem' }}>
                                                {item.acao === 'criar' ? 'Novo cadastro' :
                                                 item.acao === 'obito' ? 'Inativar + filas' :
                                                 diffToString(item.payload)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            <TablePagination
                                component="div"
                                count={itensMeta.total}
                                page={itensMeta.current_page - 1}
                                rowsPerPage={itensMeta.per_page}
                                rowsPerPageOptions={[]}
                                onPageChange={handleItensPagina}
                                labelDisplayedRows={({ from, to, count }) => `${from}–${to} de ${count}`}
                            />
                        </TableContainer>

                        <Box display="flex" gap={2}>
                            <Button variant="contained" color="primary" onClick={handleAplicar}>
                                Aplicar alterações
                            </Button>
                            <Button variant="outlined" color="inherit" onClick={handleDescartarPreview}>
                                Descartar
                            </Button>
                        </Box>
                    </Box>
                </BaseCard>
            )}

            {/* ── APLICANDO ─────────────────────────────────────────── */}
            {fase === 'applying' && (
                <BaseCard title="Aplicando...">
                    <Box display="flex" alignItems="center" gap={2}>
                        <CircularProgress size={24} />
                        <Typography>Aplicando alterações no Sysdoc...</Typography>
                    </Box>
                </BaseCard>
            )}

            {/* ── CONCLUÍDO ─────────────────────────────────────────── */}
            {fase === 'done' && syncData && (
                <BaseCard title="Sincronização Concluída">
                    <Box display="flex" flexDirection="column" gap={2}>
                        {syncData.result_erros > 0 && (
                            <Alert severity="warning">
                                {syncData.result_erros} item(s) com erro — verifique os detalhes no histórico.
                            </Alert>
                        )}
                        <Grid container spacing={2}>
                            <Grid item xs={6} sm={3}>
                                <Paper variant="outlined" sx={{ p: 2, textAlign: 'center' }}>
                                    <Typography variant="h4" color="success.main">{syncData.result_criados ?? 0}</Typography>
                                    <Typography variant="body2">Criados</Typography>
                                </Paper>
                            </Grid>
                            <Grid item xs={6} sm={3}>
                                <Paper variant="outlined" sx={{ p: 2, textAlign: 'center' }}>
                                    <Typography variant="h4" color="info.main">{syncData.result_atualizados ?? 0}</Typography>
                                    <Typography variant="body2">Atualizados</Typography>
                                </Paper>
                            </Grid>
                            <Grid item xs={6} sm={3}>
                                <Paper variant="outlined" sx={{ p: 2, textAlign: 'center' }}>
                                    <Typography variant="h4" color="error.main">{syncData.result_obitos ?? 0}</Typography>
                                    <Typography variant="body2">Óbitos</Typography>
                                </Paper>
                            </Grid>
                            <Grid item xs={6} sm={3}>
                                <Paper variant="outlined" sx={{ p: 2, textAlign: 'center' }}>
                                    <Typography variant="h4" color={syncData.result_erros > 0 ? 'warning.main' : 'text.secondary'}>
                                        {syncData.result_erros ?? 0}
                                    </Typography>
                                    <Typography variant="body2">Erros</Typography>
                                </Paper>
                            </Grid>
                        </Grid>
                        <Box>
                            <Button variant="outlined" onClick={handleNovaSincronizacao}>
                                Nova sincronização
                            </Button>
                        </Box>
                    </Box>
                </BaseCard>
            )}

            {/* ── ERRO ──────────────────────────────────────────────── */}
            {fase === 'error' && (
                <BaseCard title="Erro na Sincronização">
                    <Box display="flex" flexDirection="column" gap={2}>
                        <Alert severity="error">{erro || 'Erro desconhecido.'}</Alert>
                        <Box>
                            <Button variant="outlined" onClick={handleNovaSincronizacao}>
                                Tentar novamente
                            </Button>
                        </Box>
                    </Box>
                </BaseCard>
            )}

            {/* ── HISTÓRICO ─────────────────────────────────────────── */}
            {fase === 'idle' && historico.length > 0 && (
                <BaseCard title="Histórico de Sincronizações">
                    <TableContainer component={Paper} variant="outlined">
                        <Table size="small">
                            <TableHead>
                                <TableRow>
                                    <TableCell>Data</TableCell>
                                    <TableCell>Status</TableCell>
                                    <TableCell>Criados</TableCell>
                                    <TableCell>Atualizados</TableCell>
                                    <TableCell>Óbitos</TableCell>
                                    <TableCell>Erros</TableCell>
                                    <TableCell>Iniciado por</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {historico.map((s, i) => (
                                    <TableRow key={i}>
                                        <TableCell sx={{ fontSize: '0.75rem' }}>
                                            {s.created_at ? new Date(s.created_at).toLocaleString('pt-BR') : '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Chip
                                                label={s.status}
                                                size="small"
                                                color={s.status === 'completed' ? 'success' : s.status === 'failed' ? 'error' : 'default'}
                                            />
                                        </TableCell>
                                        <TableCell>{s.result_criados ?? '—'}</TableCell>
                                        <TableCell>{s.result_atualizados ?? '—'}</TableCell>
                                        <TableCell>{s.result_obitos ?? '—'}</TableCell>
                                        <TableCell>{s.result_erros ?? '—'}</TableCell>
                                        <TableCell>{s.iniciado_por?.name ?? '—'}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        <TablePagination
                            component="div"
                            count={histMeta.total}
                            page={histMeta.current_page - 1}
                            rowsPerPage={histMeta.per_page}
                            rowsPerPageOptions={[]}
                            onPageChange={(_, p) => carregarHistorico(p + 1)}
                            labelDisplayedRows={({ from, to, count }) => `${from}–${to} de ${count}`}
                        />
                    </TableContainer>
                </BaseCard>
            )}
        </Box>
    );
}
```

- [ ] **Step 2: Commit**

```bash
git add src/components/conformidadeCidadao/index.js
git commit -m "feat: componente ConformidadeCidadao"
```

---

## Task 10: Frontend — Página

**Files:**
- Create: `sysdoc_front/pages/conformidade-cidadao.js`

- [ ] **Step 1: Criar a página Next.js**

```js
// pages/conformidade-cidadao.js
import { Grid } from '@mui/material';
import ConformidadeCidadao from '../src/components/conformidadeCidadao';
import AuthGuard from '../src/components/authGuard';

const ConformidadeCidadaoPage = () => {
    return (
        <AuthGuard>
            <Grid container spacing={2} sx={{ p: 2 }}>
                <Grid item xs={12}>
                    <ConformidadeCidadao />
                </Grid>
            </Grid>
        </AuthGuard>
    );
};

export default ConformidadeCidadaoPage;
```

- [ ] **Step 2: Rodar o frontend e verificar a página**

```bash
cd sysdoc_front && npm run dev
```

Acessar `http://localhost:3000/conformidade-cidadao`. Esperado: página com card "Conformidade de Cidadãos — e-SUS PEC" e botão "Analisar agora".

- [ ] **Step 3: Commit**

```bash
git add pages/conformidade-cidadao.js
git commit -m "feat: página conformidade-cidadao"
```

---

---

## Task 11: Debug e Fix — Óbitos + Exposição de Erros

**Files:**
- Modify: `sysdoc_back/app/Services/ConformidadeCidadaoService.php`
- Modify: `sysdoc_back/app/Http/Controllers/ConformidadeCidadaoController.php`
- Modify: `sysdoc_back/routes/api.php`
- Modify: `sysdoc_front/src/components/conformidadeCidadao/index.js`
- Modify: `sysdoc_front/src/services/conformidadeCidadaoApi.js`

- [ ] **Step 1: Diagnosticar colunas reais do e-SUS**

Conectar ao PostgreSQL do e-SUS e executar:

```sql
SELECT column_name
FROM information_schema.columns
WHERE table_name = 'tb_fat_cad_individual'
ORDER BY column_name;
```

Identificar qual coluna contém o flag de óbito e qual coluna contém a data de óbito. Atualizar a lista de candidatas em `resolveEsusCols()`:

```php
'st_faleceu' => $this->firstCol('tb_fat_cad_individual', [
    'st_faleceu', 'in_falecido', 'st_obito', 'tp_situacao_cadastro',
    /* adicionar aqui o nome real encontrado */
]),
'dt_obito' => $this->firstCol('tb_fat_cad_individual', [
    'dt_obito', 'dt_data_obito', 'dt_falecimento',
    /* adicionar aqui o nome real encontrado */
]),
```

- [ ] **Step 2: Adicionar log de diagnóstico no início de `analisar()`**

Logo após `$cols = $this->resolveEsusCols();`:

```php
Log::info('[ConformidadeCidadao] Colunas resolvidas', [
    'st_faleceu' => $cols['st_faleceu'],
    'dt_obito'   => $cols['dt_obito'],
    'dt_nasc'    => $cols['dt_nasc'],
    'logradouro' => $cols['logradouro'],
    'municipio'  => $cols['municipio'],
    'hasDom'     => $cols['hasDom'],
]);
```

- [ ] **Step 3: Persistir resumo de erros em `sincronizacoes_cidadao.erro_mensagem` ao concluir**

No final de `aplicar()`, antes do `$sync->update(['status' => 'completed', ...])`:

```php
$resumoErros = null;
if ($erros > 0) {
    $primeiros = $sync->itens()
        ->whereNotNull('erro')
        ->orderBy('id')
        ->limit(10)
        ->pluck('erro', 'nome_esus')
        ->map(fn($err, $nome) => "{$nome}: {$err}")
        ->implode(' | ');
    $resumoErros = substr("Erros ({$erros}): " . $primeiros, 0, 1000);
}
```

E incluir `'erro_mensagem' => $resumoErros` no array do `$sync->update`.

- [ ] **Step 4: Novo endpoint `GET /erros/{job_id}`**

Em `ConformidadeCidadaoController`:

```php
public function erros(Request $request, string $jobId): JsonResponse
{
    if (!$this->autorizado($request)) {
        return response()->json(['message' => 'Sem permissão.'], 403);
    }

    $sync = SincronizacaoCidadao::where('job_id', $jobId)->first();
    if (!$sync) {
        return response()->json(['message' => 'Sincronização não encontrada.'], 404);
    }

    $perPage = min(100, max(10, (int) $request->query('per_page', 50)));
    $page    = max(1, (int) $request->query('page', 1));

    $itens = SincronizacaoItem::where('sincronizacao_id', $sync->id)
        ->whereNotNull('erro')
        ->select(['id', 'acao', 'cpf', 'cns', 'nome_esus', 'client_id', 'erro'])
        ->forPage($page, $perPage)
        ->get();

    $total = SincronizacaoItem::where('sincronizacao_id', $sync->id)->whereNotNull('erro')->count();

    return response()->json([
        'data' => $itens,
        'meta' => [
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int) ceil($total / $perPage)),
        ],
    ]);
}
```

Em `routes/api.php`, dentro do grupo `conformidade-cidadao`:

```php
Route::get('erros/{job_id}', [ConformidadeCidadaoController::class, 'erros']);
```

- [ ] **Step 5: Frontend — exibir erros após conclusão**

Em `conformidadeCidadaoApi.js`, adicionar:

```js
erros: (jobId, page = 1, perPage = 50) =>
    api.get(`${BASE}/erros/${jobId}`, { params: { page, per_page: perPage } }).then(r => r.data),
```

No componente `ConformidadeCidadao`, dentro do bloco `fase === 'done'`, se `syncData.result_erros > 0`, exibir tabela com os primeiros erros (carregar via `conformidadeCidadaoApi.erros(jobId)`):

```js
// Estado adicional
const [errosDetalhe, setErrosDetalhe] = useState([]);

// No handleAplicar, após concluído:
if (syncData.result_erros > 0) {
    const errosData = await conformidadeCidadaoApi.erros(jobId);
    setErrosDetalhe(errosData.data);
}
```

Tabela de erros:
- Colunas: Nome (e-SUS), CPF, Ação, Motivo do erro
- Botão "Exportar PDF" (abre `window.print()` ou chama endpoint de PDF)

- [ ] **Step 6: Commit**

```bash
git add app/Services/ConformidadeCidadaoService.php \
        app/Http/Controllers/ConformidadeCidadaoController.php \
        routes/api.php
git commit -m "fix: diagnóstico de colunas e-SUS, log de óbitos, endpoint de erros"

cd ../sysdoc_front
git add src/services/conformidadeCidadaoApi.js src/components/conformidadeCidadao/index.js
git commit -m "feat: exibir erros detalhados após sincronização com falhas"
```

---

## Task 12: Fix — Endereço, born_date e Cidade Padrão

**Files:**
- Modify: `sysdoc_back/app/Services/ConformidadeCidadaoService.php`

- [ ] **Step 1: Garantir que born_date null no Sysdoc entra no diff**

Em `buildDiffPayload()`, a lógica atual já detecta a diferença quando `$client->born_date` é null e o e-SUS tem data — pois `null !== '2000-01-01'`. Verificar se o bloqueio está na condição de timestamp:

```php
// Bloco atual — só processa se e-SUS for mais recente:
if ($esusUpdated && $sysdocUpdated && $esusUpdated->lte($sysdocUpdated)) {
    $semAlteracao++;
    continue;
}
```

Quando `$client->born_date` é null, `$client->updated_at` pode ser muito antigo, mas o timestamp do e-SUS também pode ser antigo. Solução: **ignorar o filtro de timestamp quando campos obrigatórios (`born_date`) estiverem nulos no Sysdoc**:

```php
$temCampoNulo = !$client->born_date;

if (!$temCampoNulo && $esusUpdated && $sysdocUpdated && $esusUpdated->lte($sysdocUpdated)) {
    $semAlteracao++;
    continue;
}
```

- [ ] **Step 2: Cidade padrão "Ilicínea"**

Em `buildCreatePayload()`, na montagem do endereço:

```php
'city' => $row['municipio'] ?: 'Ilicínea',
```

Em `buildDiffPayload()`, na comparação de cidade:

```php
$cidadeEsus = ($row['municipio'] ?? null) ?: 'Ilicínea';
if ($cidadeEsus !== $addr?->city) {
    $addrDiff['city'] = ['de' => $addr?->city, 'para' => $cidadeEsus];
}
```

Remover a condição `if (($row['municipio'] ?? null) && ...)` para `city` — substituir por comparação com fallback.

- [ ] **Step 3: Diagnóstico se `tb_fat_cad_domiciliar` não existe**

Em `resolveEsusCols()`, se `$hasDom = false`, adicionar log:

```php
if (!$hasDom) {
    Log::warning('[ConformidadeCidadao] tb_fat_cad_domiciliar não encontrada — endereços não serão sincronizados');
}
```

Verificar se a tabela existe na instalação local. Se não existir, avaliar se os dados de endereço estão em outra tabela (ex.: `tb_fat_cad_individual` com colunas de endereço inline) e expandir `resolveEsusCols()` para buscar nesses candidatos alternativos.

- [ ] **Step 4: Commit**

```bash
git add app/Services/ConformidadeCidadaoService.php
git commit -m "fix: born_date nulo entra no diff; cidade padrão Ilicínea; log de endereço"
```

---

## Task 13: Feature — Sincronizar Todos os Campos da Tabela clients

**Files:**
- Modify: `sysdoc_back/app/Services/ConformidadeCidadaoService.php`

- [ ] **Step 1: Listar os campos atuais da tabela clients**

Executar no MySQL do Sysdoc:

```sql
DESCRIBE clients;
```

Identificar todos os campos que podem ter correspondência no e-SUS. Candidatos além dos já sincronizados:

| Campo clients | Coluna e-SUS candidata |
|---|---|
| `raca_cor` | `co_raca_cor`, `tp_raca_cor` |
| `sexo` | `co_sexo` (já em criar; adicionar em atualizar) |
| `st_falecido` | `st_faleceu` (mesmo da Task 11) |
| `escolaridade` | `co_nivel_escolaridade` |
| `nacionalidade` | `co_nacionalidade_cidadao` |
| `situacao_usuario` | `tp_situacao_usuario_cidadao` |

- [ ] **Step 2: Expandir `resolveEsusCols()` com as novas colunas**

```php
'co_sexo'        => $this->firstCol('tb_fat_cad_individual', ['co_sexo', 'tp_sexo']),
'raca_cor'       => $this->firstCol('tb_fat_cad_individual', ['co_raca_cor', 'tp_raca_cor']),
'escolaridade'   => $this->firstCol('tb_fat_cad_individual', ['co_nivel_escolaridade', 'tp_nivel_escolaridade']),
'nacionalidade'  => $this->firstCol('tb_fat_cad_individual', ['co_nacionalidade_cidadao', 'tp_nacionalidade']),
```

- [ ] **Step 3: Expandir `chunkEsus()` para selecionar as novas colunas**

Adicionar as expressões no SELECT da query:

```php
$sexoExpr    = $cols['co_sexo']      ? "fci.{$cols['co_sexo']}"      : 'NULL';
$racaExpr    = $cols['raca_cor']     ? "fci.{$cols['raca_cor']}"     : 'NULL';
$escolaExpr  = $cols['escolaridade'] ? "fci.{$cols['escolaridade']}" : 'NULL';
$naciExpr    = $cols['nacionalidade']? "fci.{$cols['nacionalidade']}" : 'NULL';
```

Incluir no SELECT:
```sql
{$sexoExpr}   AS co_sexo,
{$racaExpr}   AS raca_cor,
{$escolaExpr} AS escolaridade,
{$naciExpr}   AS nacionalidade,
```

- [ ] **Step 4: Mapeamento de valores e-SUS → Sysdoc**

Criar método privado de mapeamento:

```php
private function mapSexo(?string $coSexo): string
{
    return match (strtoupper((string) $coSexo)) {
        'M', '1' => 'MASCULINE',
        'F', '2' => 'FEMININE',
        default  => 'INDETERMINATE',
    };
}

private function mapRacaCor(?string $co): ?string
{
    // Mapear conforme tabela RNDS: 01=Branca, 02=Preta, 03=Parda, 04=Amarela, 05=Indígena, 99=Sem informação
    return match ((string) $co) {
        '01' => 'BRANCA',
        '02' => 'PRETA',
        '03' => 'PARDA',
        '04' => 'AMARELA',
        '05' => 'INDIGENA',
        default => null,
    };
}
```

- [ ] **Step 5: Atualizar `buildDiffPayload()` com novos campos**

```php
// Sexo
$sexoEsus = $this->mapSexo($row['co_sexo'] ?? null);
if ($sexoEsus !== 'INDETERMINATE' && $sexoEsus !== $client->sexo) {
    $diff['sexo'] = ['de' => $client->sexo, 'para' => $sexoEsus];
}

// Raça/Cor
$racaEsus = $this->mapRacaCor($row['raca_cor'] ?? null);
if ($racaEsus && $racaEsus !== $client->raca_cor) {
    $diff['raca_cor'] = ['de' => $client->raca_cor, 'para' => $racaEsus];
}
```

- [ ] **Step 6: Atualizar `buildCreatePayload()` com novos campos**

```php
'sexo'     => $this->mapSexo($row['co_sexo'] ?? null),
'raca_cor' => $this->mapRacaCor($row['raca_cor'] ?? null),
```

- [ ] **Step 7: Atualizar `aplicarAtualizar()` para incluir novos campos**

```php
if (isset($payload['sexo']))     $clientData['sexo']     = $payload['sexo']['para'];
if (isset($payload['raca_cor'])) $clientData['raca_cor'] = $payload['raca_cor']['para'];
```

- [ ] **Step 8: Commit**

```bash
git add app/Services/ConformidadeCidadaoService.php
git commit -m "feat: sincroniza sexo e raca_cor do e-SUS; mapeia todos os campos disponíveis"
```

---

## Self-Review

### Cobertura da spec

| Requisito da spec | Task que implementa |
|---|---|
| Tabela sincronizacoes_cidadao | Task 1 |
| Tabela sincronizacao_itens | Task 1 |
| Models com relacionamentos | Task 2 |
| Rota POST /analisar | Task 6 |
| Rota GET /status/{job_id} | Task 6 |
| Rota POST /aplicar/{job_id} | Task 6 |
| Rota GET /historico | Task 6 |
| Job SincronizacaoCidadaoJob | Task 5 |
| Job AplicarSincronizacaoJob | Task 5 |
| Match CPF → CNS fallback | Task 4 (Service::analisar) |
| Chunk e-SUS 500 registros | Task 4 (chunkEsus) |
| Detecção de colunas e-SUS | Task 4 (resolveEsusCols) |
| Ação criar | Task 4 (aplicarCriar) |
| Ação atualizar | Task 4 (aplicarAtualizar) |
| Ação obito + inativar filas | Task 4 (aplicarObito) |
| Permissão configurável | Task 6 (PagePermissionService) |
| Registro em system_pages | Task 7 |
| 409 sync em andamento | Task 6 |
| 409 aplicar sem preview_ready | Task 6 |
| Fallback QUEUE_CONNECTION=sync | Automático: Job roda sync |
| Frontend 5 estados | Task 9 |
| Polling 3s | Task 9 (iniciarPolling) |
| Cards de prévia | Task 9 |
| Tabela paginada de itens | Task 9 |
| Histórico | Task 9 + Task 6 |

### Sem placeholders ✓

### Consistência de tipos ✓

- `SincronizacaoCidadao` criado em Task 2, usado identicamente em Tasks 4, 5, 6
- `SincronizacaoItem` criado em Task 2, usado identicamente em Task 4 (`SincronizacaoItem::insert`) e Task 4 service
- `conformidadeCidadaoApi` criado em Task 8, importado identicamente em Task 9
- `ConformidadeCidadao` componente criado em Task 9, importado identicamente em Task 10
