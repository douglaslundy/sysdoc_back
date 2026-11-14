<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IluminacaoPageSeeder extends Seeder
{
    public const CATEGORIA = 'Iluminação Pública';

    public const PAGINAS = [
        ['path' => '/iluminacao/cadastro', 'titulo' => 'Postes — Cadastro', 'icone' => 'lightbulb', 'ordem' => 1],
        ['path' => '/iluminacao/servico', 'titulo' => 'Postes — Serviço', 'icone' => 'wrench', 'ordem' => 2],
    ];

    // Query builder (sem eventos de model) para não gravar audit_logs; roda tanto na migration quanto no seed.
    public function run(): void
    {
        DB::table('page_categories')->updateOrInsert(
            ['nome' => self::CATEGORIA],
            ['icone' => 'lightbulb', 'ordem' => 20]
        );
        $categoria = DB::table('page_categories')->where('nome', self::CATEGORIA)->first();
        $admin = DB::table('access_profiles')->where('slug', 'admin')->first();

        foreach (self::PAGINAS as $pagina) {
            DB::table('system_pages')->updateOrInsert(
                ['path' => $pagina['path']],
                [
                    'titulo' => $pagina['titulo'],
                    'icone' => $pagina['icone'],
                    'categoria' => self::CATEGORIA,
                    'category_id' => $categoria->id,
                    'ordem' => $pagina['ordem'],
                    'ativo' => true,
                    'updated_at' => now(),
                ]
            );

            $page = DB::table('system_pages')->where('path', $pagina['path'])->first();

            if ($admin && $page) {
                DB::table('profile_page_permissions')->updateOrInsert(
                    ['access_profile_id' => $admin->id, 'system_page_id' => $page->id],
                    ['updated_at' => now()]
                );
            }
        }

        \App\Models\Secretaria::liberarPaginas(array_column(self::PAGINAS, 'path'));
    }
}
