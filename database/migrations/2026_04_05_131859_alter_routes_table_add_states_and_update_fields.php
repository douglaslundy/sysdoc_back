<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('routes', function (Blueprint $table) {

            // Alterar tamanho dos campos existentes
            $table->string('origin', 50)->change();
            $table->string('destination', 50)->change();

            // Novos campos de estado
            $table->char('origin_state', 2)->after('origin');
            $table->char('destination_state', 2)->after('destination');

            // Índices (opcional)
            $table->index('origin_state');
            $table->index('destination_state');
        });
    }

    public function down()
    {
        Schema::table('routes', function (Blueprint $table) {

            // Reverter alterações
            $table->string('origin', 30)->change();
            $table->string('destination', 30)->change();

            $table->dropColumn(['origin_state', 'destination_state']);
        });
    }
};