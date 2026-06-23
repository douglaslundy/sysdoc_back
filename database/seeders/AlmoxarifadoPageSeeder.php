<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlmoxarifadoPageSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('page_categories')->updateOrInsert(
            ['nome' => 'Almoxarifado'],
            [
                'icone' => 'package',
                'ordem' => 9,
                'ativo' => true,
                'updated_at' => now(),
            ]
        );

        $categoria = DB::table('page_categories')->where('nome', 'Almoxarifado')->first();

        $pages = [
            ['titulo' => 'Almoxarifado', 'path' => '/almoxarifado', 'icone' => 'package', 'ordem' => 1],
            ['titulo' => 'Produtos', 'path' => '/almoxarifado/produtos', 'icone' => 'archive', 'ordem' => 2],
            ['titulo' => 'Secretarias', 'path' => '/almoxarifado/secretarias', 'icone' => 'users', 'ordem' => 3],
            ['titulo' => 'Estoque', 'path' => '/almoxarifado/estoque', 'icone' => 'layers', 'ordem' => 4],
            ['titulo' => 'Requisições', 'path' => '/almoxarifado/requisicoes', 'icone' => 'clipboard', 'ordem' => 5],
            ['titulo' => 'Movimentações', 'path' => '/almoxarifado/movimentacoes', 'icone' => 'refresh-cw', 'ordem' => 6],
            ['titulo' => 'Relatórios', 'path' => '/almoxarifado/relatorios', 'icone' => 'bar-chart-2', 'ordem' => 7],
            ['titulo' => 'Configurações', 'path' => '/almoxarifado/configuracoes', 'icone' => 'settings', 'ordem' => 8],
        ];

        foreach ($pages as $page) {
            DB::table('system_pages')->updateOrInsert(
                ['path' => $page['path']],
                [
                    'titulo' => $page['titulo'],
                    'icone' => $page['icone'],
                    'categoria' => 'Almoxarifado',
                    'category_id' => $categoria?->id,
                    'ordem' => $page['ordem'],
                    'ativo' => true,
                    'updated_at' => now(),
                ]
            );
        }

        $permissoes = [
            'admin' => array_column($pages, 'path'),
            'manager' => [
                '/almoxarifado',
                '/almoxarifado/produtos',
                '/almoxarifado/secretarias',
                '/almoxarifado/estoque',
                '/almoxarifado/requisicoes',
                '/almoxarifado/movimentacoes',
                '/almoxarifado/relatorios',
            ],
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
