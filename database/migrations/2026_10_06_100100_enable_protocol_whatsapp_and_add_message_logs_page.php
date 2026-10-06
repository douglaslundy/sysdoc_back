<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Query builder (sem eventos de model): ProtocolConfig é auditado e a migration
        // não deve gravar linhas em audit_logs.
        if (DB::table('protocol_configs')->exists()) {
            DB::table('protocol_configs')->update(['notify_whatsapp' => true, 'updated_at' => now()]);
        } else {
            DB::table('protocol_configs')->insert([
                'allow_external_protocols' => true,
                'allow_reopen' => true,
                'notify_internal' => true,
                'notify_email' => false,
                'notify_whatsapp' => true,
                'default_priority' => 'normal',
                'default_due_days' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $categoria = DB::table('page_categories')->where('nome', 'Sistema')->first();

        DB::table('system_pages')->updateOrInsert(
            ['path' => '/sistema/mensagens-enviadas'],
            [
                'titulo' => 'Mensagens enviadas',
                'icone' => 'send',
                'categoria' => 'Sistema',
                'category_id' => $categoria?->id,
                'ordem' => 5,
                'ativo' => true,
                'updated_at' => now(),
            ]
        );

        $profile = DB::table('access_profiles')->where('slug', 'admin')->first();
        $page = DB::table('system_pages')->where('path', '/sistema/mensagens-enviadas')->first();

        if ($profile && $page) {
            DB::table('profile_page_permissions')->updateOrInsert(
                ['access_profile_id' => $profile->id, 'system_page_id' => $page->id],
                ['updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        // Mantém os valores atuais: desligar o alerta ou remover a página poderia sobrescrever
        // escolhas do administrador.
    }
};
