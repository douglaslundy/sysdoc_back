<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // O menu do admin vem das páginas do perfil "admin" (profile_page_permissions): sem a linha, a página não aparece.
    public function up(): void
    {
        $admin = DB::table('access_profiles')->where('slug', 'admin')->value('id');
        if (! $admin) {
            return;
        }

        $ids = DB::table('system_pages')
            ->whereIn('path', ['/sistema/painel-publico', '/sistema/atalhos', '/dashboard/atalhos'])
            ->pluck('id');

        foreach ($ids as $id) {
            DB::table('profile_page_permissions')->updateOrInsert(
                ['access_profile_id' => $admin, 'system_page_id' => $id],
                ['updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        // No-op: permissão é configuração do admin.
    }
};
