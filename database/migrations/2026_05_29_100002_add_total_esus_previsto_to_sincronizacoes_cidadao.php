<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sincronizacoes_cidadao', function (Blueprint $table) {
            $table->unsignedInteger('total_esus_previsto')->default(0)->after('total_sysdoc');
        });
    }

    public function down(): void
    {
        Schema::table('sincronizacoes_cidadao', function (Blueprint $table) {
            $table->dropColumn('total_esus_previsto');
        });
    }
};
