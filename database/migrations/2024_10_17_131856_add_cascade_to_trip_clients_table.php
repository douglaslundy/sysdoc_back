<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCascadeToTripClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trip_clients', function (Blueprint $table) {
            // Primeiro remove a chave estrangeira existente, se já houver uma
            $table->dropForeign(['trip_id']);

            // Agora adiciona a chave estrangeira novamente com a exclusão em cascata
            $table->foreign('trip_id')
                ->references('id')
                ->on('trips')
                ->onDelete('cascade'); // Excluir em cascata
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trip_clients', function (Blueprint $table) {
            // Remove a chave estrangeira com a exclusão em cascata
            $table->dropForeign(['trip_id']);

            // Adiciona a chave estrangeira original sem a exclusão em cascata
            $table->foreign('trip_id')
                ->references('id')
                ->on('trips');
        });
    }
}
