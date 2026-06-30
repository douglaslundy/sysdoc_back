<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientDuplicateCleanupTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);
    }

    public function test_lista_candidato_excluivel_de_client_duplicado_por_cpf(): void
    {
        $keeperId = $this->createClient([
            'name' => 'Cliente Principal',
            'cpf' => '11122233344',
            'cns' => '700000000000001',
        ]);
        $candidateId = $this->createClient([
            'name' => 'Cliente Duplicado',
            'cpf' => '11122233344',
            'cns' => '700000000000002',
        ]);

        $specialityId = $this->createSpeciality();
        $this->createQueue($keeperId, $specialityId);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/clients/duplicates?type=cpf');

        $response->assertOk()
            ->assertJsonPath('summary.groups', 1)
            ->assertJsonPath('summary.deletable_candidates', 1)
            ->assertJsonPath('groups.0.identifier_type', 'cpf')
            ->assertJsonPath('groups.0.identifier_value', '11122233344')
            ->assertJsonPath('groups.0.keeper.id', $keeperId)
            ->assertJsonPath('groups.0.deletable_candidates.0.id', $candidateId);
    }

    public function test_nao_lista_cns_placeholder_como_duplicado_excluivel(): void
    {
        $this->createClient([
            'name' => 'Cliente Placeholder 1',
            'cpf' => '11111111111',
            'cns' => '000000000000000',
        ]);
        $this->createClient([
            'name' => 'Cliente Placeholder 2',
            'cpf' => '22222222222',
            'cns' => '000000000000000',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/clients/duplicates?type=cns');

        $response->assertOk()
            ->assertJsonPath('summary.groups', 0)
            ->assertJsonPath('summary.deletable_candidates', 0)
            ->assertJsonCount(0, 'groups');
    }

    public function test_exclui_fisicamente_apenas_o_duplicado_sem_vinculos(): void
    {
        $keeperId = $this->createClient([
            'name' => 'Cliente Vinculado',
            'cpf' => '99988877766',
            'cns' => '700000000000010',
        ]);
        $candidateId = $this->createClient([
            'name' => 'Cliente Sem Vinculos',
            'cpf' => '99988877766',
            'cns' => '700000000000011',
        ]);

        $specialityId = $this->createSpeciality();
        $this->createQueue($keeperId, $specialityId);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/clients/duplicates', [
                'ids' => [$candidateId],
            ]);

        $response->assertOk()
            ->assertJsonPath('deleted_count', 1)
            ->assertJsonPath('deleted_ids.0', $candidateId);

        $this->assertDatabaseHas('clients', ['id' => $keeperId]);
        $this->assertDatabaseMissing('clients', ['id' => $candidateId]);
        $this->assertDatabaseMissing('addresses', ['id_client' => $candidateId]);
    }

    public function test_rejeita_exclusao_do_registro_principal_do_grupo(): void
    {
        $keeperId = $this->createClient([
            'name' => 'Cliente Principal',
            'cpf' => '12312312312',
            'cns' => '700000000000020',
        ]);
        $this->createClient([
            'name' => 'Cliente Secundario',
            'cpf' => '12312312312',
            'cns' => '700000000000021',
        ]);

        $specialityId = $this->createSpeciality();
        $this->createQueue($keeperId, $specialityId);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/clients/duplicates', [
                'ids' => [$keeperId],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('invalid_ids.0', $keeperId);

        $this->assertDatabaseHas('clients', ['id' => $keeperId]);
    }

    private function createClient(array $overrides = []): int
    {
        $data = array_merge([
            'name' => 'Cliente Teste',
            'mother' => 'Mae Teste',
            'father' => null,
            'cns' => null,
            'cpf' => fake()->numerify('###########'),
            'email' => null,
            'phone' => null,
            'obs' => null,
            'born_date' => '2000-01-01',
            'sexo' => 'FEMININE',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        $id = DB::table('clients')->insertGetId($data);

        DB::table('addresses')->insert([
            'city' => 'Cidade',
            'street' => 'Rua 1',
            'number' => '10',
            'complement' => null,
            'zip_code' => '12345-678',
            'district' => 'Centro',
            'id_client' => $id,
            'active' => 1,
        ]);

        return $id;
    }

    private function createSpeciality(): int
    {
        return DB::table('specialities')->insertGetId([
            'id_user' => $this->admin->id,
            'name' => 'Especialidade Teste',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createQueue(int $clientId, int $specialityId): void
    {
        DB::table('queue')->insert([
            'date_of_received' => now(),
            'id_client' => $clientId,
            'id_specialities' => $specialityId,
            'id_user' => $this->admin->id,
            'done' => false,
            'date_of_realized' => null,
            'urgency' => false,
            'obs' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}