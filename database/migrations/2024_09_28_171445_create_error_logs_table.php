<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateErrorLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable(); // Tipo de erro (ex: Exception, Error, etc)
            $table->string('file')->nullable(); // Arquivo onde ocorreu o erro
            $table->integer('line')->nullable(); // Linha do arquivo onde ocorreu o erro
            $table->text('message'); // Mensagem do erro
            $table->text('trace')->nullable(); // Stack trace do erro
            $table->json('context')->nullable(); // Contexto adicional (opcional)
            $table->timestamps(); // Created_at e Updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('error_logs');
    }
}
