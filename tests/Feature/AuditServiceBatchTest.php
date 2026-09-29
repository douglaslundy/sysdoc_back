<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditServiceBatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        AuditService::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        AuditService::flush();
        parent::tearDown();
    }

    private function makeClient(): Client
    {
        // withoutEvents: o ClientObserver tambem audita; aqui isolamos o AuditService.
        return Client::withoutEvents(fn () => Client::create([
            'name' => 'Cidadao', 'mother' => 'Mae', 'cpf' => '111.222.333-44',
            'born_date' => '1990-01-01', 'active' => true,
        ]));
    }

    public function test_varias_acoes_na_mesma_requisicao_viram_um_unico_insert(): void
    {
        config(['chat.defer_broadcast' => true]); // comportamento web: grava depois da resposta
        $client = $this->makeClient();

        DB::enableQueryLog();
        AuditService::record('UPDATE', $client, ['name' => 'A'], ['name' => 'B']);
        AuditService::record('VIEW', $client);

        $this->assertSame(0, AuditLog::count(), 'Ainda deve estar no buffer.');

        $this->app->terminate();

        $inserts = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_starts_with($q['query'], 'insert into `audit_logs`'));
        DB::disableQueryLog();

        $this->assertCount(1, $inserts);
        $this->assertSame(2, AuditLog::where('client_id', $client->id)->count());
    }

    public function test_em_console_grava_na_hora_e_registra_client_id(): void
    {
        $client = $this->makeClient();

        AuditService::record('VIEW', $client);

        $this->assertSame(1, AuditLog::where('client_id', $client->id)->where('model_type', 'Client')->count());
    }

    public function test_client_id_explicito_prevalece_e_model_sem_vinculo_fica_nulo(): void
    {
        AuditService::record('TRIP_CLIENT_ADDED', null, null, ['trip_id' => 1], null, 77);
        AuditService::record('LOGIN');

        $this->assertSame(77, (int) AuditLog::where('action', 'TRIP_CLIENT_ADDED')->value('client_id'));
        $this->assertNull(AuditLog::where('action', 'LOGIN')->value('client_id'));
    }

    public function test_segredos_aninhados_sao_mascarados(): void
    {
        AuditService::record('UPDATE', null, null, [
            'configuracao' => ['smtp_password' => 'segredo', 'smtp_host' => 'smtp.exemplo.com'],
            'api_key' => 'chave',
            'password' => 'senha',
        ]);

        $new = AuditLog::latest('id')->first()->new_values;

        $this->assertSame('[mascarado]', $new['configuracao']['smtp_password']);
        $this->assertSame('smtp.exemplo.com', $new['configuracao']['smtp_host']);
        $this->assertSame('[mascarado]', $new['api_key']);
        $this->assertSame('[mascarado]', $new['password']);
    }

    public function test_visualizacao_e_gravada_uma_vez_a_cada_10_minutos_por_usuario_e_cidadao(): void
    {
        $client = $this->makeClient();
        $a = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $b = User::factory()->create(['profile' => 'admin', 'active' => true]);

        Carbon::setTestNow('2026-09-30 08:00:00');
        $this->actingAs($a);
        AuditService::recordViewOncePer('VIEW', $client);
        AuditService::recordViewOncePer('VIEW', $client);
        AuditService::recordViewOncePer('VIEW', $client);
        $this->assertSame(1, AuditLog::where('action', 'VIEW')->count());

        $this->actingAs($b);
        AuditService::recordViewOncePer('VIEW', $client);
        $this->assertSame(2, AuditLog::where('action', 'VIEW')->count());

        Carbon::setTestNow('2026-09-30 08:10:01');
        $this->actingAs($a);
        AuditService::recordViewOncePer('VIEW', $client);
        $this->assertSame(3, AuditLog::where('action', 'VIEW')->count());
    }

    public function test_falha_ao_gravar_a_auditoria_nao_quebra_quem_chamou(): void
    {
        // Nome do usuario maior que a coluna (100): o INSERT falha em modo estrito.
        $tooLong = new User(['name' => str_repeat('a', 300)]);

        AuditService::record('UPDATE', null, null, ['x' => 1], $tooLong);

        $this->assertSame(0, AuditLog::count());
        $this->assertTrue(true, 'Nenhuma excecao foi lancada.');
    }
}
