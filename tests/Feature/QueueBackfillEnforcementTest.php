<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\Client;
use App\Models\Speciality;
use App\Models\SystemPage;
use App\Models\User;
use App\Services\Authorization\UserSpecialityPermissionBackfiller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class QueueBackfillEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pre_existente_mantem_acesso_total_apos_backfill_via_http(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $profile = AccessProfile::create([
            'nome' => 'Recepção Backfill Enforcement',
            'slug' => 'recepcao-backfill-enforcement',
            'ativo' => true,
        ]);
        $page = SystemPage::firstOrCreate(
            ['path' => '/queue'],
            ['titulo' => 'Fila', 'ativo' => true]
        );
        $profile->pages()->attach($page->id);

        $recepcionista = User::factory()->create(['profile' => 'recepcao-backfill-enforcement', 'active' => true]);

        $fisio = Speciality::create(['id_user' => $admin->id, 'name' => 'Fisioterapia']);
        $fono = Speciality::create(['id_user' => $admin->id, 'name' => 'Fonoaudiologia']);

        $client = Client::create([
            'name' => 'Paciente Teste',
            'mother' => 'Mae Teste',
            'cpf' => '123.456.789-00',
            'born_date' => '1990-01-01',
            'active' => true,
        ]);

        foreach ([$fisio, $fono] as $speciality) {
            DB::table('queue')->insert([
                'uuid' => (string) Str::uuid(),
                'id_client' => $client->id,
                'id_specialities' => $speciality->id,
                'id_user' => $admin->id,
                'done' => false,
                'urgency' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        (new UserSpecialityPermissionBackfiller())->run();

        $response = $this->actingAs($recepcionista, 'sanctum')->getJson('/api/queues?per_page=50');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id_specialities')->all();
        $this->assertContains($fisio->id, $ids);
        $this->assertContains($fono->id, $ids);
    }
}
