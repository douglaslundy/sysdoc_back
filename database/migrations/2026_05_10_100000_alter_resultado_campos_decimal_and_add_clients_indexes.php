<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alarga valor_numerico de decimal(10,3) para decimal(15,4)
        // Suporta valores como contagem de plaquetas (até ~3 bilhões/mm³)
        // DDL seguro: alargamento de tipo não afeta dados existentes
        Schema::table('resultado_campos', function (Blueprint $table) {
            $table->decimal('valor_numerico', 15, 4)->nullable()->change();
        });

        // Índice em clients.cns para pesquisa em pedidos (não tinha índice)
        Schema::table('clients', function (Blueprint $table) {
            $table->index('cns', 'clients_cns_index');
        });
    }

    public function down(): void
    {
        Schema::table('resultado_campos', function (Blueprint $table) {
            $table->decimal('valor_numerico', 10, 3)->nullable()->change();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_cns_index');
        });
    }
};
