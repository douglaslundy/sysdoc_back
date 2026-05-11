<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alvaras', function (Blueprint $table) {
            $table->id();
            $table->string('numero_alvara', 20)->unique();
            $table->string('nivel_risco', 5);
            $table->unsignedBigInteger('estabelecimento_id');
            $table->date('data_alvara');
            $table->date('vencimento_alvara')->nullable();
            $table->text('contato')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('estabelecimento_id')
                ->references('id')
                ->on('estabelecimentos')
                ->onDelete('restrict');

            $table->index('nivel_risco');
            $table->index('estabelecimento_id');
            $table->index('data_alvara');
            $table->index('vencimento_alvara');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alvaras');
    }
};
