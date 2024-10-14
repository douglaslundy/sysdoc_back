<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDriverToTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trips', function (Blueprint $table) {
            // Adiciona a coluna driver e define como chave estrangeira
            $table->unsignedBigInteger('driver')->nullable()->after('user_id');

            // Define a chave estrangeira para a tabela users
            $table->foreign('driver')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trips', function (Blueprint $table) {
            // Remove a chave estrangeira e a coluna
            $table->dropForeign(['driver']);
            $table->dropColumn('driver');
        });
    }
}
