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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 20);
            $table->string('model', 30);
            $table->string('color', 10);
            $table->string('license_plate', 7)->unique();
            $table->string('renavan', 11)->unique();
            $table->string('chassis', 17)->unique();
            $table->integer('capacity');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicles');
    }
};
