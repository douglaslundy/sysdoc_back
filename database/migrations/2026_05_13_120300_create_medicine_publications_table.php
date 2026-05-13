<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_publications', function (Blueprint $table) {
            $table->id();
            $table->enum('reference_type', ['daily', 'monthly']);
            $table->unsignedBigInteger('reference_id');
            $table->enum('channel', ['site', 'panel', 'instagram', 'facebook', 'other']);
            $table->enum('status', ['pending', 'published', 'failed'])->default('pending');
            $table->json('payload_summary')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['reference_type', 'reference_id'], 'idx_medicine_publication_ref');
            $table->index(['channel', 'status'], 'idx_medicine_publication_channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_publications');
    }
};

