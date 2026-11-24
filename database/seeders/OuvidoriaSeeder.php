<?php

namespace Database\Seeders;

use App\Models\Secretaria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OuvidoriaSeeder extends Seeder
{
    public const CATEGORIA = 'Ouvidoria';

    public const PAGINA = '/sistema/ouvidoria';

    public const PAGINA_CONFIG = '/sistema/ouvidoria/configuracao';

    public const OUVIDOR = 'ouvidor';

    // Query builder (sem eventos de model) para não gravar audit_logs; roda na migration e no seed.
    public function run(): void
    {
        DB::table('page_categories')->updateOrInsert(['nome' => self::CATEGORIA], ['icone' => 'message-square', 'ordem' => 22]);
        $categoria = DB::table('page_categories')->where('nome', self::CATEGORIA)->first();

        DB::table('system_pages')->updateOrInsert(['path' => self::PAGINA], [
            'titulo' => 'Ouvidoria', 'icone' => 'message-square', 'categoria' => self::CATEGORIA,
            'category_id' => $categoria->id, 'ordem' => 1, 'ativo' => true, 'updated_at' => now(),
        ]);

        DB::table('access_profiles')->updateOrInsert(['slug' => self::OUVIDOR], [
            'nome' => 'Ouvidor', 'descricao' => 'Responde e encaminha as manifestações da ouvidoria',
            'ativo' => true, 'chat_enabled' => false, 'updated_at' => now(),
        ]);

        $porPerfil = [
            'admin' => [self::PAGINA],
            'gestor' => [self::PAGINA],
            self::OUVIDOR => ['/dashboard', '/dashboard/inicio', self::PAGINA],
        ];
        foreach ($porPerfil as $slug => $paths) {
            $perfil = DB::table('access_profiles')->where('slug', $slug)->first();
            if (! $perfil) {
                continue;
            }
            foreach (DB::table('system_pages')->whereIn('path', $paths)->pluck('id') as $pageId) {
                DB::table('profile_page_permissions')->updateOrInsert(
                    ['access_profile_id' => $perfil->id, 'system_page_id' => $pageId],
                    ['updated_at' => now()]
                );
            }
        }

        // A ouvidoria atravessa secretarias (encaminha para qualquer uma): a página vale em todas.
        Secretaria::liberarPaginas(
            [self::PAGINA, '/dashboard', '/dashboard/inicio'],
            DB::table('secretarias')->pluck('slug')->all()
        );

        self::paginaConfig();
    }

    /**
     * Página "Configuração do portal" (textos e logo do portal público): só o perfil admin.
     * Separada do run() para a migration criá-la sem reaplicar as liberações acima.
     */
    public static function paginaConfig(): void
    {
        // Mesma categoria da página da Ouvidoria (sobrevive a renomear a categoria na tela); sem ela, cria a padrão.
        $categoriaId = DB::table('system_pages')->where('path', self::PAGINA)->value('category_id')
            ?? DB::table('page_categories')->where('nome', self::CATEGORIA)->value('id');
        if (! $categoriaId) {
            DB::table('page_categories')->insert(['nome' => self::CATEGORIA, 'icone' => 'message-square', 'ordem' => 22, 'created_at' => now(), 'updated_at' => now()]);
            $categoriaId = DB::table('page_categories')->where('nome', self::CATEGORIA)->value('id');
        }
        $categoria = DB::table('page_categories')->where('id', $categoriaId)->first();
        DB::table('system_pages')->updateOrInsert(['path' => self::PAGINA_CONFIG], [
            'titulo' => 'Configuração do portal', 'icone' => 'settings', 'categoria' => $categoria->nome,
            'category_id' => $categoria->id, 'ordem' => 2, 'ativo' => true, 'updated_at' => now(),
        ]);
        $pagina = DB::table('system_pages')->where('path', self::PAGINA_CONFIG)->value('id');
        $admin = DB::table('access_profiles')->where('slug', 'admin')->value('id');
        if ($admin) {
            DB::table('profile_page_permissions')->updateOrInsert(
                ['access_profile_id' => $admin, 'system_page_id' => $pagina],
                ['updated_at' => now()]
            );
        }
        Secretaria::liberarPaginas([self::PAGINA_CONFIG], DB::table('secretarias')->pluck('slug')->all());
    }
}
