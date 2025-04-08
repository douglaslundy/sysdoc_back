<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDriverToUsersTable extends Migration
{
    /**
     * Executa as migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Adiciona a coluna is_driver do tipo boolean, com valor padrão 0 (falso)
            $table->boolean('is_driver')->default(0)->after('cpf');
        });
    }

    /**
     * Reverte as migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove a coluna is_driver
            $table->dropColumn('is_driver');
        });
    }
}
