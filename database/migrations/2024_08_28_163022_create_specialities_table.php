<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('specialities', function (Blueprint $table) {
            $table->id(); // Cria a coluna 'id' como chave primária
            $table->unsignedBigInteger('id_user'); // Cria a coluna 'id_user'
            $table->string('name', 50); // Cria a coluna 'name' do tipo string
            $table->timestamps(); // Cria as colunas 'created_at' e 'updated_at'

            // Define a chave estrangeira que referencia a tabela 'users'
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove primeiro a chave estrangeira
        Schema::table('specialities', function (Blueprint $table) {
            $table->dropForeign(['id_user']);
        });

        // Drope a tabela specialities
        Schema::dropIfExists('specialities');
    }
};
