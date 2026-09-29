<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardCacheTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider globalDashboards
     */
    public function test_dashboards_globais_usam_cache_no_servidor(string $uri, string $cacheKey): void
    {
        Cache::forget($cacheKey);
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        DB::enableQueryLog();
        $first = $this->actingAs($admin, 'sanctum')->getJson($uri)->assertOk()->json();
        $firstQueries = count(DB::getQueryLog());

        DB::flushQueryLog();
        $this->app['auth']->forgetGuards();
        $second = $this->actingAs($admin, 'sanctum')->getJson($uri)->assertOk()->json();
        $secondQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($first, $second);
        $this->assertLessThan($firstQueries, $secondQueries, 'A segunda chamada deve vir do cache.');
        $this->assertTrue(Cache::has($cacheKey));
    }

    public static function globalDashboards(): array
    {
        return [
            'almoxarifado' => ['/api/dashboard/almoxarifado', 'dashboard.almoxarifado'],
            'arquivo' => ['/api/dashboard/arquivo', 'dashboard.arquivo'],
        ];
    }
}
