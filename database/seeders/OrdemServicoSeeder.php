<?php

namespace Database\Seeders;

use App\Models\Secretaria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrdemServicoSeeder extends Seeder
{
    public const CATEGORIA = 'Ordem de Serviço';

    public const PAGINAS = [
        ['path' => '/ordem-servico', 'titulo' => 'Ordens de Serviço', 'icone' => 'wrench', 'ordem' => 1],
        ['path' => '/ordem-servico/configuracao', 'titulo' => 'Configuração de OS', 'icone' => 'settings', 'ordem' => 2],
    ];

    public const TECNICO = 'tecnico';

    // Query builder (sem eventos de model) para não gravar audit_logs; roda na migration e no seed.
    public function run(): void
    {
        DB::table('page_categories')->updateOrInsert(['nome' => self::CATEGORIA], ['icone' => 'wrench', 'ordem' => 21]);
        $categoria = DB::table('page_categories')->where('nome', self::CATEGORIA)->first();

        foreach (self::PAGINAS as $p) {
            DB::table('system_pages')->updateOrInsert(['path' => $p['path']], [
                'titulo' => $p['titulo'], 'icone' => $p['icone'], 'categoria' => self::CATEGORIA,
                'category_id' => $categoria->id, 'ordem' => $p['ordem'], 'ativo' => true, 'updated_at' => now(),
            ]);
        }

        DB::table('access_profiles')->updateOrInsert(['slug' => self::TECNICO], [
            'nome' => 'Técnico', 'descricao' => 'Atende as ordens de serviço atribuídas a ele',
            'ativo' => true, 'chat_enabled' => true, 'updated_at' => now(),
        ]);

        $porPerfil = [
            'admin' => array_column(self::PAGINAS, 'path'),
            'gestor' => array_column(self::PAGINAS, 'path'),
            self::TECNICO => ['/dashboard', '/dashboard/inicio', '/dashboard/chat', '/ordem-servico'],
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

        // OS atravessa secretarias (qualquer uma abre para qualquer outra): a página vale em todas.
        Secretaria::liberarPaginas(
            array_merge(array_column(self::PAGINAS, 'path'), ['/dashboard', '/dashboard/inicio', '/dashboard/chat']),
            DB::table('secretarias')->pluck('slug')->all()
        );
    }
}
