<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change(); // Motorista opcional
            $table->foreignId('vehicle_id')->nullable()->change(); // Veículo opcional
            $table->timestamp('departure_time')->nullable()->change(); // Hora de saída opcional
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change(); // Volta para obrigatório
            $table->foreignId('vehicle_id')->nullable(false)->change(); // Volta para obrigatório
            $table->timestamp('departure_time')->nullable(false)->change(); // Volta para obrigatório
        });
    }
};
