<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultado_campos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resultado_exame_id');
            $table->foreign('resultado_exame_id')->references('id')->on('resultado_exames')->onDelete('cascade');
            $table->unsignedBigInteger('exame_campo_id');
            $table->foreign('exame_campo_id')->references('id')->on('exame_campos');
            $table->unsignedBigInteger('exame_id');
            $table->foreign('exame_id')->references('id')->on('exames');
            $table->decimal('valor_numerico', 10, 3)->nullable();
            $table->text('valor_texto')->nullable();
            $table->enum('status_referencia', ['normal', 'baixo', 'alto', 'critico', 'indefinido'])->default('indefinido');
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index('resultado_exame_id');
            $table->index('exame_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultado_campos');
    }
};
