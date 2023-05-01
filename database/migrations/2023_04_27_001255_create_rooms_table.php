<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('description', 50)->nullable();
            $table->enum('status', ['OPEN', 'BUSY', 'CLOSED'])->default('open');

            // falta fazer o validation daqui
            $table->unsignedBigInteger('call_service_id')->required();
            $table->foreign('call_service_id')->references('id')->on('call_services');
            // ate aqui

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
