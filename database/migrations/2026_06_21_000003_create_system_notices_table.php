<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_notices', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('body');
            $table->longText('image_data')->nullable();
            $table->unsignedSmallInteger('times_per_day')->default(1);
            $table->unsignedSmallInteger('interval_minutes')->default(60);
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_notices');
    }
};
