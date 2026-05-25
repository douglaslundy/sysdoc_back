<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estabelecimento_cnaes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estabelecimento_id');
            $table->unsignedBigInteger('cnae_id');
            $table->timestamps();

            $table->foreign('estabelecimento_id')
                ->references('id')
                ->on('estabelecimentos')
                ->onDelete('cascade');

            $table->foreign('cnae_id')
                ->references('id')
                ->on('cnaes')
                ->onDelete('cascade');

            $table->unique(['estabelecimento_id', 'cnae_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estabelecimento_cnaes');
    }
};

