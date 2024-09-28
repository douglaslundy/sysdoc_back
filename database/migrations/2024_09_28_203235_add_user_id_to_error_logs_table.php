<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserIdToErrorLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('error_logs', function (Blueprint $table) {
            // Adiciona o campo user_id como unsignedBigInteger
            $table->unsignedBigInteger('user_id')->nullable()->after('id'); // Use after para definir a posição da coluna

            // Define user_id como chave estrangeira referenciando a tabela users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('error_logs', function (Blueprint $table) {
            // Remove a chave estrangeira e o campo user_id
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
}
