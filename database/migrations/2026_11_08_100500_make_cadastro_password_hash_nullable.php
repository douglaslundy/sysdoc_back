<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// O hash do pedido é apagado (null) depois da decisão: a senha passa a viver só em users.password.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cadastro_solicitacoes', function (Blueprint $table) {
            $table->string('password_hash')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Sem volta segura: pedidos decididos já não têm hash.
    }
};
