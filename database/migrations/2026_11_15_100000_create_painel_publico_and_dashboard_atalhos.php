<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('painel_publico_config')) {
            Schema::create('painel_publico_config', function (Blueprint $table) {
                $table->id();
                $table->string('pagina_url', 500)->default('/painel-psf');
                $table->string('esquerda_url', 500)->default('/painel-esus');
                $table->string('direita_url', 500)->default('/transparency/medicines-panel');
                $table->unsignedTinyInteger('largura_esquerda')->default(60);
                $table->timestamps();
            });
        }

        if (! DB::table('painel_publico_config')->exists()) {
            DB::table('painel_publico_config')->insert(['created_at' => now(), 'updated_at' => now()]);
        }

        if (! Schema::hasTable('dashboard_atalhos')) {
            Schema::create('dashboard_atalhos', function (Blueprint $table) {
                $table->id();
                $table->string('titulo', 60);
                $table->string('url', 500);
                $table->string('icone', 40)->default('link');
                $table->unsignedInteger('ordem')->default(0);
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });

            DB::table('dashboard_atalhos')->insert([
                ['titulo' => 'Clientes', 'url' => '/clients', 'icone' => 'users', 'ordem' => 1],
                ['titulo' => 'Viagens', 'url' => '/trips', 'icone' => 'map-pin', 'ordem' => 2],
                ['titulo' => 'Atendimentos', 'url' => '/attendance/queue', 'icone' => 'list', 'ordem' => 3],
            ]);
            DB::table('dashboard_atalhos')->update(['created_at' => now(), 'updated_at' => now()]);
        }

        $this->registrarPaginas();
    }

    /** Aba do dashboard (liberável por perfil/secretaria) e telas de admin no menu Sistema. */
    private function registrarPaginas(): void
    {
        if (! Schema::hasTable('system_pages')) {
            return;
        }

        $dash = DB::table('page_categories')->where('nome', 'Dashboard')->first();
        $sist = DB::table('page_categories')->where('nome', 'Sistema')->first();

        $paginas = [
            ['Dashboard - Atalhos', '/dashboard/atalhos', 'link', 'Dashboard', $dash?->id, 12],
            ['Painel público', '/sistema/painel-publico', 'monitor', 'Sistema', $sist?->id, 7],
            ['Atalhos do dashboard', '/sistema/atalhos', 'link', 'Sistema', $sist?->id, 8],
        ];

        foreach ($paginas as [$titulo, $path, $icone, $categoria, $categoriaId, $ordem]) {
            DB::table('system_pages')->updateOrInsert(['path' => $path], [
                'titulo' => $titulo, 'icone' => $icone, 'categoria' => $categoria,
                'category_id' => $categoriaId, 'ordem' => $ordem, 'ativo' => true, 'updated_at' => now(),
            ]);
        }

        $aba = DB::table('system_pages')->where('path', '/dashboard/atalhos')->value('id');
        $inicio = DB::table('system_pages')->where('path', '/dashboard/inicio')->value('id');
        if (! $aba) {
            return;
        }

        // Perfis que já viam o Início passam a ver a aba nova; secretarias que têm o Início também.
        if ($inicio && Schema::hasTable('profile_page_permissions')) {
            foreach (DB::table('profile_page_permissions')->where('system_page_id', $inicio)->pluck('access_profile_id') as $perfil) {
                DB::table('profile_page_permissions')->updateOrInsert(
                    ['access_profile_id' => $perfil, 'system_page_id' => $aba],
                    ['updated_at' => now()]
                );
            }
        }
        if ($inicio && Schema::hasTable('secretaria_pages')) {
            foreach (DB::table('secretaria_pages')->where('system_page_id', $inicio)->pluck('secretaria_id') as $secretaria) {
                DB::table('secretaria_pages')->updateOrInsert(['secretaria_id' => $secretaria, 'system_page_id' => $aba], []);
            }
        }
    }

    public function down(): void
    {
        // No-op: configuração do admin; não apaga dados nem permissões.
    }
};
