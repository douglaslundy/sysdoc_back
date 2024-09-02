<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('queue', function (Blueprint $table) {
            $table->id(); // Cria a coluna id como chave primária
            $table->dateTime('date_of_received'); // Data de recebimento

            // Chaves estrangeiras com relacionamento
            $table->unsignedBigInteger('id_client');
            $table->unsignedBigInteger('id_specialities');
            $table->unsignedBigInteger('id_user');

            // Colunas adicionais
            $table->boolean('done')->default(false); // Situação (se já foi feito)
            $table->date('date_of_realized')->nullable(); // Data de realização (se feito)
            $table->boolean('urgency')->default(false); // Prioridade (rotina/urgente)
            $table->string('obs', 200)->nullable(); // Observações (máximo 200 caracteres)

            // Campos de timestamp padrão
            $table->timestamps(); // Cria created_at e updated_at

            // Definindo as foreign keys e relacionamentos
            $table->foreign('id_client')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('id_specialities')->references('id')->on('specialities')->onDelete('cascade');
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
        Schema::table('queue', function (Blueprint $table) {
            // Removendo as foreign keys
            $table->dropForeign(['id_client']);
            $table->dropForeign(['id_specialities']);
            $table->dropForeign(['id_user']);
        });

        // Removendo a tabela
        Schema::dropIfExists('queue');
    }
}
