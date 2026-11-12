<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PATH = '/trips/agenda';

    // Página "Agenda de Viagens" no bloco TFD do menu. Quem já tem a página de Viagens recebe a agenda
    // (o administrador pode tirar depois em Perfis); admin sempre tem.
    public function up(): void
    {
        $categoria = DB::table('page_categories')->where('nome', 'TFD')->first();
        $viagens = DB::table('system_pages')->where('path', '/trips')->first();

        DB::table('system_pages')->updateOrInsert(
            ['path' => self::PATH],
            [
                'titulo' => 'Agenda de Viagens',
                'icone' => 'calendar',
                'categoria' => 'TFD',
                'category_id' => $categoria->id ?? null,
                'ordem' => ($viagens->ordem ?? 0) + 1,
                'ativo' => true,
                'updated_at' => now(),
            ]
        );
        $pagina = DB::table('system_pages')->where('path', self::PATH)->first();

        $perfis = DB::table('access_profiles')->where('slug', 'admin')->pluck('id');
        if ($viagens) {
            $perfis = $perfis->merge(DB::table('profile_page_permissions')->where('system_page_id', $viagens->id)->pluck('access_profile_id'));
        }
        foreach ($perfis->unique() as $perfilId) {
            DB::table('profile_page_permissions')->updateOrInsert(
                ['access_profile_id' => $perfilId, 'system_page_id' => $pagina->id],
                ['updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        // As permissões saem em cascata com a página.
        DB::table('system_pages')->where('path', self::PATH)->delete();
    }
};
