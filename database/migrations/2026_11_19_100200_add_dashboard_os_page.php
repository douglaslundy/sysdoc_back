<?php

use App\Models\Secretaria;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PATH = '/dashboard/ordens-servico';

    // Aba "OS" do dashboard: cria a página, libera nas secretarias e dá ao admin (o menu vem do perfil).
    public function up(): void
    {
        $categoria = DB::table('page_categories')->where('nome', 'Dashboard')->value('id');

        DB::table('system_pages')->updateOrInsert(['path' => self::PATH], [
            'titulo' => 'Dashboard - Ordens de Serviço',
            'icone' => 'tool',
            'categoria' => 'Dashboard',
            'category_id' => $categoria,
            'ordem' => 12,
            'ativo' => true,
            'updated_at' => now(),
        ]);

        $pageId = DB::table('system_pages')->where('path', self::PATH)->value('id');
        $admin = DB::table('access_profiles')->where('slug', 'admin')->value('id');
        if ($admin && $pageId) {
            DB::table('profile_page_permissions')->updateOrInsert(
                ['access_profile_id' => $admin, 'system_page_id' => $pageId],
                ['updated_at' => now()]
            );
        }

        Secretaria::liberarPaginas([self::PATH], Secretaria::pluck('slug')->all());
    }

    public function down(): void
    {
        DB::table('system_pages')->where('path', self::PATH)->delete();
    }
};
