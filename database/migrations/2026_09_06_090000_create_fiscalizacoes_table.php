<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscalizacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estabelecimento_id')->constrained('estabelecimentos')->cascadeOnDelete();
            $table->foreignId('fiscal_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('data_visita');
            $table->string('resultado', 30);
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estabelecimento_id', 'data_visita'], 'fiscalizacoes_estab_data_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscalizacoes');
    }
};
