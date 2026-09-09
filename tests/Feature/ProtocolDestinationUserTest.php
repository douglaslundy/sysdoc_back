<?php

namespace Tests\Feature;

use App\Models\ProtocolOrganizationalUnit;
use App\Models\ProtocolType;
use App\Models\ProtocolUserUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtocolDestinationUserTest extends TestCase
{
    use RefreshDatabase;

    private function seedType(): void
    {
        ProtocolType::firstOrCreate(['codigo' => 'administrativo'], ['nome' => 'Administrativo', 'ativo' => true, 'ordem' => 1]);
    }

    private function secretaria(string $nome, string $codigo): ProtocolOrganizationalUnit
    {
        return ProtocolOrganizationalUnit::create([
            'tipo' => 'secretaria',
            'codigo' => $codigo,
            'nome' => $nome,
            'ativo' => true,
        ]);
    }

    public function test_cria_protocolo_para_usuario_sem_lotacao_quando_a_secretaria_e_informada(): void
    {
        $this->seedType();
        $autor = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $destino = User::factory()->create(['profile' => 'user', 'active' => true]); // sem ProtocolUserUnit
        $secretaria = $this->secretaria('Secretaria de Saude', 'SEC-SAUDE');

        $this->actingAs($autor, 'sanctum')
            ->postJson('/api/protocolos', [
                'assunto' => 'Protocolo para usuario sem lotacao',
                'tipo' => 'administrativo',
                'origem_unit_id' => $secretaria->id,
                'destino_unit_id' => $secretaria->id,
                'destino_user_id' => $destino->id,
            ])
            ->assertCreated()
            ->assertJsonPath('responsavel_atual_id', $destino->id)
            ->assertJsonPath('destino_unit_id', $secretaria->id);
    }

    public function test_rejeita_usuario_lotado_em_outra_secretaria(): void
    {
        $this->seedType();
        $autor = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $destino = User::factory()->create(['profile' => 'user', 'active' => true]);

        $saude = $this->secretaria('Secretaria de Saude', 'SEC-SAUDE');
        $educacao = $this->secretaria('Secretaria de Educacao', 'SEC-EDU');

        ProtocolUserUnit::create([
            'user_id' => $destino->id,
            'protocol_organizational_unit_id' => $educacao->id,
            'papel' => 'lotacao',
            'ativo' => true,
        ]);

        $this->actingAs($autor, 'sanctum')
            ->postJson('/api/protocolos', [
                'assunto' => 'Protocolo cruzando secretaria errada',
                'tipo' => 'administrativo',
                'origem_unit_id' => $saude->id,
                'destino_unit_id' => $saude->id,
                'destino_user_id' => $destino->id,
            ])
            ->assertStatus(422);
    }
}
