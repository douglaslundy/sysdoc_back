<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function(Blueprint $table){
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('cpf')->unique();
            $table->string('password');
        });

        Schema::create('sectors', function(Blueprint $table){
            $table->id();
            $table->string('name');
        });

        Schema::create('letters', function(Blueprint $table){
            $table->id();
            $table->integer('id_user');
            $table->integer('number')->unique();
            $table->date('date');
            $table->string('subject_matter', 100);
            $table->string('sender', 50);
            $table->string('recipient', 50);
            $table->string('obs', 1000)->nullable();
            $table->string('fileurl', 100)->nullable();
            $table->boolean('dispatched')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('all_tables');
    }
}
