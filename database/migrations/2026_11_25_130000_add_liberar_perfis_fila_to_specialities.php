<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Especialidade liberada (com todas as permissões) para todo perfil que tem a página de Filas. */
    public function up(): void
    {
        if (Schema::hasColumn('specialities', 'liberar_perfis_fila')) {
            return;
        }

        Schema::table('specialities', function (Blueprint $table) {
            $table->boolean('liberar_perfis_fila')->default(false)->after('allows_session_scheduling');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('specialities', 'liberar_perfis_fila')) {
            Schema::table('specialities', function (Blueprint $table) {
                $table->dropColumn('liberar_perfis_fila');
            });
        }
    }
};
