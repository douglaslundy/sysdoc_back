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
        Schema::create('model_ia', function (Blueprint $table) {
            $table->id();
            $table->integer('id_user');
            $table->string('summary', 500)->nullable();
            $table->string('prompt', 500)->nullable();
            $table->string('model', 1000)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_ia');
    }
};
