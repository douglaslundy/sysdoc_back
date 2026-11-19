<?php

use App\Models\Secretaria;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PATH = '/sistema/documentacao-api';

    // Página "Documentação da API" (menu Sistema): liberada nas secretarias e a todos os perfis.
    // Só INSERE o que falta; o admin pode retirar de um perfil em Perfis.
    public function up(): void
    {
        $categoria = DB::table('page_categories')->where('nome', 'Sistema')->value('id');

        DB::table('system_pages')->updateOrInsert(['path' => self::PATH], [
            'titulo' => 'Documentação da API',
            'icone' => 'book',
            'categoria' => 'Sistema',
            'category_id' => $categoria,
            'ordem' => 9,
            'ativo' => true,
            'updated_at' => now(),
        ]);

        $pagina = DB::table('system_pages')->where('path', self::PATH)->value('id');
        if (! $pagina) {
            return;
        }

        foreach (DB::table('access_profiles')->pluck('id') as $perfil) {
            $existe = DB::table('profile_page_permissions')
                ->where('access_profile_id', $perfil)->where('system_page_id', $pagina)->exists();
            if (! $existe) {
                DB::table('profile_page_permissions')->insert([
                    'access_profile_id' => $perfil, 'system_page_id' => $pagina, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        Secretaria::liberarPaginas([self::PATH], Secretaria::pluck('slug')->all());
    }

    public function down(): void
    {
        DB::table('system_pages')->where('path', self::PATH)->delete();
    }
};
