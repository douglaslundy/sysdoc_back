<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_notice_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_notice_id')->constrained('system_notices')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('shown_at');
            $table->timestamps();

            $table->index(['system_notice_id', 'user_id', 'shown_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_notice_views');
    }
};
