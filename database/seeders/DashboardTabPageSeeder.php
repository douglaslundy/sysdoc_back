<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DashboardTabPageSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Categoria "Dashboard"
        DB::table('page_categories')->updateOrInsert(
            ['nome' => 'Dashboard'],
            [
                'icone'      => 'bar-chart-2',
                'ordem'      => 2,
                'ativo'      => true,
                'updated_at' => now(),
            ]
        );

        $categoria = DB::table('page_categories')->where('nome', 'Dashboard')->first();

        // 2. Abas como system_pages
        $pages = [
            ['titulo' => 'Dashboard - Início',               'path' => '/dashboard/inicio',      'icone' => 'home',        'ordem' => 1],
            ['titulo' => 'Dashboard - Vigilância Sanitária', 'path' => '/dashboard/vigilancia',  'icone' => 'shield',      'ordem' => 2],
            ['titulo' => 'Dashboard - Laboratório',          'path' => '/dashboard/laboratorio', 'icone' => 'thermometer', 'ordem' => 3],
            ['titulo' => 'Dashboard - Fila',                 'path' => '/dashboard/fila',        'icone' => 'list',        'ordem' => 4],
            ['titulo' => 'Dashboard - TFD',                  'path' => '/dashboard/tfd',         'icone' => 'send',        'ordem' => 5],
            ['titulo' => 'Dashboard - Farmácia',             'path' => '/dashboard/farmacia',    'icone' => 'package',     'ordem' => 6],
            ['titulo' => 'Dashboard - Logs/QR',              'path' => '/dashboard/logs',        'icone' => 'eye',         'ordem' => 7],
            ['titulo' => 'Dashboard - Conformidades',        'path' => '/dashboard/conformidades','icone' => 'shield',      'ordem' => 8],
            ['titulo' => 'Dashboard - Chat',                  'path' => '/dashboard/chat',         'icone' => 'message-circle','ordem' => 9],
        ];

        foreach ($pages as $page) {
            DB::table('system_pages')->updateOrInsert(
                ['path' => $page['path']],
                [
                    'titulo'      => $page['titulo'],
                    'icone'       => $page['icone'],
                    'categoria'   => 'Dashboard',
                    'category_id' => $categoria?->id,
                    'ordem'       => $page['ordem'],
                    'ativo'       => true,
                    'updated_at'  => now(),
                ]
            );
        }

        // 3. Permissões iniciais por perfil
        // admin recebe todas; user/manager recebem tudo exceto Logs/QR
        $todasPaths  = array_column($pages, 'path');
        $semAdministracao = array_filter(
            $todasPaths,
            fn ($p) => ! in_array($p, ['/dashboard/logs', '/dashboard/chat'], true)
        );

        $permissoes = [
            'admin'   => $todasPaths,
            'user'    => array_values($semAdministracao),
            'manager' => array_values($semAdministracao),
        ];

        foreach ($permissoes as $slug => $paths) {
            $profile = DB::table('access_profiles')->where('slug', $slug)->first();
            if (! $profile) continue;

            foreach ($paths as $path) {
                $pg = DB::table('system_pages')->where('path', $path)->first();
                if (! $pg) continue;

                DB::table('profile_page_permissions')->updateOrInsert(
                    ['access_profile_id' => $profile->id, 'system_page_id' => $pg->id],
                    ['updated_at' => now()]
                );
            }
        }
    }
}
