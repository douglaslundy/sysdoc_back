<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\SystemPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SensitiveRoutesAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, array{0: string, 1: string, 2: array}> */
    private function protectedCalls(): array
    {
        return [
            ['GET', '/api/email/config', []],
            ['POST', '/api/email/config', ['smtp_host' => 'x.example.com']],
            ['GET', '/api/whatsapp/config', []],
            ['POST', '/api/whatsapp/config', []],
            ['GET', '/api/errorlogs', []],
            ['GET', '/api/protocolos/configuracoes', []],
            ['PUT', '/api/protocolos/configuracoes', ['default_due_days' => 1]],
            ['POST', '/api/protocolos/tipos', ['codigo' => 'zzz', 'nome' => 'ZZZ']],
            ['POST', '/api/protocolos/unidades-organizacionais', ['tipo' => 'secretaria', 'nome' => 'X']],
            ['GET', '/api/protocolos/alertas', []],
        ];
    }

    public function test_usuario_comum_sem_permissao_nao_acessa_configuracoes_sensiveis(): void
    {
        $user = User::factory()->create(['profile' => 'user', 'active' => true]);

        foreach ($this->protectedCalls() as [$method, $uri, $data]) {
            $this->app['auth']->forgetGuards();
            $this->actingAs($user, 'sanctum')->json($method, $uri, $data)
                ->assertStatus(403, "$method $uri deveria exigir permissao da pagina");
        }
    }

    public function test_administrador_continua_acessando(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        foreach ([['GET', '/api/email/config'], ['GET', '/api/whatsapp/config'], ['GET', '/api/errorlogs'], ['GET', '/api/protocolos/configuracoes'], ['GET', '/api/protocolos/alertas']] as [$method, $uri]) {
            $this->app['auth']->forgetGuards();
            $this->actingAs($admin, 'sanctum')->json($method, $uri)->assertOk();
        }
    }

    public function test_perfil_com_a_pagina_liberada_acessa(): void
    {
        $page = SystemPage::create(['titulo' => 'E-mail', 'path' => '/configuracoes/email', 'icone' => 'mail', 'ordem' => 1, 'ativo' => true]);
        $profile = AccessProfile::create(['nome' => 'Gestor', 'slug' => 'gestor', 'ativo' => true]);
        $profile->pages()->sync([$page->id]);
        $user = User::factory()->create(['profile' => 'gestor', 'active' => true]);

        $this->actingAs($user, 'sanctum')->getJson('/api/email/config')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->actingAs($user, 'sanctum')->getJson('/api/whatsapp/config')->assertStatus(403);
    }

    public function test_leituras_usadas_pelo_formulario_de_protocolo_continuam_abertas(): void
    {
        $user = User::factory()->create(['profile' => 'user', 'active' => true]);

        foreach (['/api/protocolos/tipos', '/api/protocolos/unidades-organizacionais', '/api/protocolos/contexto-novo'] as $uri) {
            $this->app['auth']->forgetGuards();
            $this->actingAs($user, 'sanctum')->getJson($uri)->assertOk();
        }
    }

    public function test_cadastro_publico_de_contas_nao_existe(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Intruso',
            'email' => 'intruso@example.com',
            'cpf' => '52998224725',
            'password' => 'senha-forte-123',
            'password_confirm' => 'senha-forte-123',
        ]);

        $this->assertContains($response->status(), [404, 405]);
        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);
    }
}
