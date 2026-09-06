<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\Client;
use App\Models\Speciality;
use App\Models\SystemPage;
use App\Models\User;
use App\Models\UserSpecialityPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class QueueSpecialityPermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $limited;

    private Speciality $fisio;

    private Speciality $fono;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $profile = AccessProfile::create([
            'nome' => 'Usuario Fila',
            'slug' => 'user',
            'ativo' => true,
        ]);
        $page = SystemPage::firstOrCreate(
            ['path' => '/queue'],
            ['titulo' => 'Fila', 'ativo' => true]
        );
        $profile->pages()->attach($page->id);

        $this->limited = User::factory()->create(['profile' => 'user', 'active' => true]);
        $this->fisio = Speciality::create(['id_user' => $this->admin->id, 'name' => 'Fisioterapia']);
        $this->fono = Speciality::create(['id_user' => $this->admin->id, 'name' => 'Fonoaudiologia']);

        $this->client = Client::create([
            'name' => 'Paciente Teste',
            'mother' => 'Mae Teste',
            'cpf' => '123.456.789-00',
            'born_date' => '1990-01-01',
            'active' => true,
        ]);

        // $limited só pode VER e INSERIR em Fisioterapia; nenhum acesso a Fonoaudiologia.
        UserSpecialityPermission::create([
            'user_id' => $this->limited->id,
            'speciality_id' => $this->fisio->id,
            'can_view' => true,
            'can_edit' => false,
            'can_insert' => true,
        ]);
    }

    private function insertQueueRow(Speciality $speciality): int
    {
        DB::table('queue')->insert([
            'uuid' => (string) Str::uuid(),
            'id_client' => $this->client->id,
            'id_specialities' => $speciality->id,
            'id_user' => $this->admin->id,
            'done' => false,
            'urgency' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('queue')->max('id');
    }

    public function test_listagem_so_traz_especialidades_com_can_view(): void
    {
        $this->insertQueueRow($this->fisio);
        $this->insertQueueRow($this->fono);

        $response = $this->actingAs($this->limited, 'sanctum')->getJson('/api/queues?per_page=50');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id_specialities')->all();
        $this->assertContains($this->fisio->id, $ids);
        $this->assertNotContains($this->fono->id, $ids);
    }

    public function test_criar_em_especialidade_sem_can_insert_retorna_403(): void
    {
        $response = $this->actingAs($this->limited, 'sanctum')->postJson('/api/queues', [
            'id_client' => $this->client->id,
            'id_specialities' => $this->fono->id,
            'id_user' => $this->limited->id,
            'urgency' => false,
        ]);

        $response->assertStatus(403);
    }

    public function test_criar_em_especialidade_com_can_insert_funciona(): void
    {
        $response = $this->actingAs($this->limited, 'sanctum')->postJson('/api/queues', [
            'id_client' => $this->client->id,
            'id_specialities' => $this->fisio->id,
            'id_user' => $this->limited->id,
            'urgency' => false,
        ]);

        $response->assertStatus(201);
    }

    public function test_editar_em_especialidade_sem_can_edit_retorna_403(): void
    {
        $queueId = $this->insertQueueRow($this->fisio);

        $response = $this->actingAs($this->limited, 'sanctum')->putJson("/api/queues/{$queueId}", [
            'obs' => 'tentando editar',
        ]);

        $response->assertStatus(403);
    }

    public function test_excluir_em_especialidade_sem_can_edit_retorna_403(): void
    {
        $queueId = $this->insertQueueRow($this->fisio);

        $response = $this->actingAs($this->limited, 'sanctum')->deleteJson("/api/queues/{$queueId}");

        $response->assertStatus(403);
    }

    public function test_admin_nunca_e_bloqueado(): void
    {
        $queueId = $this->insertQueueRow($this->fono);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/queues/{$queueId}", ['obs' => 'admin edita'])
            ->assertOk();
    }

    public function test_specialities_options_retorna_flags_por_especialidade(): void
    {
        $response = $this->actingAs($this->limited, 'sanctum')->getJson('/api/queues/specialities-options');

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $this->fisio->id,
            'name' => 'Fisioterapia',
            'can_view' => true,
            'can_edit' => false,
            'can_insert' => true,
        ]);
        $response->assertJsonFragment([
            'id' => $this->fono->id,
            'name' => 'Fonoaudiologia',
            'can_view' => false,
            'can_edit' => false,
            'can_insert' => false,
        ]);
    }
}
