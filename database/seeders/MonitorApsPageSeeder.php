<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MonitorApsPageSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Categoria "Monitor APS"
        DB::table('page_categories')->updateOrInsert(
            ['nome' => 'Monitor APS'],
            [
                'icone'      => 'activity',
                'ordem'      => 12,
                'ativo'      => true,
                'updated_at' => now(),
            ]
        );

        $categoria = DB::table('page_categories')->where('nome', 'Monitor APS')->first();

        // 2. Páginas do módulo
        $pages = [
            ['titulo' => 'Monitor APS - Dashboard',           'path' => '/monitor-aps',                    'icone' => 'bar-chart-2',  'ordem' => 1],
            ['titulo' => 'Monitor APS - Vínculo Territorial', 'path' => '/monitor-aps/vinculo',             'icone' => 'map-pin',      'ordem' => 2],
            ['titulo' => 'Monitor APS - Indicadores',         'path' => '/monitor-aps/qualidade',           'icone' => 'check-circle', 'ordem' => 3],
            ['titulo' => 'Monitor APS - Por Equipe',          'path' => '/monitor-aps/equipe',              'icone' => 'users',        'ordem' => 4],
            ['titulo' => 'Monitor APS - Visitas ACS/TACS',    'path' => '/monitor-aps/visitas',             'icone' => 'home',         'ordem' => 5],
            ['titulo' => 'Monitor APS - Mapa de Visitas',     'path' => '/monitor-aps/visitas/mapa',        'icone' => 'map',          'ordem' => 6],
            ['titulo' => 'Monitor APS - Configurações',       'path' => '/monitor-aps/configuracoes',       'icone' => 'settings',     'ordem' => 7],
        ];

        foreach ($pages as $page) {
            DB::table('system_pages')->updateOrInsert(
                ['path' => $page['path']],
                [
                    'titulo'      => $page['titulo'],
                    'icone'       => $page['icone'],
                    'categoria'   => 'Monitor APS',
                    'category_id' => $categoria?->id,
                    'ordem'       => $page['ordem'],
                    'ativo'       => true,
                    'updated_at'  => now(),
                ]
            );
        }

        // 3. Permissões por perfil
        $permissoes = [
            'admin'   => ['/monitor-aps', '/monitor-aps/vinculo', '/monitor-aps/qualidade', '/monitor-aps/equipe', '/monitor-aps/visitas', '/monitor-aps/visitas/mapa', '/monitor-aps/configuracoes'],
            'manager' => ['/monitor-aps', '/monitor-aps/vinculo', '/monitor-aps/qualidade', '/monitor-aps/equipe', '/monitor-aps/visitas', '/monitor-aps/visitas/mapa'],
        ];

        foreach ($permissoes as $slug => $paths) {
            $profile = DB::table('access_profiles')->where('slug', $slug)->first();
            if (! $profile) continue;

            foreach ($paths as $path) {
                $page = DB::table('system_pages')->where('path', $path)->first();
                if (! $page) continue;

                DB::table('profile_page_permissions')->updateOrInsert(
                    ['access_profile_id' => $profile->id, 'system_page_id' => $page->id],
                    ['updated_at' => now()]
                );
            }
        }
    }
}
