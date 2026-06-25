<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\ProtocolOrganizationalUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProtocolUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_cadastro_de_usuario_persiste_vinculo_organizacional(): void
    {
        AccessProfile::create([
            'nome' => 'Usuário',
            'slug' => 'user',
            'ativo' => true,
        ]);
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $user = User::factory()->create(['profile' => 'user', 'active' => true, 'cpf' => '52998224725']);
        $unit = ProtocolOrganizationalUnit::create([
            'tipo' => 'secretaria',
            'nome' => 'Secretaria de Administração',
            'ativo' => true,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/users/{$user->id}", [
                'profile' => 'user',
                'name' => $user->name,
                'email' => $user->email,
                'cpf' => $user->cpf,
                'active' => true,
                'protocol_unit_ids' => [$unit->id],
            ])
            ->assertOk()
            ->assertJsonPath('user.protocol_unit_ids.0', $unit->id);

        $this->assertDatabaseHas('protocol_user_units', [
            'user_id' => $user->id,
            'protocol_organizational_unit_id' => $unit->id,
            'papel' => 'lotacao',
            'ativo' => true,
        ]);
    }
}
