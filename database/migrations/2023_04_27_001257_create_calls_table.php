<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->dateTime('call_datetime');
            $table->dateTime('start_datetime')->nullable();
            $table->dateTime('end_datetime')->nullable();

            // CRIAR UMA TABELA PARA GRAVAR O NUMERO DA SENHA
            // $table->STRING('pass_number', 8);

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedBigInteger('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients');

            // fazer validador
            $table->string('reason', 200)->nullable();

            $table->unsignedBigInteger('room_id')->nullable();
            $table->foreign('room_id')->references('id')->on('rooms');

            $table->unsignedBigInteger('call_service_id');
            $table->foreign('call_service_id')->references('id')->on('call_services');

            $table->string('subject', 200)->nullable();
            $table->enum('status', ['NOT_STARTED', 'IN_PROGRESS', 'CLOSED', 'ABANDONED']);
            //

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
