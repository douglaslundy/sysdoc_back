<?php

use Database\Seeders\OuvidoriaSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ouvidoria_config')) {
            Schema::create('ouvidoria_config', function (Blueprint $table) {
                $table->id();
                $table->string('nome_marca', 60)->default('DL Sistemas');
                $table->string('logo_path')->nullable();
                $table->string('registrar_titulo', 120);
                $table->string('registrar_texto', 500)->nullable();
                $table->json('registrar_itens')->nullable();
                $table->string('consultar_titulo', 120);
                $table->string('consultar_texto', 500)->nullable();
                $table->json('consultar_itens')->nullable();
                $table->timestamps();
            });
        }

        // Página "Configuração do portal" no menu Ouvidoria, só para admin.
        OuvidoriaSeeder::paginaConfig();
    }

    public function down(): void
    {
        DB::table('system_pages')->where('path', OuvidoriaSeeder::PAGINA_CONFIG)->delete();
        Schema::dropIfExists('ouvidoria_config');
    }
};
