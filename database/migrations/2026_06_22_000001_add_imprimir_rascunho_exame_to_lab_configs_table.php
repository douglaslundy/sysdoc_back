<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_configs', function (Blueprint $table) {
            $table->boolean('imprimir_rascunho_exame')->default(false)->after('email_habilitado');
        });
    }

    public function down(): void
    {
        Schema::table('lab_configs', function (Blueprint $table) {
            $table->dropColumn('imprimir_rascunho_exame');
        });
    }
};
