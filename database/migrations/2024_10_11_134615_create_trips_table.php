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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // Motorista
            $table->foreignId('vehicle_id')->constrained('vehicles'); // Veículo
            $table->foreignId('route_id')->constrained('routes'); // Rota
            $table->timestamp('departure_time'); // Hora de saída
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('trips');
    }
};
