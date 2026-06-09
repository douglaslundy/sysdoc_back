<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

class VisitaAcsResponsabilidadeTest extends TestCase
{
    private function actingAsAdmin(): void
    {
        $user = new User();
        $user->id = 1;
        $user->profile = 'admin';
        $user->active = true;

        Sanctum::actingAs($user);
    }

    public function test_responsabilidade_route_exists(): void
    {
        Cache::put('aps_db_config', null, 3600);

        $this->actingAsAdmin();
        $response = $this->getJson('/api/monitor-aps/visitas/responsabilidade');

        $this->assertNotEquals(404, $response->status());
    }

    public function test_responsabilidade_com_ine_nao_retorna_500(): void
    {
        Cache::put('aps_db_config', null, 3600);

        $this->actingAsAdmin();
        $response = $this->getJson('/api/monitor-aps/visitas/responsabilidade?ine=0000123456');

        $this->assertNotEquals(404, $response->status());
        $this->assertNotEquals(500, $response->status());
    }
}
