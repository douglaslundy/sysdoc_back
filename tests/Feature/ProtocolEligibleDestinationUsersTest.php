<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\ProtocolOrganizationalUnit;
use App\Models\ProtocolUserUnit;
use App\Models\SystemPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtocolEligibleDestinationUsersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ProtocolOrganizationalUnit $secretaria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $this->secretaria = ProtocolOrganizationalUnit::create([
            'tipo' => 'secretaria',
            'codigo' => 'SEC-SAUDE',
            'nome' => 'Secretaria de Saude',
            'ativo' => true,
        ]);
    }

    private function linkUserToUnit(User $user, ProtocolOrganizationalUnit $unit): void
    {
        ProtocolUserUnit::create([
            'user_id' => $user->id,
            'protocol_organizational_unit_id' => $unit->id,
            'papel' => 'membro',
            'ativo' => true,
        ]);
    }

    private function grantProtocolPageAccess(string $profileSlug, string $path = '/protocolo'): void
    {
        $page = SystemPage::firstOrCreate(
            ['path' => $path],
            ['titulo' => $path, 'icone' => 'inbox', 'ordem' => 1, 'ativo' => true]
        );

        $profile = AccessProfile::firstOrCreate(
            ['slug' => $profileSlug],
            ['nome' => $profileSlug, 'ativo' => true]
        );

        $profile->pages()->syncWithoutDetaching([$page->id]);
    }

    public function test_exclui_usuario_da_unidade_cujo_perfil_nao_tem_acesso_a_pagina_de_protocolo(): void
    {
        $usuarioSemAcesso = User::factory()->create(['profile' => 'user_sem_protocolo', 'active' => true]);
        $this->linkUserToUnit($usuarioSemAcesso, $this->secretaria);
        // Perfil "user_sem_protocolo" nunca recebe a pagina /protocolo.
        AccessProfile::firstOrCreate(['slug' => 'user_sem_protocolo'], ['nome' => 'user_sem_protocolo', 'ativo' => true]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/protocolos/usuarios-elegiveis?unit_id={$this->secretaria->id}");

        $response->assertOk();
        $response->assertJsonMissing(['id' => $usuarioSemAcesso->id]);
    }

    public function test_inclui_usuario_da_unidade_cujo_perfil_tem_acesso_a_pagina_de_protocolo(): void
    {
        $usuarioComAcesso = User::factory()->create(['profile' => 'user_com_protocolo', 'active' => true]);
        $this->linkUserToUnit($usuarioComAcesso, $this->secretaria);
        $this->grantProtocolPageAccess('user_com_protocolo');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/protocolos/usuarios-elegiveis?unit_id={$this->secretaria->id}");

        $response->assertOk();
        $response->assertJsonFragment(['id' => $usuarioComAcesso->id, 'name' => $usuarioComAcesso->name]);
    }

    public function test_inclui_usuario_com_acesso_a_pagina_mesmo_sem_lotacao_em_unidade(): void
    {
        $usuarioSemLotacao = User::factory()->create(['profile' => 'user_com_protocolo', 'active' => true]);
        $this->grantProtocolPageAccess('user_com_protocolo');
        // Nenhum ProtocolUserUnit criado para este usuario.

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/protocolos/usuarios-elegiveis?unit_id={$this->secretaria->id}");

        $response->assertOk();
        $response->assertJsonFragment(['id' => $usuarioSemLotacao->id, 'name' => $usuarioSemLotacao->name]);
    }

    public function test_inclui_usuario_cujo_perfil_so_tem_uma_subpagina_do_protocolo(): void
    {
        // Perfil tem "Caixa de Entrada" mas NAO a pagina raiz "/protocolo".
        $usuario = User::factory()->create(['profile' => 'atendente_protocolo', 'active' => true]);
        $this->grantProtocolPageAccess('atendente_protocolo', '/protocolo/caixa-entrada');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/protocolos/usuarios-elegiveis?unit_id={$this->secretaria->id}");

        $response->assertOk();
        $response->assertJsonFragment(['id' => $usuario->id, 'name' => $usuario->name]);
    }

    public function test_exclui_usuario_inativo_mesmo_com_acesso_a_pagina(): void
    {
        $usuarioInativo = User::factory()->create(['profile' => 'user_com_protocolo', 'active' => false]);
        $this->grantProtocolPageAccess('user_com_protocolo');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/protocolos/usuarios-elegiveis?unit_id={$this->secretaria->id}");

        $response->assertOk();
        $response->assertJsonMissing(['id' => $usuarioInativo->id]);
    }

    public function test_inclui_usuario_de_unidade_filha_da_secretaria_selecionada(): void
    {
        $departamento = ProtocolOrganizationalUnit::create([
            'parent_id' => $this->secretaria->id,
            'tipo' => 'departamento',
            'codigo' => 'DEPTO-VIGILANCIA',
            'nome' => 'Vigilancia Sanitaria',
            'ativo' => true,
        ]);

        $usuarioDoDepartamento = User::factory()->create(['profile' => 'user_departamento', 'active' => true]);
        $this->linkUserToUnit($usuarioDoDepartamento, $departamento);
        $this->grantProtocolPageAccess('user_departamento');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/protocolos/usuarios-elegiveis?unit_id={$this->secretaria->id}");

        $response->assertOk();
        $response->assertJsonFragment(['id' => $usuarioDoDepartamento->id]);
    }
}
