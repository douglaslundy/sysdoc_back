<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClientHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true, 'name' => 'Admin']);
        $this->client = Client::create([
            'name' => 'Maria', 'mother' => 'Mae', 'cpf' => '111.222.333-44',
            'born_date' => '1990-01-01', 'active' => true,
        ]);
        AuditLog::query()->delete();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function log(string $action, string $model, array $old = null, array $new = null, ?int $clientId = null, string $userName = 'Admin'): AuditLog
    {
        return AuditLog::create([
            'user_id' => $this->admin->id, 'user_name' => $userName, 'action' => $action, 'model_type' => $model,
            'model_id' => 1, 'client_id' => $clientId ?? $this->client->id, 'endpoint' => 'api/x', 'method' => 'GET',
            'ip_address' => '127.0.0.1', 'old_values' => $old, 'new_values' => $new, 'created_at' => now(),
        ]);
    }

    private function history(User $user, int $clientId, string $query = '')
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($user, 'sanctum')->getJson("/api/clients/{$clientId}/historico{$query}");
    }

    public function test_admin_ve_os_eventos_do_cidadao_do_mais_recente_para_o_mais_antigo(): void
    {
        Carbon::setTestNow('2026-09-30 08:00:00');
        $this->log('VIEW', 'Client');
        Carbon::setTestNow('2026-09-30 09:00:00');
        $this->log('UPDATE', 'Client', ['name' => 'Maria'], ['name' => 'Maria Silva'], null, 'Operadora');

        $response = $this->history($this->admin, $this->client->id)->assertOk();

        $response->assertJsonPath('data.0.titulo', 'Editou o cadastro')
            ->assertJsonPath('data.0.usuario', 'Operadora')
            ->assertJsonPath('data.0.detalhe', 'Nome: Maria → Maria Silva')
            ->assertJsonPath('data.1.titulo', 'Visualizou o cadastro')
            ->assertJsonCount(2, 'data');
        $this->assertNotEmpty($response->json('data.0.data'));
    }

    public function test_nao_mistura_eventos_de_outro_cidadao(): void
    {
        $other = Client::create(['name' => 'Joao', 'mother' => 'M', 'cpf' => '999.888.777-66', 'born_date' => '1980-01-01', 'active' => true]);
        AuditLog::query()->delete();
        $this->log('VIEW', 'Client', null, null, $other->id);

        $this->history($this->admin, $this->client->id)->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_cidadao_sem_eventos_devolve_lista_vazia_e_inexistente_devolve_404(): void
    {
        $this->history($this->admin, $this->client->id)->assertOk()->assertJsonCount(0, 'data');
        $this->history($this->admin, 999999)->assertNotFound();
    }

    public function test_permissao_por_perfil(): void
    {
        $profile = AccessProfile::create(['nome' => 'Atendente', 'slug' => 'atendente', 'ativo' => true, 'client_history_view_enabled' => false]);
        $user = User::factory()->create(['profile' => 'atendente', 'active' => true]);
        $this->log('VIEW', 'Client');

        $this->history($user, $this->client->id)->assertForbidden();

        $profile->update(['client_history_view_enabled' => true]);
        $this->history($user, $this->client->id)->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_paginacao_de_30_por_pagina(): void
    {
        foreach (range(1, 35) as $ignored) {
            $this->log('VIEW', 'Client');
        }

        $this->history($this->admin, $this->client->id)->assertOk()
            ->assertJsonCount(30, 'data')->assertJsonPath('total', 35)->assertJsonPath('last_page', 2);
        $this->history($this->admin, $this->client->id, '?page=2')->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_permissao_aparece_em_my_permissions_e_e_gravada_no_perfil(): void
    {
        $profile = AccessProfile::create(['nome' => 'Atendente', 'slug' => 'atendente', 'ativo' => true, 'client_history_view_enabled' => true]);
        $user = User::factory()->create(['profile' => 'atendente', 'active' => true]);

        $this->app['auth']->forgetGuards();
        $this->actingAs($user, 'sanctum')->getJson('/api/auth/my-permissions')
            ->assertOk()->assertJsonPath('capabilities.client_history_view', true);

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/access-profiles/{$profile->id}", ['client_history_view_enabled' => false])
            ->assertOk();
        $this->assertFalse((bool) $profile->fresh()->client_history_view_enabled);
    }
}
