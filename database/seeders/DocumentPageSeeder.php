<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentPageSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('page_categories')->updateOrInsert(
            ['nome' => 'Documentos'],
            [
                'icone' => 'file-text',
                'ordem' => 15,
                'ativo' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $categoria = DB::table('page_categories')->where('nome', 'Documentos')->first();

        $pages = [
            ['titulo' => 'Documentos', 'path' => '/documentos', 'icone' => 'file-text', 'ordem' => 1],
            ['titulo' => 'Tipos de Documentos', 'path' => '/documentos/tipos', 'icone' => 'list', 'ordem' => 2],
            ['titulo' => 'Aprovações', 'path' => '/documentos/aprovacoes', 'icone' => 'check-circle', 'ordem' => 3],
        ];

        foreach ($pages as $page) {
            DB::table('system_pages')->updateOrInsert(
                ['path' => $page['path']],
                [
                    'titulo' => $page['titulo'],
                    'icone' => $page['icone'],
                    'categoria' => 'Documentos',
                    'category_id' => $categoria?->id,
                    'ordem' => $page['ordem'],
                    'ativo' => true,
                    'updated_at' => now(),
                ]
            );
        }

        $permissoes = [
            'admin' => ['/documentos', '/documentos/tipos', '/documentos/aprovacoes'],
            'manager' => ['/documentos'],
            'tfd' => ['/documentos'],
        ];

        foreach ($permissoes as $slug => $paths) {
            $profile = DB::table('access_profiles')->where('slug', $slug)->first();
            if (! $profile) {
                continue;
            }

            foreach ($paths as $path) {
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
