<?php

use App\Models\Secretaria;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Aba "Início" (atalhos) para todos os perfis e secretarias existentes. Só INSERE o que falta, e só desta página:
    // não altera nem remove nenhuma outra permissão. Cada usuário continua vendo só os botões que pode abrir.
    public function up(): void
    {
        $pagina = DB::table('system_pages')->where('path', '/dashboard/atalhos')->value('id');
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

        Secretaria::liberarPaginas(['/dashboard/atalhos'], Secretaria::pluck('slug')->all());
    }

    public function down(): void
    {
        // No-op: permissão é configuração do admin.
    }
};
