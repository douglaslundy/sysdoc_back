<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class PainelEsusUnidadesTest extends TestCase
{
    public function test_unidades_route_exists_and_is_not_404(): void
    {
        Cache::put('aps_db_config', null, 3600);

        $response = $this->actingAs(\App\Models\User::factory()->create())
            ->getJson('/api/painel-esus/unidades');

        $this->assertNotEquals(404, $response->status());
    }

    public function test_validar_cnes_never_returns_500(): void
    {
        Cache::put('aps_db_config', null, 3600);

        $response = $this->getJson('/api/public/painel-esus/validar-cnes?cnes=1234567');

        $this->assertNotEquals(500, $response->status());
    }
}
