<?php

namespace Tests\Feature;

use App\Models\Speciality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialityAllowsSessionSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_especialidade_com_flag_ligada(): void
    {
        $user = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/specialities', [
            'id_user' => $user->id,
            'name' => 'Fisioterapia',
            'allows_session_scheduling' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('allows_session_scheduling', true);
        $this->assertDatabaseHas('specialities', [
            'name' => 'Fisioterapia',
            'allows_session_scheduling' => 1,
        ]);
    }

    public function test_especialidade_sem_informar_a_flag_fica_desligada_por_padrao(): void
    {
        $user = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/specialities', [
            'id_user' => $user->id,
            'name' => 'Cardiologia',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('specialities', [
            'name' => 'Cardiologia',
            'allows_session_scheduling' => 0,
        ]);
    }

    public function test_atualiza_flag_de_especialidade_existente(): void
    {
        $user = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $speciality = Speciality::create(['id_user' => $user->id, 'name' => 'Fonoaudiologia']);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/specialities/{$speciality->id}", [
            'id_user' => $user->id,
            'name' => 'Fonoaudiologia',
            'allows_session_scheduling' => true,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('specialities', [
            'id' => $speciality->id,
            'allows_session_scheduling' => 1,
        ]);
    }
}
