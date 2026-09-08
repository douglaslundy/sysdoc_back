<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientViewCapabilitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sempre_pode_ver_viagens_e_relatorio_do_cliente(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/auth/my-permissions');

        $response->assertOk();
        $response->assertJsonPath('capabilities.client_trips_view', true);
        $response->assertJsonPath('capabilities.client_report_view', true);
    }

    public function test_perfil_com_flags_ativas_ve_viagens_e_relatorio(): void
    {
        AccessProfile::create([
            'nome' => 'Perfil Com Acesso',
            'slug' => 'com-acesso',
            'ativo' => true,
            'client_trips_view_enabled' => true,
            'client_report_view_enabled' => true,
        ]);
        $usuario = User::factory()->create(['profile' => 'com-acesso', 'active' => true]);

        $response = $this->actingAs($usuario, 'sanctum')->getJson('/api/auth/my-permissions');

        $response->assertOk();
        $response->assertJsonPath('capabilities.client_trips_view', true);
        $response->assertJsonPath('capabilities.client_report_view', true);
    }

    public function test_perfil_sem_flags_nao_ve_viagens_nem_relatorio(): void
    {
        AccessProfile::create([
            'nome' => 'Perfil Sem Acesso',
            'slug' => 'sem-acesso',
            'ativo' => true,
        ]);
        $usuario = User::factory()->create(['profile' => 'sem-acesso', 'active' => true]);

        $response = $this->actingAs($usuario, 'sanctum')->getJson('/api/auth/my-permissions');

        $response->assertOk();
        $response->assertJsonPath('capabilities.client_trips_view', false);
        $response->assertJsonPath('capabilities.client_report_view', false);
    }
}
