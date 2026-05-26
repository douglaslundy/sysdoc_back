<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class VisitaAcsResponsabilidadeTest extends TestCase
{
    public function test_responsabilidade_route_exists(): void
    {
        Cache::put('aps_db_config', null, 3600);

        $response = $this->actingAs(\App\Models\User::factory()->create())
            ->getJson('/api/monitor-aps/visitas/responsabilidade');

        $this->assertNotEquals(404, $response->status());
    }

    public function test_responsabilidade_com_ine_nao_retorna_500(): void
    {
        Cache::put('aps_db_config', null, 3600);

        $response = $this->actingAs(\App\Models\User::factory()->create())
            ->getJson('/api/monitor-aps/visitas/responsabilidade?ine=0000123456');

        $this->assertNotEquals(404, $response->status());
        $this->assertNotEquals(500, $response->status());
    }
}
