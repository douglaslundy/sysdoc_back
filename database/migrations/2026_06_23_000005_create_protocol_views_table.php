<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protocol_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protocol_id')->constrained('protocols')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('departamento', 150)->nullable();
            $table->string('equipe', 150)->nullable();
            $table->dateTime('visualized_at')->nullable();
            $table->timestamps();

            $table->index(['protocol_id', 'visualized_at'], 'prot_views_protocol_visualized_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocol_views');
    }
};
