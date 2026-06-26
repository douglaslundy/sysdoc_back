<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KanbanPageSeeder extends Seeder
{
    public function run(): void
    {
        $categoria = DB::table('page_categories')->where('nome', 'Administração')->first();

        $page = [
            'titulo' => 'Kanban Geral',
            'path' => '/kanban',
            'icone' => 'trello',
            'categoria' => 'Administração',
            'category_id' => $categoria?->id,
            'ordem' => 13,
            'ativo' => true,
        ];

        DB::table('system_pages')->updateOrInsert(
            ['path' => $page['path']],
            [
                'titulo' => $page['titulo'],
                'icone' => $page['icone'],
                'categoria' => $page['categoria'],
                'category_id' => $page['category_id'],
                'ordem' => $page['ordem'],
                'ativo' => $page['ativo'],
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $pageModel = DB::table('system_pages')->where('path', $page['path'])->first();
        if (! $pageModel) {
            return;
        }

        $profiles = ['admin', 'manager', 'user', 'tfd', 'driver'];

        foreach ($profiles as $slug) {
            $profile = DB::table('access_profiles')->where('slug', $slug)->first();
            if (! $profile) {
                continue;
            }

            DB::table('profile_page_permissions')->updateOrInsert(
                [
                    'access_profile_id' => $profile->id,
                    'system_page_id' => $pageModel->id,
                ],
                ['updated_at' => now()]
            );
        }
    }
}
