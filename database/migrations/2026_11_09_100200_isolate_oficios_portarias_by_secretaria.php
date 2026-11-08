<?php

use App\Support\IsolaPorSecretaria;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABELAS = ['letters', 'ordinances'];

    public function up(): void
    {
        IsolaPorSecretaria::up(self::TABELAS);

        // A numeração de portarias passa a ser por secretaria.
        Schema::table('ordinances', function (Blueprint $table) {
            $table->unique(['secretaria_id', 'number', 'year'], 'ordinances_secretaria_number_year_unique');
            $table->dropUnique(['number', 'year']);
        });
    }

    public function down(): void
    {
        // O índice único composto sustenta a FK: solta a FK antes de trocar os índices.
        IsolaPorSecretaria::dropFk('ordinances');
        Schema::table('ordinances', function (Blueprint $table) {
            $table->unique(['number', 'year']);
            $table->dropUnique('ordinances_secretaria_number_year_unique');
        });

        IsolaPorSecretaria::down(self::TABELAS);
    }
};
