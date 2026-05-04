<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos_exame', function (Blueprint $table) {
            $table->unsignedBigInteger('medico_solicitante_id')->nullable()->after('status');
            $table->foreign('medico_solicitante_id')
                  ->references('id')->on('medicos_solicitantes')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_exame', function (Blueprint $table) {
            $table->dropForeign(['medico_solicitante_id']);
            $table->dropColumn('medico_solicitante_id');
        });
    }
};
