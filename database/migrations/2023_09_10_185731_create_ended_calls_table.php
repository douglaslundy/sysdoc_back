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
        Schema::create('ended_calls', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients');

            $table->unsignedBigInteger('call_service_forwarded_id')->nullable();
            $table->foreign('call_service_forwarded_id')->references('id')->on('call_services');

            $table->unsignedBigInteger('call_id')->nullable();
            $table->foreign('call_id')->references('id')->on('calls');

            $table->string('description', 500)->nullable();
            $table->enum('service_status', ['finished', 'forwarded'])->default('finished');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ended_calls');
    }
};
