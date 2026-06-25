<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('almoxarifado_requisicoes', function (Blueprint $table) {
            $table->foreignId('requisitante_user_id')
                ->nullable()
                ->after('solicitante')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('almoxarifado_requisicoes')
            ->orderBy('id')
            ->eachById(function ($requisicao) {
                $creatorId = DB::table('almoxarifado_requisicao_status_historicos')
                    ->where('almoxarifado_requisicao_id', $requisicao->id)
                    ->whereNull('status_anterior')
                    ->orderBy('id')
                    ->value('user_id');

                DB::table('almoxarifado_requisicoes')
                    ->where('id', $requisicao->id)
                    ->update(['requisitante_user_id' => $creatorId]);
            });
    }

    public function down(): void
    {
        Schema::table('almoxarifado_requisicoes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requisitante_user_id');
        });
    }
};
