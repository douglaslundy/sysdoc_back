<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConformidadeCidadaoPageSeeder extends Seeder
{
    public function run(): void
    {
        $categoria = DB::table('page_categories')->where('nome', 'Cadastros')->first();

        DB::table('system_pages')->updateOrInsert(
            ['path' => '/conformidade-cidadao'],
            [
                'titulo'      => 'Conformidade de Cidadãos',
                'icone'       => 'refresh-cw',
                'categoria'   => 'Cadastros',
                'category_id' => $categoria?->id,
                'ativo'       => true,
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );

        $page    = DB::table('system_pages')->where('path', '/conformidade-cidadao')->first();
        $profile = DB::table('access_profiles')->where('slug', 'admin')->first();

        if ($page && $profile) {
            DB::table('profile_page_permissions')->updateOrInsert(
                ['access_profile_id' => $profile->id, 'system_page_id' => $page->id],
                ['updated_at' => now()]
            );
        }
    }
}
