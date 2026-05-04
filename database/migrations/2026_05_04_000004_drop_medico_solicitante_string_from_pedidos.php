<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos_exame', function (Blueprint $table) {
            $table->dropColumn('medico_solicitante');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_exame', function (Blueprint $table) {
            $table->string('medico_solicitante', 100)->nullable();
        });
    }
};
