<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentConfigPageSeeder extends Seeder
{
    public function run(): void
    {
        $categoria = DB::table('page_categories')->where('nome', 'Documentos')->first();

        if (! $categoria) {
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
        }

        DB::table('system_pages')->updateOrInsert(
            ['path' => '/documentos/configuracoes'],
            [
                'titulo' => 'Configurações',
                'icone' => 'settings',
                'categoria' => 'Documentos',
                'category_id' => $categoria?->id,
                'ordem' => 4,
                'ativo' => true,
                'updated_at' => now(),
            ]
        );

        $profile = DB::table('access_profiles')->where('slug', 'admin')->first();
        $page = DB::table('system_pages')->where('path', '/documentos/configuracoes')->first();

        if ($profile && $page) {
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
