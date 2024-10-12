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
        Schema::create('trip_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips'); // Viagem
            $table->foreignId('client_id')->constrained('clients'); // Cliente
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('trip_clients');
    }
};
