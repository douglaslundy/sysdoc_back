<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_equipe_aps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nu_ine', 10);
            $table->string('no_equipe', 100);
            $table->unique(['user_id', 'nu_ine']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_equipe_aps');
    }
};
