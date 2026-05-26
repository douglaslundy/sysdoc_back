<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class CidadaoAcsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'profile' => 'admin',
            'active'  => true,
        ]);
    }

    public function test_cidadaos_index_route_exists(): void
    {
        Cache::put('aps_db_config', null, 3600);
        $r = $this->actingAs($this->user, 'sanctum')->getJson('/api/monitor-aps/cidadaos');
        $this->assertNotEquals(404, $r->status());
    }

    public function test_cidadaos_agentes_route_exists(): void
    {
        Cache::put('aps_db_config', null, 3600);
        $r = $this->actingAs($this->user, 'sanctum')->getJson('/api/monitor-aps/cidadaos/agentes');
        $this->assertNotEquals(404, $r->status());
    }

    public function test_cidadaos_index_resposta_estruturada(): void
    {
        Cache::put('aps_db_config', null, 3600);
        $r = $this->actingAs($this->user, 'sanctum')->getJson('/api/monitor-aps/cidadaos?page=1&per_page=10');
        if ($r->status() === 200) {
            $r->assertJsonStructure(['cidadaos', 'meta' => ['total', 'page', 'per_page', 'pages']]);
        }
        $this->assertNotEquals(500, $r->status());
    }

    public function test_cidadaos_busca_minimo_3_chars(): void
    {
        Cache::put('aps_db_config', null, 3600);
        $r = $this->actingAs($this->user, 'sanctum')->getJson('/api/monitor-aps/cidadaos?busca=AB');
        if ($r->status() !== 503) {
            $this->assertEquals(422, $r->status());
        }
    }
}
