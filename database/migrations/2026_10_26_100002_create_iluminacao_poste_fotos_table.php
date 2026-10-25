<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('iluminacao_poste_fotos')) {
            return;
        }

        Schema::create('iluminacao_poste_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poste_id')->constrained('iluminacao_postes')->cascadeOnDelete();
            $table->foreignId('movimentacao_id')->nullable()->constrained('iluminacao_poste_movimentacoes')->cascadeOnDelete();
            $table->string('momento', 10)->default('cadastro');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 30)->default('private');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            $table->index(['poste_id', 'momento'], 'iluminacao_fotos_poste_momento_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iluminacao_poste_fotos');
    }
};
