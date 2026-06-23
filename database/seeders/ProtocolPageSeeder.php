<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProtocolPageSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('page_categories')->updateOrInsert(
            ['nome' => 'Protocolo'],
            [
                'icone' => 'inbox',
                'ordem' => 13,
                'ativo' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $categoria = DB::table('page_categories')->where('nome', 'Protocolo')->first();

        $pages = [
            ['titulo' => 'Protocolo', 'path' => '/protocolo', 'icone' => 'inbox', 'ordem' => 1],
            ['titulo' => 'Caixa de Entrada', 'path' => '/protocolo/caixa-entrada', 'icone' => 'mail', 'ordem' => 2],
            ['titulo' => 'Novo Protocolo', 'path' => '/protocolo/novo', 'icone' => 'plus-circle', 'ordem' => 3],
            ['titulo' => 'Estrutura Organizacional', 'path' => '/protocolo/estrutura', 'icone' => 'layers', 'ordem' => 4],
            ['titulo' => 'Alertas', 'path' => '/protocolo/alertas', 'icone' => 'bell', 'ordem' => 5],
            ['titulo' => 'Configurações', 'path' => '/protocolo/configuracoes', 'icone' => 'settings', 'ordem' => 6],
        ];

        foreach ($pages as $page) {
            DB::table('system_pages')->updateOrInsert(
                ['path' => $page['path']],
                [
                    'titulo' => $page['titulo'],
                    'icone' => $page['icone'],
                    'categoria' => 'Protocolo',
                    'category_id' => $categoria?->id,
                    'ordem' => $page['ordem'],
                    'ativo' => true,
                    'updated_at' => now(),
                ]
            );
        }

        $paths = array_column($pages, 'path');
        $permissoes = [
            'admin' => $paths,
            'manager' => ['/protocolo', '/protocolo/caixa-entrada', '/protocolo/novo', '/protocolo/estrutura', '/protocolo/alertas'],
        ];

        foreach ($permissoes as $slug => $pathsPerfil) {
            $profile = DB::table('access_profiles')->where('slug', $slug)->first();
            if (! $profile) {
                continue;
            }

            foreach ($pathsPerfil as $path) {
                $page = DB::table('system_pages')->where('path', $path)->first();
                if (! $page) {
                    continue;
                }

                DB::table('profile_page_permissions')->updateOrInsert(
                    [
                        'access_profile_id' => $profile->id,
                        'system_page_id' => $page->id,
                    ],
                    ['updated_at' => now()]
                );
            }
        }
    }
}
