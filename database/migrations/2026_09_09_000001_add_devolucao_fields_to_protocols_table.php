<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('protocols', function (Blueprint $table) {
            $table->dateTime('devolvido_em')->nullable()->after('reaberto_em');
            $table->text('justificativa_devolucao')->nullable()->after('justificativa_encerramento');
        });
    }

    public function down(): void
    {
        Schema::table('protocols', function (Blueprint $table) {
            $table->dropColumn(['devolvido_em', 'justificativa_devolucao']);
        });
    }
};
