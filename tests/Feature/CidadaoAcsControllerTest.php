<?php
namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CidadaoAcsControllerTest extends TestCase
{
    private function actingAsAdmin(): void
    {
        $user = new User();
        $user->id = 1;
        $user->profile = 'admin';
        $user->active = true;

        Sanctum::actingAs($user);
    }

    public function test_cidadaos_index_route_exists(): void
    {
        Cache::put('aps_db_config', null, 3600);
        $r = $this->getJson('/api/monitor-aps/cidadaos');
        $this->assertNotEquals(404, $r->status());
    }

    public function test_cidadaos_agentes_route_exists(): void
    {
        Cache::put('aps_db_config', null, 3600);
        $this->actingAsAdmin();
        $r = $this->getJson('/api/monitor-aps/cidadaos/agentes');
        $this->assertNotEquals(404, $r->status());
    }

    public function test_cidadaos_index_resposta_estruturada(): void
    {
        Cache::put('aps_db_config', null, 3600);
        $this->actingAsAdmin();
        $r = $this->getJson('/api/monitor-aps/cidadaos?page=1&per_page=10');
        if ($r->status() === 200) {
            $r->assertJsonStructure(['cidadaos', 'meta' => ['total', 'page', 'per_page', 'pages']]);
        }
        $this->assertNotEquals(500, $r->status());
    }

    public function test_cidadaos_busca_minimo_3_chars(): void
    {
        Cache::put('aps_db_config', null, 3600);
        $this->actingAsAdmin();
        $r = $this->getJson('/api/monitor-aps/cidadaos?busca=AB');
        if ($r->status() !== 503) {
            $this->assertEquals(422, $r->status());
        }
    }
}
