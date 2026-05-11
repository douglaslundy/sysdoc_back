<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estabelecimentos', function (Blueprint $table) {
            $table->id();
            $table->string('nome_responsavel', 255);
            $table->string('nome_estabelecimento', 255);
            $table->string('endereco', 500);
            $table->text('cnaes');
            $table->timestamps();
            $table->softDeletes();

            $table->index('nome_estabelecimento');
            $table->index('nome_responsavel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estabelecimentos');
    }
};
