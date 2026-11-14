<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GestorProfileSeeder extends Seeder
{
    public const SLUG = 'gestor';

    // Só páginas que já existem (início, protocolo, kanban, chat, usuários): nenhuma system_page nova, logo nada a liberar.
    public const PAGINAS = ['/dashboard', '/dashboard/inicio', '/dashboard/chat', '/kanban', '/protocolo', '/protocolo/caixa-entrada', '/protocolo/novo', '/users'];

    // Query builder (sem eventos de model) para não gravar audit_logs; roda na migration e no seed.
    public function run(): void
    {
        DB::table('access_profiles')->updateOrInsert(
            ['slug' => self::SLUG],
            [
                'nome' => 'Gestor',
                'descricao' => 'Gestão de usuários e solicitações da própria secretaria',
                'ativo' => true,
                'chat_enabled' => true,
                'updated_at' => now(),
            ]
        );

        $perfil = DB::table('access_profiles')->where('slug', self::SLUG)->first();

        foreach (DB::table('system_pages')->whereIn('path', self::PAGINAS)->pluck('id') as $pageId) {
            DB::table('profile_page_permissions')->updateOrInsert(
                ['access_profile_id' => $perfil->id, 'system_page_id' => $pageId],
                ['updated_at' => now()]
            );
        }
    }
}
