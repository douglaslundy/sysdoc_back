<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdUserToVehiclesAndRoutesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Adicionar coluna id_user na tabela vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedBigInteger('id_user')->after('id')->nullable(); // Adiciona o campo id_user após o campo id e permite null
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade'); // Cria chave estrangeira para a tabela users
        });

        // Adicionar coluna id_user na tabela routes
        Schema::table('routes', function (Blueprint $table) {
            $table->unsignedBigInteger('id_user')->after('id')->nullable(); // Adiciona o campo id_user após o campo id e permite null
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade'); // Cria chave estrangeira para a tabela users
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remover coluna id_user da tabela vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['id_user']);
            $table->dropColumn('id_user');
        });

        // Remover coluna id_user da tabela routes
        Schema::table('routes', function (Blueprint $table) {
            $table->dropForeign(['id_user']);
            $table->dropColumn('id_user');
        });
    }
}
