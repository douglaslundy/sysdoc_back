<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('iluminacao_postes')) {
            return;
        }

        Schema::create('iluminacao_postes', function (Blueprint $table) {
            $table->id();
            $table->string('protocolo', 20)->nullable()->unique();
            $table->char('client_uuid', 36)->nullable()->unique();
            $table->string('ponto_referencia', 160);
            $table->string('logradouro', 160);
            $table->string('numero', 20)->nullable();
            $table->string('bairro', 100);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('precisao_m', 8, 2)->nullable();
            $table->string('status', 20)->default('pendente');
            $table->timestamp('concluido_em')->nullable();
            $table->foreignId('cadastrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['active', 'status', 'created_at'], 'iluminacao_postes_lista_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iluminacao_postes');
    }
};
