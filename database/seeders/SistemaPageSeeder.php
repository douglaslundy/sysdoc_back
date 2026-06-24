<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SistemaPageSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('page_categories')->updateOrInsert(
            ['nome' => 'Sistema'],
            [
                'icone' => 'settings',
                'ordem' => 14,
                'ativo' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $categoria = DB::table('page_categories')->where('nome', 'Sistema')->first();

        DB::table('profile_page_permissions')
            ->whereIn('system_page_id', function ($query) {
                $query->select('id')
                    ->from('system_pages')
                    ->whereIn('path', ['/protocolo/configuracoes', '/configuracoes/whatsapp']);
            })
            ->delete();

        DB::table('system_pages')
            ->where('path', '/protocolo/configuracoes')
            ->delete();

        $pages = [
            ['titulo' => 'Configurações WhatsApp', 'path' => '/configuracoes/whatsapp', 'icone' => 'message-circle', 'ordem' => 1],
            ['titulo' => 'Alertas', 'path' => '/protocolo/alertas', 'icone' => 'bell', 'ordem' => 2],
        ];

        foreach ($pages as $page) {
            DB::table('system_pages')->updateOrInsert(
                ['path' => $page['path']],
                [
                    'titulo' => $page['titulo'],
                    'icone' => $page['icone'],
                    'categoria' => 'Sistema',
                    'category_id' => $categoria?->id,
                    'ordem' => $page['ordem'],
                    'ativo' => true,
                    'updated_at' => now(),
                ]
            );
        }

        $permissoes = [
            'admin' => array_column($pages, 'path'),
            'manager' => ['/protocolo/alertas'],
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
