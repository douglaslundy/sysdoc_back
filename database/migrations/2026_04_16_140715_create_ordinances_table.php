<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordinances', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('number');
            $table->year('year');

            $table->enum('type', ['normativa', 'ordinatoria']);

            $table->string('title', 255);
            $table->string('subject', 255);
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();

            $table->text('legal_basis')->nullable();
            $table->string('department', 150)->default('Secretaria Municipal de Saúde');

            $table->string('signatory_name', 150);
            $table->string('signatory_role', 150)->nullable();

            $table->date('publication_date');

            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->unique(['number', 'year']);
            $table->index('user_id');
            $table->index('year');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordinances');
    }
};