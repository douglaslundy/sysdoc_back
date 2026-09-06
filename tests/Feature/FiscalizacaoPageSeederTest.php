<?php

namespace Tests\Feature;

use Database\Seeders\AccessProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiscalizacaoPageSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_cria_pagina_fiscalizacoes_e_concede_a_admin_e_manager(): void
    {
        (new AccessProfileSeeder())->run();

        $this->assertDatabaseHas('system_pages', [
            'path' => '/fiscalizacoes',
            'titulo' => 'Fiscalizações',
            'categoria' => 'Vigilância Sanitária',
            'ativo' => 1,
        ]);

        $page = \DB::table('system_pages')->where('path', '/fiscalizacoes')->first();
        $adminProfile = \DB::table('access_profiles')->where('slug', 'admin')->first();
        $managerProfile = \DB::table('access_profiles')->where('slug', 'manager')->first();

        $this->assertDatabaseHas('profile_page_permissions', [
            'access_profile_id' => $adminProfile->id,
            'system_page_id' => $page->id,
        ]);
        $this->assertDatabaseHas('profile_page_permissions', [
            'access_profile_id' => $managerProfile->id,
            'system_page_id' => $page->id,
        ]);
    }
}
