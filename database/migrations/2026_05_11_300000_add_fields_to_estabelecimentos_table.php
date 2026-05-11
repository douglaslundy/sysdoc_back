<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estabelecimentos', function (Blueprint $table) {
            $table->string('razao_social', 255)->nullable()->after('nome_responsavel');
            $table->string('nome_fantasia', 255)->nullable()->after('razao_social');
            $table->string('cnpj', 18)->nullable()->after('nome_fantasia');
            $table->string('telefone', 20)->nullable()->after('cnpj');
            $table->text('obs')->nullable()->after('cnaes');
        });
    }

    public function down(): void
    {
        Schema::table('estabelecimentos', function (Blueprint $table) {
            $table->dropColumn(['razao_social', 'nome_fantasia', 'cnpj', 'telefone', 'obs']);
        });
    }
};
