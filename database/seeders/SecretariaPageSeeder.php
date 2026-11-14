<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SecretariaPageSeeder extends Seeder
{
    public const PATH = '/sistema/secretarias';

    // Query builder (sem eventos de model) para não gravar audit_logs; roda na migration e no seed.
    public function run(): void
    {
        $categoria = DB::table('page_categories')->where('nome', 'Administração')->first();

        DB::table('system_pages')->updateOrInsert(
            ['path' => self::PATH],
            [
                'titulo' => 'Secretarias',
                'icone' => 'building',
                'categoria' => 'Administração',
                'category_id' => $categoria?->id,
                'ordem' => 14,
                'ativo' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        \App\Models\Secretaria::liberarPaginas([self::PATH]);

        $page = DB::table('system_pages')->where('path', self::PATH)->first();
        $admin = DB::table('access_profiles')->where('slug', 'admin')->first();

        if ($admin && $page) {
            DB::table('profile_page_permissions')->updateOrInsert(
                ['access_profile_id' => $admin->id, 'system_page_id' => $page->id],
                ['updated_at' => now()]
            );
        }
    }
}
