<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pessoa_fotos')) {
            return;
        }

        Schema::create('pessoa_fotos', function (Blueprint $table) {
            $table->id();
            // "cpf:<11 dígitos>" (compartilhada entre cidadão e usuário) ou "client:<id>" / "user:<id>" quando não há CPF.
            $table->string('chave', 40)->unique();
            $table->string('disk', 30)->default('private');
            $table->string('path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pessoa_fotos');
    }
};
