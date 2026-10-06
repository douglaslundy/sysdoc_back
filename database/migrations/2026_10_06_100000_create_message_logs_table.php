<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('message_logs')) {
            return;
        }

        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('canal', 20); // whatsapp | email
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('destino', 190);
            $table->string('assunto', 255)->nullable();
            $table->text('mensagem');
            $table->string('status', 20); // enviado | erro
            $table->text('erro')->nullable();
            $table->string('origem', 120)->nullable(); // ex.: protocolo:criado
            $table->unsignedBigInteger('protocol_id')->nullable();
            $table->timestamp('enviada_em')->nullable();
            $table->timestamps();

            $table->index('canal');
            $table->index('created_at');
            $table->index('protocol_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};
